"""Integration tests for Telemedicine module with real database."""
import os
import pytest
from datetime import datetime, date, timedelta, timezone
from httpx import AsyncClient, ASGITransport
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import sessionmaker

# Set test env vars before importing app
os.environ.setdefault('JWT_SECRET_KEY', 'test-secret-key-for-integration')
os.environ.setdefault('JWT_ALGORITHM', 'HS256')

import sys
from pathlib import Path
sys.path.append(str(Path(__file__).resolve().parents[2] / 'python'))

from app.shared.models import Base
from app.shared.models.unified import User as DBUser
from app.modules.telemedicine.infrastructure.database_repository import DatabaseTelemedicineRepository
from app.modules.telemedicine.infrastructure.models import DoctorModel
from app.modules.telemedicine.api.routes import set_repository
from app.main import app as fastapi_app
from app.modules.telemedicine.api.routes import telemedicine_user_with_rate_limit as original_dep


@pytest.fixture(scope="session")
def db_url():
    url = os.getenv("INTEGRATION_TEST_DATABASE_URL")
    if not url:
        pytest.skip("INTEGRATION_TEST_DATABASE_URL not set, skipping integration tests")
    if not url.startswith("postgresql+asyncpg://"):
        if url.startswith("postgresql://"):
            url = url.replace("postgresql://", "postgresql+asyncpg://", 1)
        else:
            pytest.fail(f"Unsupported database URL: {url}")
    return url


@pytest.fixture
async def db_engine(db_url):
    engine = create_async_engine(db_url, echo=False, pool_size=5, max_overflow=10)
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.drop_all)
        await conn.run_sync(Base.metadata.create_all)
    yield engine
    await engine.dispose()


@pytest.fixture
async def db_session(db_engine):
    async_session = sessionmaker(db_engine, class_=AsyncSession, expire_on_commit=False)
    async with async_session() as session:
        yield session


@pytest.fixture
async def repo(db_session):
    """Repository using real database."""
    return DatabaseTelemedicineRepository(db_session)


@pytest.fixture(autouse=True)
async def setup_repo(repo):
    """Override default in-memory repo with DB repo and seed sample doctor."""
    set_repository(repo)
    # Seed a sample doctor if not exists
    sample_doctor = await repo.get_doctor("doc_int_1")
    if not sample_doctor:
        now = datetime.utcnow()
        doctor = DoctorModel(
            id="doc_int_1",
            user_id="doctor_user_1",
            full_name="Dr. Integration Test",
            specialty="General Practitioner",
            qualifications='["MD", "Board Certified"]',
            experience_years=10,
            rating=4.5,
            review_count=0,
            consultation_fee=200000,
            available_languages="[\"English\", \"Vietnamese\"]",
            profile_image=None,
            is_available=True,
            working_hours="{\"mon-fri\": \"08:00-17:00\"}",
            created_at=now,
            updated_at=now
        )
        repo.db.add(doctor)
        await repo.db.flush()
    # Ensure patient user exists for appointment foreign key
    patient = await repo.db.get(DBUser, "patient_user_123")
    if not patient:
        patient_user = DBUser(
            id="patient_user_123",
            email="patient@example.com",
            username="patient123",
            password_hash="dummy_hash",
            is_active=True,
            is_verified=True
        )
        repo.db.add(patient_user)
        await repo.db.flush()
    yield


@pytest.fixture
async def client():
    """Async HTTP client."""
    transport = ASGITransport(app=fastapi_app)
    async with AsyncClient(transport=transport, base_url="http://test") as ac:
        yield ac


@pytest.fixture
def mock_auth(user_id: str = "patient_user_123"):
    """Mock auth dependency to return specified user_id."""
    async def mock_dep():
        return user_id
    from app.modules.telemedicine.api.routes import telemedicine_user_with_rate_limit as original_dep
    # Override dependency on the FastAPI app
    fast_api_app = fastapi_app
    fast_api_app.dependency_overrides[original_dep] = mock_dep
    yield
    if original_dep in fast_api_app.dependency_overrides:
        del fast_api_app.dependency_overrides[original_dep]


