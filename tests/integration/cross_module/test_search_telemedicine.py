"""Cross-module integration tests: Search + Telemedicine."""
import os
import pytest
from datetime import datetime, timedelta, timezone
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import sessionmaker

os.environ.setdefault('JWT_SECRET_KEY', 'test-secret-key-for-integration')
os.environ.setdefault('JWT_ALGORITHM', 'HS256')

import sys
from pathlib import Path
sys.path.append(str(Path(__file__).resolve().parents[3] / 'python'))

from app.shared.models import Base
from app.modules.telemedicine.infrastructure.database_repository import DatabaseTelemedicineRepository
from app.modules.telemedicine.infrastructure.models import DoctorModel
from app.modules.search_ml.repository import SearchMLRepository
from app.modules.telemedicine.api.routes import set_repository as set_telemed_repo


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
        await conn.run_sync(Base.metadata.create_all)
    yield engine
    await engine.dispose()


@pytest.fixture
async def db_session(db_engine):
    async_session = sessionmaker(db_engine, class_=AsyncSession, expire_on_commit=False)
    async with async_session() as session:
        yield session


@pytest.fixture
async def telemed_repo(db_session):
    repo = DatabaseTelemedicineRepository(db_session)
    set_telemed_repo(repo)
    return repo


@pytest.fixture
async def search_repo(db_session):
    """SearchML repository using the same DB session as telemedicine."""
    repo = SearchMLRepository(session=db_session)
    return repo

@pytest.fixture
def client():
    """FastAPI TestClient with telemedicine router.
    Note: telemed_repo fixture must run before this to set repository.
    """
    from fastapi import FastAPI
    from app.modules.telemedicine.api import routes as telemed_routes
    app = FastAPI()
    app.include_router(telemed_routes.router)
    from fastapi.testclient import TestClient
    with TestClient(app) as client:
        yield client


@pytest.fixture(autouse=True)
async def seed_data(telemed_repo):
    """Seed a doctor that can be indexed and searched."""
    doctor = await telemed_repo.get_doctor("cross_doc_1")
    if not doctor:
        now = datetime.utcnow()
        from app.modules.telemedicine.domain.models import Doctor as DomainDoctor
        doc = DomainDoctor(
            id="cross_doc_1",
            user_id="doctor_user_x",
            full_name="Dr. Cross Module Test",
            specialty="General Practitioner",
            qualifications=["MD"],
            experience_years=5,
            rating=4.0,
            review_count=0,
            consultation_fee=100000,
            available_languages=["English"],
            profile_image=None,
            is_available=True,
            working_hours={},
            created_at=now,
            updated_at=now
        )
        await telemed_repo.create_doctor(doc)
    yield


class TestCrossModuleSearchTelemedicine:
    """Test integration between SearchML and Telemedicine modules."""

    @pytest.mark.asyncio
    async def test_index_doctor_and_search(self, telemed_repo, search_repo):
        """Doctor created in telemedicine should be searchable via search index."""
        # 1. Create doctor (already seeded)
        doctor = await telemed_repo.get_doctor("cross_doc_1")
        assert doctor is not None

        # 2. Index doctor into search (simulate some background indexing or explicit)
        # SearchMLRepository.index_entity expects entity_id, entity_type, content, metadata
        content = f"{doctor.full_name} - {doctor.specialty} - Available for consultations"
        await search_repo.index_entity(
            entity_id=doctor.id,
            entity_type="doctor",
            content=content,
            metadata={"specialty": doctor.specialty, "fee": doctor.consultation_fee}
        )

        # 3. Search for doctor by name
        results = await search_repo.search_index("Cross Module", entity_types=["doctor"])
        assert len(results) >= 1
        found = next((r for r in results if r.entity_id == doctor.id), None)
        assert found is not None
        assert doctor.full_name in found.content

    @pytest.mark.asyncio
    async def test_search_leads_to_appointment_booking(self, telemed_repo, search_repo, client, monkeypatch):
        """User searches doctor, finds one, and books appointment."""
        # 1. Seed doctor and index (already done by fixtures)
        doctor = await telemed_repo.get_doctor("cross_doc_1")
        assert doctor

        # 2. Simulate user search
        results = await search_repo.search_index("Cross Module", entity_types=["doctor"])
        assert len(results) >= 1
        doctor_id = results[0].entity_id

        # 3. Use telemedicine API to book appointment (with mock auth)
        from app.modules.telemedicine.api import routes as telemed_routes
        monkeypatch.setattr(
            telemed_routes,
            'telemedicine_user_with_rate_limit',
            lambda: "patient_cross_123"
        )
        appointment_time = (datetime.now(timezone.utc) + timedelta(days=1)).isoformat()
        payload = {
            "doctor_id": doctor_id,
            "patient_id": "patient_cross_123",
            "scheduled_time": appointment_time,
            "consultation_type": "video",
            "symptoms": "general checkup"
        }
        response = client.post("/api/telemedicine/appointments", json=payload)
        assert response.status_code in [200, 201]
        appt = response.json()
        assert appt["doctor_id"] == doctor_id
        assert appt["patient_id"] == "patient_cross_123"
        assert appt["status"] == "pending"

        # 4. Verify appointment exists in repo
        found_appt = await telemed_repo.get_appointment(appt["id"])
        assert found_appt is not None
        assert found_appt.doctor_id == doctor_id
