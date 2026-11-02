"""Tests for Doctor Booking"""
import pytest
from modules.DoctorBooking.doctor_manager import DoctorManager


class TestDoctorManager:
    def setup_method(self):
        self.manager = DoctorManager()
    
    def test_search_doctors(self):
        """Test searching doctors by specialty"""
        specialty = "cardiology"
        doctors = self.manager.search_doctors(specialty)
        assert isinstance(doctors, list)
    
    def test_book_appointment(self):
        """Test booking an appointment"""
        doctor_id = 123
        patient_id = 456
        result = self.manager.book_appointment(doctor_id, patient_id)
        assert result is not None
        assert result['doctor_id'] == doctor_id
        assert result['patient_id'] == patient_id
    
    def test_cancel_appointment(self):
        """Test cancelling an appointment"""
        # First book an appointment
        booked = self.manager.book_appointment(123, 456)
        appointment_id = booked['id']
        
        # Then cancel it
        result = self.manager.cancel_appointment(appointment_id)
        assert result is not None
        assert result['status'] == 'cancelled'
    
    def test_get_available_slots(self):
        """Test getting available slots"""
        doctor_id = 123
        slots = self.manager.get_available_slots(doctor_id)
        assert isinstance(slots, list)
    
    def test_confirm_appointment(self):
        """Test confirming an appointment"""
        # First book an appointment
        booked = self.manager.book_appointment(123, 456)
        appointment_id = booked['id']
        
        # Then confirm it
        result = self.manager.confirm_appointment(appointment_id)
        assert result is not None
        assert result['status'] == 'confirmed'
    
    def test_get_appointment_history(self):
        """Test getting appointment history"""
        patient_id = 456
        # Book some appointments
        self.manager.book_appointment(123, patient_id)
        self.manager.book_appointment(124, patient_id)
        
        history = self.manager.get_appointment_history(patient_id)
        assert isinstance(history, list)
        assert len(history) >= 2


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
