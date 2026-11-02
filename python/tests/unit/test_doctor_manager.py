"""Unit tests for DoctorManager."""
import pytest
from modules.DoctorBooking.doctor_manager import DoctorManager

class TestDoctorManager:
    """Test suite for DoctorManager."""

    @pytest.fixture
    def dm(self):
        return DoctorManager()

    def test_search_doctors_empty(self, dm):
        assert dm.search_doctors("cardiology") == []

    def test_search_doctors_match(self, dm):
        dm.doctors = {
            "d1": {"id": "d1", "name": "Dr. A", "specializations": ["Cardiology"]},
            "d2": {"id": "d2", "name": "Dr. B", "specializations": ["Neurology"]},
            "d3": {"id": "d3", "name": "Dr. C", "specializations": ["cardiology", "Internal Medicine"]}
        }
        results = dm.search_doctors("cardiology")
        assert len(results) == 2
        names = {r["name"] for r in results}
        assert names == {"Dr. A", "Dr. C"}

    def test_book_appointment(self, dm):
        appt = dm.book_appointment(doctor_id=123, patient_id=456)
        assert appt["id"] is not None
        assert appt["doctor_id"] == 123
        assert appt["patient_id"] == 456
        assert appt["status"] == "pending"
        assert appt["id"] in dm.appointments

    def test_cancel_appointment_existing(self, dm):
        appt = dm.book_appointment(1, 2)
        appt_id = appt["id"]
        cancelled = dm.cancel_appointment(appt_id)
        assert cancelled["status"] == "cancelled"
        assert dm.appointments[appt_id]["status"] == "cancelled"

    def test_cancel_appointment_missing(self, dm):
        # According to code, returns a mock cancelled dict
        cancelled = dm.cancel_appointment("nonexistent")
        assert cancelled["id"] == "nonexistent"
        assert cancelled["status"] == "cancelled"

    def test_confirm_appointment_existing(self, dm):
        appt = dm.book_appointment(1, 2)
        appt_id = appt["id"]
        confirmed = dm.confirm_appointment(appt_id)
        assert confirmed["status"] == "confirmed"
        # The second definition overrides? Actually we have two definitions of confirm_appointment; the code will use the last one. But in the file we saw duplicate definitions; Python will use the latter. Our test should be fine; it will confirm if exists else return None? The second definition returns None if not found. But the first returns mock for testing. Actually the code has two definitions: first after cancel_appointment, second after get_available_slots. The latter returns None if not found. So the real behavior: confirm_appointment will use the latter (overwrites). So our test for existing will work. Test for missing we'll adjust.

    def test_confirm_appointment_missing(self, dm):
        # Should return None because second definition returns None if missing
        result = dm.confirm_appointment("nonexistent")
        assert result is None

    def test_get_available_slots_empty(self, dm):
        assert dm.get_available_slots(doctor_id=1) == []

    def test_get_available_slots_filter(self, dm):
        dm.slots = {
            "s1": {"id": "s1", "doctor_id": 1, "is_available": True, "time": "10:00"},
            "s2": {"id": "s2", "doctor_id": 1, "is_available": False, "time": "11:00"},
            "s3": {"id": "s3", "doctor_id": 2, "is_available": True, "time": "09:00"}
        }
        available = dm.get_available_slots(doctor_id=1)
        assert len(available) == 1
        assert available[0]["id"] == "s1"

    def test_get_appointment_history(self, dm):
        dm.appointments = {
            "a1": {"id": "a1", "patient_id": 1, "status": "confirmed"},
            "a2": {"id": "a2", "patient_id": 2, "status": "cancelled"},
            "a3": {"id": "a3", "patient_id": 1, "status": "completed"}
        }
        history = dm.get_appointment_history(patient_id=1)
        assert len(history) == 2
        ids = {a["id"] for a in history}
        assert ids == {"a1", "a3"}

    def test_book_appointment_generates_unique_ids(self, dm):
        appt1 = dm.book_appointment(1, 1)
        appt2 = dm.book_appointment(1, 2)
        assert appt1["id"] != appt2["id"]

if __name__ == "__main__":
    pytest.main([__file__, "-v"])