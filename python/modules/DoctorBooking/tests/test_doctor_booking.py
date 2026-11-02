import pytest
from unittest.mock import AsyncMock, MagicMock
from datetime import datetime, timezone, timedelta
from modules.DoctorBooking.models.schemas import Appointment, TimeSlot, Doctor, PatientInfo, Hospital, BookingStatus, PaymentStatus
from modules.DoctorBooking.service.service import DoctorBookingService

@pytest.fixture
def mock_db():
    return MagicMock()

@pytest.fixture
def doctor_booking_service(mock_db):
    svc = DoctorBookingService(mock_db)
    svc.appointment_repo = AsyncMock()
    svc.slot_repo = AsyncMock()
    svc.doctor_repo = AsyncMock()
    svc.hospital_repo = AsyncMock()
    svc.patient_repo = AsyncMock()
    return svc

@pytest.mark.asyncio
async def test_register_patient(doctor_booking_service):
    patient_data = {
        "id": "pat123",
        "user_id": "user123",
        "full_name": "John Doe",
        "phone": "123456789"
    }
    
    doctor_booking_service.patient_repo.create = AsyncMock(return_value=PatientInfo(**patient_data))
    
    result = await doctor_booking_service.register_patient(**patient_data)
    
    assert result is not None
    doctor_booking_service.patient_repo.create.assert_called_once()

@pytest.mark.asyncio
async def test_register_doctor(doctor_booking_service):
    doctor_data = {
        "id": "doc123",
        "hospital_id": "hosp123",
        "department_id": "dept123",
        "user_id": "doc123",
        "full_name": "Dr. Smith",
        "specializations": ["cardiology"],
        "license_number": "MD123",
        "years_of_experience": 10,
        "consultation_fee": 150.0,
        "is_available": True
    }
    
    doctor_booking_service.doctor_repo.create = AsyncMock(return_value=Doctor(**doctor_data))
    
    result = await doctor_booking_service.register_doctor(**doctor_data)
    
    assert result is not None
    doctor_booking_service.doctor_repo.create.assert_called_once()

@pytest.mark.asyncio
async def test_register_hospital(doctor_booking_service):
    hospital_data = {
        "id": "hosp123",
        "name": "General Hospital",
        "address": "123 Main St",
        "city": "Hanoi",
        "country": "Vietnam",
        "phone": "123456789"
    }
    
    doctor_booking_service.hospital_repo.create = AsyncMock(return_value=Hospital(**hospital_data))
    
    result = await doctor_booking_service.register_hospital(**hospital_data)
    
    assert result is not None

@pytest.mark.asyncio
async def test_get_hospitals(doctor_booking_service):
    doctor_booking_service.hospital_repo.get_all = AsyncMock(return_value=[])
    
    result = await doctor_booking_service.get_hospitals()
    
    assert result == []

@pytest.mark.asyncio
async def test_get_doctors(doctor_booking_service):
    doctor_booking_service.doctor_repo.get_all = AsyncMock(return_value=[])
    
    result = await doctor_booking_service.get_doctors()
    
    assert result == []

@pytest.mark.asyncio
async def test_create_time_slots_doctor_not_found(doctor_booking_service):
    doctor_booking_service.doctor_repo.get_by_id = AsyncMock(return_value=None)
    
    with pytest.raises(ValueError, match="Doctor not found"):
        await doctor_booking_service.create_time_slots("doc123", datetime.now(timezone.utc))

@pytest.mark.asyncio
async def test_book_appointment_doctor_not_found(doctor_booking_service):
    doctor_booking_service.doctor_repo.get_by_id = AsyncMock(return_value=None)
    
    with pytest.raises(ValueError, match="Doctor not available"):
        await doctor_booking_service.book_appointment(
            "patient123", "doc123", "slot123", "consultation"
        )

@pytest.mark.asyncio
async def test_book_appointment_slot_not_available(doctor_booking_service):
    doctor = Doctor(
        id="doc123",
        hospital_id="hosp123", department_id="dept123",
        user_id="doc123", full_name="Dr. Smith",
        specializations=["cardiology"], license_number="MD123",
        years_of_experience=10, consultation_fee=150.0,
        is_available=True
    )
    
    doctor_booking_service.doctor_repo.get_by_id = AsyncMock(return_value=doctor)
    doctor_booking_service.slot_repo.get_by_id = AsyncMock(return_value=None)
    
    with pytest.raises(ValueError, match="Time slot not available"):
        await doctor_booking_service.book_appointment(
            "patient123", "doc123", "slot123", "consultation"
        )

@pytest.mark.asyncio
async def test_rate_appointment_invalid_rating(doctor_booking_service):
    with pytest.raises(ValueError, match="Rating must be between 1 and 5"):
        await doctor_booking_service.rate_appointment("apt123", 6, "good")

