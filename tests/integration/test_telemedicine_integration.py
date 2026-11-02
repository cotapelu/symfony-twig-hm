"""
Integration tests for Telemedicine API endpoints.

Tests actual endpoint behavior with in-memory database.
"""

import pytest
import sys
import os
from datetime import datetime, timedelta
from fastapi.testclient import TestClient

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))


class TestTelemedicineIntegration:
    """Integration tests for telemedicine module"""

    @pytest.fixture(autouse=True)
    async def reset_globals(self, monkeypatch):
        """Reset in-memory repository before each test and seed with sample doctor."""
        from python.app.modules.telemedicine.infrastructure.in_memory_repository import InMemoryTelemedicineRepository
        import python.app.modules.telemedicine.domain.models as models

        # Create fresh repository
        fresh_repo = InMemoryTelemedicineRepository()

        # Add a sample doctor for tests
        doctor = models.Doctor(
            id="doc-1",
            user_id="doctor-1",  # distinct from patient user
            full_name="Dr. Smith",
            specialty="General Practice",
            qualifications=["MD"],
            experience_years=10,
            rating=4.5,
            review_count=20,
            consultation_fee=200000,
            available_languages=["en", "vi"],
            profile_image=None,
            is_available=True,
            working_hours={"mon-fri": "9-17"},
            created_at=models.datetime.utcnow()
        )
        await fresh_repo.create_doctor(doctor)

        # Override get_current_user_id to return test user BEFORE importing routes
        async def mock_get_current_user_id(payload=None):
            return 'user-123'
        monkeypatch.setattr('app.shared.middleware.auth.get_current_user_id', mock_get_current_user_id)

        # Now import routes and inject repository
        from python.app.modules.telemedicine.api import routes as telemed_routes
        telemed_routes.set_repository(fresh_repo)

        yield fresh_repo

        # Cleanup: reset repository after test
        fresh_repo2 = InMemoryTelemedicineRepository()
        from python.app.modules.telemedicine.api import routes as telemed_routes_cleanup
        telemed_routes_cleanup.set_repository(fresh_repo2)

    @pytest.fixture
    def client_with_auth(self):
        """Create FastAPI test client with proper dependency overrides for auth.
        Note: auth override is already handled in reset_globals fixture via monkeypatch.
        """
        from python.app.modules.telemedicine.api import routes as telemed_routes
        from fastapi import FastAPI

        app = FastAPI()
        app.include_router(telemed_routes.router)

        with TestClient(app) as client:
            yield client

    def test_search_doctors_returns_list(self, client_with_auth):
        """GET /api/telemedicine/doctors should return doctor list"""
        response = client_with_auth.get("/api/telemedicine/doctors")
        assert response.status_code == 200
        data = response.json()
        assert isinstance(data, list)
        assert len(data) >= 1  # at least the seeded doctor

    def test_get_doctor_by_id(self, client_with_auth):
        """GET /api/telemedicine/doctors/{id} should return doctor"""
        response = client_with_auth.get("/api/telemedicine/doctors/doc-1")
        assert response.status_code == 200
        doctor = response.json()
        assert doctor["id"] == "doc-1"
        assert doctor["full_name"] == "Dr. Smith"

    def test_get_doctor_availability(self, client_with_auth):
        """GET /api/telemedicine/doctors/{id}/availability should return slots"""
        # For simplicity, the route uses mock availability slots
        response = client_with_auth.get("/api/telemedicine/doctors/doc-1/availability")
        assert response.status_code == 200
        data = response.json()
        assert "available_slots" in data
        assert isinstance(data["available_slots"], list)

    def test_create_appointment_authenticated(self, client_with_auth):
        """POST /api/telemedicine/appointments should create appointment for authenticated user"""
        appointment_data = {
            "doctor_id": "doc-1",
            "patient_id": "user-123",  # must match auth
            "scheduled_time": (datetime.now() + timedelta(days=1)).isoformat(),
            "consultation_type": "video",
            "symptoms": "cough"
        }
        response = client_with_auth.post("/api/telemedicine/appointments", json=appointment_data)
        assert response.status_code in [200, 201], f"Expected success, got {response.status_code}"
        appt = response.json()
        assert appt["patient_id"] == "user-123"
        assert appt["doctor_id"] == "doc-1"
        assert appt["status"] == "pending"

    def test_create_appointment_patient_mismatch_fails(self, client_with_auth):
        """POST /appointments with patient_id != auth user should 403"""
        appointment_data = {
            "doctor_id": "doc-1",
            "patient_id": "other-patient",
            "scheduled_time": (datetime.now() + timedelta(days=1)).isoformat(),
            "consultation_type": "video",
            "symptoms": "test"
        }
        response = client_with_auth.post("/api/telemedicine/appointments", json=appointment_data)
        assert response.status_code == 403

    def test_get_appointment_ownership(self, client_with_auth, reset_globals):
        """GET /appointments/{id} should enforce ownership"""
        import python.app.modules.telemedicine.domain.models as models
        repo = reset_globals
        import asyncio

        # Create an appointment for patient-123 via repository
        appt_id = "appt-test-1"
        appointment = models.Appointment(
            id=appt_id,
            doctor_id="doc-1",
            patient_id="user-123",
            scheduled_time=models.datetime.now() + timedelta(days=1),
            duration_minutes=30,
            consultation_type=models.ConsultationType.VIDEO,
            status=models.AppointmentStatus.PENDING,
            symptoms="test",
            created_at=models.datetime.now()
        )
        asyncio.run(repo.create_appointment(appointment))

        response = client_with_auth.get(f"/api/telemedicine/appointments/{appt_id}")
        assert response.status_code == 200

        # Change appointment to belong to other patient using repo.update_appointment
        appointment.patient_id = "other-patient"
        asyncio.run(repo.update_appointment(appointment))

        response = client_with_auth.get(f"/api/telemedicine/appointments/{appt_id}")
        assert response.status_code == 403

    def test_cancel_appointment_ownership(self, client_with_auth, reset_globals):
        """POST /appointments/{id}/cancel should check ownership"""
        import python.app.modules.telemedicine.domain.models as models
        repo = reset_globals
        import asyncio

        appt_id = "appt-cancel-1"
        appointment = models.Appointment(
            id=appt_id,
            doctor_id="doc-1",
            patient_id="user-123",
            scheduled_time=models.datetime.now() + timedelta(days=1),
            duration_minutes=30,
            consultation_type=models.ConsultationType.VIDEO,
            status=models.AppointmentStatus.PENDING,
            symptoms="test",
            created_at=models.datetime.now()
        )
        asyncio.run(repo.create_appointment(appointment))

        response = client_with_auth.post(f"/api/telemedicine/appointments/{appt_id}/cancel")
        assert response.status_code == 200

        # Try cancelling appointment of another patient
        appointment.patient_id = "other-patient"
        asyncio.run(repo.update_appointment(appointment))

        response = client_with_auth.post(f"/api/telemedicine/appointments/{appt_id}/cancel")
        assert response.status_code == 403

    def test_confirm_appointment_doctor_only(self, client_with_auth, reset_globals):
        """POST /appointments/{id}/confirm should only allow assigned doctor"""
        import python.app.modules.telemedicine.domain.models as models
        repo = reset_globals
        import asyncio

        appt_id = "appt-confirm-1"
        appointment = models.Appointment(
            id=appt_id,
            doctor_id="doc-1",  # existing doctor with user_id != user-123
            patient_id="patient-1",
            scheduled_time=models.datetime.now() + timedelta(days=1),
            duration_minutes=30,
            consultation_type=models.ConsultationType.VIDEO,
            status=models.AppointmentStatus.PENDING,
            symptoms="test",
            created_at=models.datetime.now()
        )
        asyncio.run(repo.create_appointment(appointment))

        response = client_with_auth.post(f"/api/telemedicine/appointments/{appt_id}/confirm")
        assert response.status_code == 403

    def test_get_patient_appointments_own_only(self, client_with_auth):
        """GET /appointments/patient/{id} should only allow own appointments"""
        response = client_with_auth.get("/api/telemedicine/appointments/patient/user-123")
        # Should succeed even if no appointments (returns empty list)
        assert response.status_code == 200

        response = client_with_auth.get("/api/telemedicine/appointments/patient/other-patient")
        assert response.status_code == 403

    def test_get_doctor_appointments_doctor_only(self, client_with_auth, reset_globals):
        """GET /appointments/doctor/{id} should only allow that doctor"""
        import python.app.modules.telemedicine.domain.models as models
        repo = reset_globals
        import asyncio

        # Create a doctor whose user_id matches the authenticated user
        my_doctor = models.Doctor(
            id="my-doctor",
            user_id="user-123",
            full_name="Dr. Me",
            specialty="General",
            qualifications=[],
            experience_years=0,
            rating=0,
            review_count=0,
            consultation_fee=0,
            available_languages=[],
            is_available=True,
            working_hours={},
            created_at=datetime.now()
        )
        asyncio.run(repo.create_doctor(my_doctor))

        response = client_with_auth.get(f"/api/telemedicine/appointments/doctor/{my_doctor.id}")
        assert response.status_code == 200

        # Create another doctor with different user_id
        other_doctor = models.Doctor(
            id="other-doctor",
            user_id="other-user",
            full_name="Dr. Other",
            specialty="Some",
            qualifications=[],
            experience_years=0,
            rating=0,
            review_count=0,
            consultation_fee=0,
            available_languages=[],
            is_available=True,
            working_hours={},
            created_at=datetime.now()
        )
        asyncio.run(repo.create_doctor(other_doctor))

        response = client_with_auth.get("/api/telemedicine/appointments/doctor/other-doctor")
        assert response.status_code == 403


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
