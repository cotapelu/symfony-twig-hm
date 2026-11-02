"""Tests for Telemedicine Service"""
import pytest


def test_import():
    """Test that the module can be imported"""
    from modules.Telemedicine.models.schemas import Appointment, TimeSlot, Doctor, Patient
    assert Appointment is not None
    assert TimeSlot is not None
    assert Doctor is not None
    assert Patient is not None


def test_appointment_status_enum():
    """Test AppointmentStatus enum"""
    from modules.Telemedicine.models.schemas import AppointmentStatus
    
    assert AppointmentStatus.PENDING.value == "pending"
    assert AppointmentStatus.CONFIRMED.value == "confirmed"


def test_consultation_type_enum():
    """Test ConsultationType enum"""
    from modules.Telemedicine.models.schemas import ConsultationType
    
    assert ConsultationType.VIDEO.value == "video"
    assert ConsultationType.AUDIO.value == "audio"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