@pytest.mark.asyncio
async def test_cancel_booking_not_found(doctor_booking_service):
    doctor_booking_service.appointment_repo.get_by_id = AsyncMock(return_value=None)
    
    with pytest.raises(ValueError, match="Appointment not found"):
        await doctor_booking_service.cancel_booking("apt123")

@pytest.mark.asyncio
async def test_cancel_booking_cannot_cancel(doctor_booking_service):
    appointment = Appointment(
        id="apt123",
        patient_id="patient123",
        doctor_id="doc123",
        status=BookingStatus.COMPLETED,
        hospital_id="hosp123",
        department_id="dept123",
        time_slot_id="slot123",
        appointment_type="consultation",
        booking_date=datetime.now(timezone.utc),
        fee=150.0,
        final_fee=150.0
    )
    
    doctor_booking_service.appointment_repo.get_by_id = AsyncMock(return_value=appointment)
    
    with pytest.raises(ValueError, match="Cannot cancel this booking"):
        await doctor_booking_service.cancel_booking("apt123")

@pytest.mark.asyncio
async def test_get_patient_bookings(doctor_booking_service):
    doctor_booking_service.appointment_repo.get_by_patient = AsyncMock(return_value=[])
    
    result = await doctor_booking_service.get_patient_bookings("patient123")
    
    assert result == []

@pytest.mark.asyncio
async def test_get_doctor_bookings(doctor_booking_service):
    doctor_booking_service.appointment_repo.get_by_doctor = AsyncMock(return_value=[])
    
    result = await doctor_booking_service.get_doctor_bookings("doc123")
    
    assert result == []

@pytest.mark.asyncio
async def test_get_patient(doctor_booking_service):
    doctor_booking_service.patient_repo.get_by_id = AsyncMock(return_value=None)
    
    result = await doctor_booking_service.get_patient("patient123")
    
    assert result is None

@pytest.mark.asyncio
async def test_update_patient(doctor_booking_service):
    doctor_booking_service.patient_repo.update = AsyncMock()
    doctor_booking_service.patient_repo.get_by_id = AsyncMock(return_value=PatientInfo(id="pat123", user_id="patient123", full_name="John Doe", phone="123456789"))
    
    result = await doctor_booking_service.update_patient("patient123", phone="987654321")
    
    doctor_booking_service.patient_repo.update.assert_called_once()

# --- Additional tests to increase coverage ---

@pytest.mark.asyncio
async def test_get_hospital_success(doctor_booking_service):
    hospital = Hospital(
        id="hosp1", name="General Hospital", address="123 Main St",
        city="Hanoi", country="Vietnam", phone="0123456789"
    )
    doctor_booking_service.hospital_repo.get_by_id = AsyncMock(return_value=hospital)
    result = await doctor_booking_service.get_hospital("hosp1")
    assert result == hospital

@pytest.mark.asyncio
async def test_get_hospital_not_found(doctor_booking_service):
    doctor_booking_service.hospital_repo.get_by_id = AsyncMock(return_value=None)
    result = await doctor_booking_service.get_hospital("nonexistent")
    assert result is None

@pytest.mark.asyncio
async def test_get_doctor_success(doctor_booking_service):
    doctor = Doctor(
        id="doc1", hospital_id="hosp1", department_id="dept1", user_id="user1",
        full_name="Dr. Smith", specializations=["cardiology"], license_number="LIC123",
        years_of_experience=5, consultation_fee=100.0, is_available=True
    )
    doctor_booking_service.doctor_repo.get_by_id = AsyncMock(return_value=doctor)
    result = await doctor_booking_service.get_doctor("doc1")
    assert result == doctor

@pytest.mark.asyncio
async def test_get_doctor_not_found(doctor_booking_service):
    doctor_booking_service.doctor_repo.get_by_id = AsyncMock(return_value=None)
    result = await doctor_booking_service.get_doctor("nonexistent")
    assert result is None

@pytest.mark.asyncio
async def test_get_doctors_with_specialization(doctor_booking_service):
    doctors = [Doctor(
        id="doc1", hospital_id="hosp1", department_id="dept1", user_id="user1",
        full_name="Dr. Smith", specializations=["cardiology"], license_number="LIC123",
        years_of_experience=5, consultation_fee=100.0, is_available=True
    )]
    doctor_booking_service.doctor_repo.get_all = AsyncMock(return_value=doctors)
    result = await doctor_booking_service.get_doctors(specialization="cardiology")
    assert result == doctors

