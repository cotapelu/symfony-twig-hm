"""Unit tests for DoctorManager"""
import pytest
from modules.DoctorBooking.doctor_manager import DoctorManager

@pytest.fixture
def manager():
    return DoctorManager()

def test_init(manager):
    assert manager.doctors == {}
    assert manager.appointments == {}
    assert manager.slots == {}

def test_search_doctors_empty(manager):
    assert manager.search_doctors('cardiology') == []

def test_book_appointment(manager):
    appt = manager.book_appointment(doctor_id=123, patient_id=456)
    assert appt['doctor_id'] == 123
    assert appt['patient_id'] == 456
    assert appt['status'] == 'pending'
    assert appt['id'] in manager.appointments

def test_cancel_appointment_existing(manager):
    appt = manager.book_appointment(doctor_id=1, patient_id=2)
    cancelled = manager.cancel_appointment(appt['id'])
    assert cancelled is not None
    assert cancelled['status'] == 'cancelled'
    assert manager.appointments[appt['id']]['status'] == 'cancelled'

def test_cancel_appointment_not_found(manager):
    result = manager.cancel_appointment('nonexistent')
    # Production returns a mock cancelled dict for non-existent ID
    assert result == {'id': 'nonexistent', 'status': 'cancelled'}

def test_confirm_appointment_existing(manager):
    appt = manager.book_appointment(doctor_id=1, patient_id=2)
    confirmed = manager.confirm_appointment(appt['id'])
    assert confirmed is not None
    assert confirmed['status'] == 'confirmed'
    assert manager.appointments[appt['id']]['status'] == 'confirmed'

def test_confirm_appointment_not_found(manager):
    result = manager.confirm_appointment('nonexistent')
    assert result is None

def test_get_available_slots_empty(manager):
    assert manager.get_available_slots(1) == []

def test_get_available_slots_availability(manager):
    manager.slots['s1'] = {'id': 's1', 'doctor_id': 1, 'is_available': True, 'time': '10:00'}
    manager.slots['s2'] = {'id': 's2', 'doctor_id': 1, 'is_available': False, 'time': '11:00'}
    manager.slots['s3'] = {'id': 's3', 'doctor_id': 2, 'is_available': True, 'time': '09:00'}
    slots = manager.get_available_slots(1)
    assert len(slots) == 1
    assert slots[0]['id'] == 's1'

def test_get_appointment_history_empty(manager):
    assert manager.get_appointment_history(patient_id=1) == []

def test_get_appointment_history_multiple(manager):
    manager.book_appointment(doctor_id=1, patient_id=100)
    manager.book_appointment(doctor_id=2, patient_id=100)
    manager.book_appointment(doctor_id=1, patient_id=200)  # another patient
    history = manager.get_appointment_history(100)
    assert len(history) == 2
    patient_ids = {a['patient_id'] for a in history}
    assert patient_ids == {100}