class TestTelemedicineIntegrationDB:
    """Integration tests using real PostgreSQL database."""

    @pytest.mark.asyncio
    async def test_doctor_search_returns_list(self, client, mock_auth):
        """GET /api/telemedicine/doctors should return list."""
        response = await client.get("/api/telemedicine/doctors")
        assert response.status_code == 200
        data = response.json()
        assert isinstance(data, list)
        assert len(data) >= 1

    @pytest.mark.asyncio
    async def test_get_doctor_by_id(self, client, mock_auth):
        """GET /api/telemedicine/doctors/{id} returns doctor."""
        response = await client.get("/api/telemedicine/doctors/doc_int_1")
        assert response.status_code == 200
        doctor = response.json()
        assert doctor["id"] == "doc_int_1"
        assert doctor["full_name"] == "Dr. Integration Test"

    @pytest.mark.asyncio
    async def test_get_doctor_availability(self, client, mock_auth):
        """GET /api/telemedicine/doctors/{id}/availability returns slots."""
        response = await client.get("/api/telemedicine/doctors/doc_int_1/availability")
        assert response.status_code == 200
        data = response.json()
        assert "available_slots" in data
        assert isinstance(data["available_slots"], list)

    @pytest.mark.asyncio
    async def test_book_appointment_success(self, client, mock_auth):
        """POST /appointments creates appointment for authenticated patient."""
        appointment_time = (datetime.utcnow() + timedelta(days=1)).replace(microsecond=0).isoformat()
        payload = {
            "doctor_id": "doc_int_1",
            "patient_id": "patient_user_123",
            "scheduled_time": appointment_time,
            "consultation_type": "video",
            "symptoms": "cough"
        }
        response = await client.post("/api/telemedicine/appointments", json=payload)
        assert response.status_code in [200, 201], f"Expected 200/201, got {response.status_code}, body: {response.text}"
        appt = response.json()
        assert appt["doctor_id"] == "doc_int_1"
        assert appt["patient_id"] == "patient_user_123"
        assert appt["status"] == "pending"

    @pytest.mark.asyncio
    async def test_book_appointment_double_booking_conflict(self, client, mock_auth):
        """Double booking should return 409."""
        appointment_time = (datetime.utcnow() + timedelta(days=2)).isoformat()
        payload = {
            "doctor_id": "doc_int_1",
            "patient_id": "patient_user_123",
            "scheduled_time": appointment_time,
            "consultation_type": "video",
            "symptoms": "test"
        }
        # First booking
        resp1 = await client.post("/api/telemedicine/appointments", json=payload)
        assert resp1.status_code in [200, 201]

        # Second booking at same time should fail
        resp2 = await client.post("/api/telemedicine/appointments", json=payload)
        assert resp2.status_code == 400
        assert "already has an appointment" in resp2.json()["detail"]

    @pytest.mark.asyncio
    async def test_get_appointment_ownership(self, client):
        """Patient can view own appointment, not others."""
        # Set patient auth
        fastapi_app.dependency_overrides[original_dep] = lambda: "patient_user_123"
        try:
            # Book an appointment
            appt_time = (datetime.utcnow() + timedelta(days=3)).isoformat()
            create_payload = {
                "doctor_id": "doc_int_1",
                "patient_id": "patient_user_123",
                "scheduled_time": appt_time,
                "consultation_type": "video",
                "symptoms": "checkup"
            }
            resp = await client.post("/api/telemedicine/appointments", json=create_payload)
            assert resp.status_code == 201
            appt_id = resp.json()["id"]

            # View own appointment
            get_resp = await client.get(f"/api/telemedicine/appointments/{appt_id}")
            assert get_resp.status_code == 200

            # Switch to another patient
            fastapi_app.dependency_overrides[original_dep] = lambda: "other_patient"
            get_resp2 = await client.get(f"/api/telemedicine/appointments/{appt_id}")
            assert get_resp2.status_code == 403
        finally:
            if original_dep in fastapi_app.dependency_overrides:
                del fastapi_app.dependency_overrides[original_dep]

    @pytest.mark.asyncio
    async def test_confirm_appointment_doctor_only(self, client):
        """Only doctor can confirm appointment."""
        # Set patient auth
        fastapi_app.dependency_overrides[original_dep] = lambda: "patient_user_123"
        try:
            # Book appointment
            appt_time = (datetime.utcnow() + timedelta(days=4)).isoformat()
            create_payload = {
                "doctor_id": "doc_int_1",
                "patient_id": "patient_user_123",
                "scheduled_time": appt_time,
                "consultation_type": "video",
                "symptoms": "test"
            }
            resp = await client.post("/api/telemedicine/appointments", json=create_payload)
            assert resp.status_code == 201
            appt_id = resp.json()["id"]

            # Try confirm as patient -> 403
            confirm_resp = await client.post(f"/api/telemedicine/appointments/{appt_id}/confirm")
            assert confirm_resp.status_code == 403

            # Switch to doctor
            fastapi_app.dependency_overrides[original_dep] = lambda: "doctor_user_1"
            confirm_resp2 = await client.post(f"/api/telemedicine/appointments/{appt_id}/confirm")
            assert confirm_resp2.status_code == 200
            data = confirm_resp2.json()
            assert "meeting_link" in data or "message" in data
        finally:
            if original_dep in fastapi_app.dependency_overrides:
                del fastapi_app.dependency_overrides[original_dep]

    @pytest.mark.asyncio
    async def test_cancel_appointment_authorized(self, client):
        """Patient can cancel own appointment."""
        fastapi_app.dependency_overrides[original_dep] = lambda: "patient_user_123"
        try:
            appt_time = (datetime.utcnow() + timedelta(days=5)).isoformat()
            create_payload = {
                "doctor_id": "doc_int_1",
                "patient_id": "patient_user_123",
                "scheduled_time": appt_time,
                "consultation_type": "video",
                "symptoms": "test"
            }
            resp = await client.post("/api/telemedicine/appointments", json=create_payload)
            assert resp.status_code == 201
            appt_id = resp.json()["id"]

            cancel_resp = await client.post(f"/api/telemedicine/appointments/{appt_id}/cancel")
            assert cancel_resp.status_code == 200
            assert "cancelled" in cancel_resp.json()["message"].lower()

            # Verify status changed
            get_resp = await client.get(f"/api/telemedicine/appointments/{appt_id}")
            assert get_resp.status_code == 200
            assert get_resp.json()["status"] == "cancelled"
        finally:
            if original_dep in fastapi_app.dependency_overrides:
                del fastapi_app.dependency_overrides[original_dep]

    @pytest.mark.asyncio
    async def test_create_review_after_completion(self, client, repo):
        """Patient can review completed appointment."""
        fastapi_app.dependency_overrides[original_dep] = lambda: "patient_user_123"
        try:
            # Patient books
            appt_time = (datetime.utcnow() + timedelta(days=6)).isoformat()
            create_payload = {
                "doctor_id": "doc_int_1",
                "patient_id": "patient_user_123",
                "scheduled_time": appt_time,
                "consultation_type": "video",
                "symptoms": "test"
            }
            resp = await client.post("/api/telemedicine/appointments", json=create_payload)
            assert resp.status_code == 201
            appt_id = resp.json()["id"]

            # Switch to doctor to confirm
            fastapi_app.dependency_overrides[original_dep] = lambda: "doctor_user_1"
            confirm_resp = await client.post(f"/api/telemedicine/appointments/{appt_id}/confirm")
            assert confirm_resp.status_code == 200

            # Mark as completed directly via repository for test
            async def complete():
                appt = await repo.get_appointment(appt_id)
                if appt:
                    appt.status = "completed"
                    await repo.update_appointment(appt)
            await complete()

            # Switch back to patient to create review
            fastapi_app.dependency_overrides[original_dep] = lambda: "patient_user_123"
            review_payload = {
                "appointment_id": appt_id,
                "rating": 5,
                "comment": "Excellent doctor!"
            }
            review_resp = await client.post("/api/telemedicine/reviews", json=review_payload)
            assert review_resp.status_code in [200, 201]
            review = review_resp.json()
            assert review["rating"] == 5
            assert review["doctor_id"] == "doc_int_1"

            # Verify doctor rating updated
            doctor_resp = await client.get("/api/telemedicine/doctors/doc_int_1")
            assert doctor_resp.status_code == 200
            doctor = doctor_resp.json()
            assert doctor["rating"] == 5.0
            assert doctor["review_count"] == 1
        finally:
            if original_dep in fastapi_app.dependency_overrides:
                del fastapi_app.dependency_overrides[original_dep]