@pytest.mark.asyncio
async def test_get_doctors_by_hospital(doctor_booking_service):
    doctors = [Doctor(
        id="doc1", hospital_id="hosp1", department_id="dept1", user_id="user1",
        full_name="Dr. Smith", specializations=["cardiology"], license_number="LIC123",
        years_of_experience=5, consultation_fee=100.0, is_available=True
    )]
    doctor_booking_service.doctor_repo.get_by_hospital = AsyncMock(return_value=doctors)
    result = await doctor_booking_service.get_doctors_by_hospital("hosp1")
    assert result == doctors

@pytest.mark.asyncio
async def test_search_doctors(doctor_booking_service):
    doctors = [Doctor(
        id="doc1", hospital_id="hosp1", department_id="dept1", user_id="user1",
        full_name="Dr. Smith", specializations=["cardiology"], license_number="LIC123",
        years_of_experience=5, consultation_fee=100.0, is_available=True
    )]
    doctor_booking_service.doctor_repo.search = AsyncMock(return_value=doctors)
    result = await doctor_booking_service.search_doctors("smith")
    assert result == doctors

@pytest.mark.asyncio
async def test_create_time_slots_success(doctor_booking_service):
    doctor = Doctor(
        id="doc123",
        hospital_id="hosp123",
        department_id="dept123",
        user_id="docuser123",
        full_name="Dr. Smith",
        specializations=["cardiology"],
        license_number="MD123",
        years_of_experience=10,
        consultation_fee=150.0,
        is_available=True
    )
    doctor_booking_service.doctor_repo.get_by_id = AsyncMock(return_value=doctor)
    doctor_booking_service.slot_repo.create = AsyncMock(side_effect=lambda slot: slot)
    date = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
    slots = await doctor_booking_service.create_time_slots("doc123", date)
    assert isinstance(slots, list)
    assert len(slots) > 0
    first = slots[0]
    assert first.doctor_id == "doc123"
    assert first.is_available is True
    assert first.fee == 150.0

@pytest.mark.asyncio
async def test_get_available_slots(doctor_booking_service):
    slot = TimeSlot(
        id="slot1", doctor_id="doc1", date=datetime.now(timezone.utc),
        start_time="08:00", end_time="08:30", is_available=True, fee=100.0
    )
    doctor_booking_service.slot_repo.get_available = AsyncMock(return_value=[slot])
    result = await doctor_booking_service.get_available_slots("doc1", datetime.now(timezone.utc))
    assert result == [slot]

@pytest.mark.asyncio
async def test_book_appointment_success(doctor_booking_service):
    doctor = Doctor(
        id="doc1",
        hospital_id="hosp1",
        department_id="dept1",
        user_id="docuser1",
        full_name="Dr. Jones",
        specializations=["dentistry"],
        license_number="L123",
        years_of_experience=5,
        consultation_fee=120.0,
        is_available=True
    )
    slot = TimeSlot(
        id="slot1", doctor_id="doc1", date=datetime.now(timezone.utc),
        start_time="08:00", end_time="08:30", is_available=True, fee=120.0
    )
    doctor_booking_service.doctor_repo.get_by_id = AsyncMock(return_value=doctor)
    doctor_booking_service.slot_repo.get_by_id = AsyncMock(return_value=slot)
    doctor_booking_service.slot_repo.book = AsyncMock(return_value=True)
    doctor_booking_service.appointment_repo.create = AsyncMock(return_value=Appointment(
        id="apt1", patient_id="patient1", doctor_id="doc1", time_slot_id="slot1",
        appointment_type="consultation", booking_date=datetime.now(timezone.utc), fee=120.0, final_fee=120.0,
        status=BookingStatus.PENDING, payment_status=PaymentStatus.PENDING,
        hospital_id="hosp1", department_id="dept1"
    ))
    appointment = await doctor_booking_service.book_appointment("patient1", "doc1", "slot1", "consultation")
    assert appointment is not None
    doctor_booking_service.slot_repo.book.assert_called_once_with("slot1")

@pytest.mark.asyncio
async def test_confirm_booking(doctor_booking_service):
    appointment_after = Appointment(
        id="apt1", patient_id="patient1", doctor_id="doc1", status=BookingStatus.CONFIRMED,
        time_slot_id="slot1", appointment_type="consultation", booking_date=datetime.now(timezone.utc),
        fee=100.0, final_fee=100.0, hospital_id="hosp1", department_id="dept1"
    )
    doctor_booking_service.appointment_repo.update_status = AsyncMock(return_value=appointment_after)
    result = await doctor_booking_service.confirm_booking("apt1")
    assert result.status == BookingStatus.CONFIRMED
    doctor_booking_service.appointment_repo.update_status.assert_called_once_with("apt1", BookingStatus.CONFIRMED)

@pytest.mark.asyncio
async def test_process_payment(doctor_booking_service):
    appointment = Appointment(
        id="apt1", patient_id="patient1", doctor_id="doc1", status=BookingStatus.CONFIRMED,
        time_slot_id="slot1", appointment_type="consultation", booking_date=datetime.now(timezone.utc),
        fee=100.0, final_fee=100.0, hospital_id="hosp1", department_id="dept1",
        payment_status=PaymentStatus.PAID
    )
    doctor_booking_service.appointment_repo.update_payment = AsyncMock(return_value=appointment)
    result = await doctor_booking_service.process_payment("apt1", "txn123")
    assert result.payment_status == PaymentStatus.PAID
    doctor_booking_service.appointment_repo.update_payment.assert_called_once_with("apt1", PaymentStatus.PAID, "txn123")

@pytest.mark.asyncio
async def test_rate_appointment_success(doctor_booking_service):
    # Original appointment without rating
    original_appointment = Appointment(
        id="apt1", patient_id="patient1", doctor_id="doc1",
        status=BookingStatus.COMPLETED, rating=None, review=None,
        time_slot_id="slot1", appointment_type="consultation", booking_date=datetime.now(timezone.utc),
        fee=100.0, final_fee=100.0, hospital_id="hosp1", department_id="dept1"
    )
    # After rating update
    updated_appointment = Appointment(
        id="apt1", patient_id="patient1", doctor_id="doc1",
        status=BookingStatus.COMPLETED, rating=5, review="Great",
        time_slot_id="slot1", appointment_type="consultation", booking_date=datetime.now(timezone.utc),
        fee=100.0, final_fee=100.0, hospital_id="hosp1", department_id="dept1"
    )
    doctor = Doctor(
        id="doc1", rating=4.5, total_reviews=2,
        hospital_id="hosp1", department_id="dept1", user_id="docuser1",
        full_name="Dr. Smith", specializations=["cardio"], license_number="LIC123",
        years_of_experience=5, consultation_fee=100.0, is_available=True
    )
    # Mock get_by_id to return original then updated
    doctor_booking_service.appointment_repo.get_by_id = AsyncMock(side_effect=[original_appointment, updated_appointment])
    doctor_booking_service.appointment_repo.update = AsyncMock()
    doctor_booking_service.doctor_repo.get_by_id = AsyncMock(return_value=doctor)
    doctor_booking_service.doctor_repo.update = AsyncMock()
    result = await doctor_booking_service.rate_appointment("apt1", 5, "Great")
    assert result.rating == 5
    assert result.review == "Great"
    doctor_booking_service.appointment_repo.update.assert_called_once()
    doctor_booking_service.doctor_repo.update.assert_called_once()

@pytest.mark.asyncio
async def test_get_booking_details(doctor_booking_service):
    appointment = Appointment(
        id="apt1", patient_id="patient1", doctor_id="doc1",
        status=BookingStatus.PENDING, time_slot_id="slot1", appointment_type="consultation",
        booking_date=datetime.now(timezone.utc), fee=100.0, final_fee=100.0,
        hospital_id="hosp1", department_id="dept1"
    )
    doctor_booking_service.appointment_repo.get_by_id = AsyncMock(return_value=appointment)
    result = await doctor_booking_service.get_booking_details("apt1")
    assert result == appointment

@pytest.mark.asyncio
async def test_get_today_appointments(doctor_booking_service):
    today = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
    appointments = [Appointment(
        id="apt1", patient_id="patient1", doctor_id="doc1",
        status=BookingStatus.PENDING, time_slot_id="slot1", appointment_type="consultation",
        booking_date=today, fee=100.0, final_fee=100.0,
        hospital_id="hosp1", department_id="dept1"
    )]
    doctor_booking_service.appointment_repo.get_by_doctor = AsyncMock(return_value=appointments)
    result = await doctor_booking_service.get_today_appointments("doc1")
    assert result == appointments

@pytest.mark.asyncio
async def test_update_patient_success(doctor_booking_service):
    patient = PatientInfo(id="pat1", user_id="user1", full_name="John Doe", phone="123456789")
    doctor_booking_service.patient_repo.update = AsyncMock()
    doctor_booking_service.patient_repo.get_by_id = AsyncMock(return_value=patient)
    result = await doctor_booking_service.update_patient("pat1", phone="987654321")
    doctor_booking_service.patient_repo.update.assert_called_once()
    assert result is not None

@pytest.mark.asyncio
async def test_get_hospitals_with_query(doctor_booking_service):
    hospital = Hospital(
        id="hosp1", name="General Hospital", address="123 Main St",
        city="Hanoi", country="Vietnam", phone="0123456789"
    )
    hospitals = [hospital]
    doctor_booking_service.hospital_repo.search = AsyncMock(return_value=hospitals)
    result = await doctor_booking_service.get_hospitals(query="General")
    assert result == hospitals
    doctor_booking_service.hospital_repo.search.assert_called_once_with("General")
