import pytest
from unittest.mock import AsyncMock, MagicMock
from datetime import datetime, timezone, timedelta
from modules.Telemedicine.service.service import TelemedicineService
from modules.Telemedicine.models.schemas import (
    Patient, Doctor, TimeSlot, Appointment, VideoSession, MedicalRecord,
    ConsultationType, AppointmentStatus, VideoSessionStatus
)

@pytest.fixture
def mock_db():
    return MagicMock()

@pytest.fixture
def service(mock_db):
    s = TelemedicineService(mock_db)
    s.patient_repo = AsyncMock()
    s.doctor_repo = AsyncMock()
    s.slot_repo = AsyncMock()
    s.appointment_repo = AsyncMock()
    s.session_repo = AsyncMock()
    s.record_repo = AsyncMock()
    return s

def make_patient(id="p1", user_id="u1", full_name="John Doe", **kwargs):
    return Patient(id=id, user_id=user_id, full_name=full_name, **kwargs)

def make_doctor(id="d1", user_id="u2", full_name="Dr. Smith", specializations=["Cardiology"],
                license_number="LIC123", years_of_experience=5, consultation_fee=100.0, is_available=True, **kwargs):
    return Doctor(
        id=id, user_id=user_id, full_name=full_name, specializations=specializations,
        license_number=license_number, years_of_experience=years_of_experience,
        consultation_fee=consultation_fee, is_available=is_available, **kwargs
    )

def make_timeslot(id="ts1", doctor_id="d1", date=None, start_time="08:00", end_time="08:30", is_available=True):
    if date is None:
        date = datetime.now(timezone.utc)
    return TimeSlot(
        id=id, doctor_id=doctor_id, date=date, start_time=start_time, end_time=end_time,
        is_available=is_available
    )

def make_appointment(
    id="a1", patient_id="p1", doctor_id="d1", time_slot_id="ts1",
    appointment_type=ConsultationType.VIDEO, chief_complaint="Chest pain", symptoms=["pressure"],
    fee=100.0, final_fee=100.0, scheduled_at=None, status=AppointmentStatus.PENDING,
    rating=None, review=None, **kwargs
):
    if scheduled_at is None:
        scheduled_at = datetime.now(timezone.utc)
    return Appointment(
        id=id, patient_id=patient_id, doctor_id=doctor_id, time_slot_id=time_slot_id,
        appointment_type=appointment_type, chief_complaint=chief_complaint, symptoms=symptoms,
        fee=fee, final_fee=final_fee, scheduled_at=scheduled_at, status=status,
        rating=rating, review=review, **kwargs
    )

def make_video_session(id="vs1", appointment_id="a1", room_id="room1", token="token1", started_at=None, ended_at=None):
    return VideoSession(
        id=id, appointment_id=appointment_id, room_id=room_id, token=token,
        started_at=started_at, ended_at=ended_at
    )

def make_medical_record(id="mr1", patient_id="p1", appointment_id="a1", doctor_id="d1",
                        diagnosis="MI", treatment_plan="Surgery", prescription="Drug", notes=None):
    return MedicalRecord(
        id=id, patient_id=patient_id, appointment_id=appointment_id, doctor_id=doctor_id,
        diagnosis=diagnosis, treatment_plan=treatment_plan, prescription=prescription, notes=notes
    )

# __init__ test
def test_init_creates_repositories(service, mock_db):
    assert service.db is mock_db

# Patient registration
@pytest.mark.asyncio
async def test_register_patient_success(service):
    patient = make_patient(user_id="u123", full_name="Alice", gender="F", date_of_birth="1990-01-01")
    service.patient_repo.create.return_value = patient
    result = await service.register_patient(user_id="u123", full_name="Alice", gender="F", date_of_birth="1990-01-01")
    service.patient_repo.create.assert_awaited_once()
    assert result.user_id == "u123"
    assert result.full_name == "Alice"

@pytest.mark.asyncio
async def test_register_patient_with_extra_fields(service):
    patient = make_patient(user_id="u124", full_name="Bob", phone="1234567890")
    service.patient_repo.create.return_value = patient
    result = await service.register_patient(user_id="u124", full_name="Bob", phone="1234567890")
    assert result.phone == "1234567890"

# Doctor registration
@pytest.mark.asyncio
async def test_register_doctor_success(service):
    doctor = make_doctor(
        user_id="u200", full_name="Dr. Who", specializations=["Pediatrics"],
        license_number="ABC987", years_of_experience=10, consultation_fee=150.0
    )
    service.doctor_repo.create.return_value = doctor
    result = await service.register_doctor(
        user_id="u200", full_name="Dr. Who", specializations=["Pediatrics"],
        license_number="ABC987", years_of_experience=10, consultation_fee=150.0
    )
    service.doctor_repo.create.assert_awaited_once()
    assert result.specializations == ["Pediatrics"]
    assert result.consultation_fee == 150.0

# Get doctors
@pytest.mark.asyncio
async def test_get_doctors_no_filter(service):
    doctors = [make_doctor(id="d1"), make_doctor(id="d2")]
    service.doctor_repo.get_all.return_value = doctors
    result = await service.get_doctors()
    service.doctor_repo.get_all.assert_awaited_once_with(None)
    assert len(result) == 2

@pytest.mark.asyncio
async def test_get_doctors_with_specialization(service):
    doctors = [make_doctor(id="d1", specializations=["Cardiology"])]
    service.doctor_repo.get_all.return_value = doctors
    result = await service.get_doctors(specialization="Cardiology")
    service.doctor_repo.get_all.assert_awaited_once_with("Cardiology")
    assert len(result) == 1

@pytest.mark.asyncio
async def test_search_doctors(service):
    doctors = [make_doctor(id="d1", full_name="Dr. Smith")]
    service.doctor_repo.search.return_value = doctors
    result = await service.search_doctors("Smith")
    service.doctor_repo.search.assert_awaited_once_with("Smith")
    assert result[0].full_name == "Dr. Smith"

@pytest.mark.asyncio
async def test_get_doctor_by_id_found(service):
    doctor = make_doctor(id="d1")
    service.doctor_repo.get_by_id.return_value = doctor
    result = await service.get_doctor_by_id("d1")
    service.doctor_repo.get_by_id.assert_awaited_once_with("d1")
    assert result.id == "d1"

@pytest.mark.asyncio
async def test_get_doctor_by_id_not_found(service):
    service.doctor_repo.get_by_id.return_value = None
    result = await service.get_doctor_by_id("nonexistent")
    assert result is None

# Time slots
@pytest.mark.asyncio
async def test_get_available_slots(service):
    slots = [make_timeslot(id="ts1"), make_timeslot(id="ts2")]
    service.slot_repo.get_available_slots.return_value = slots
    date = datetime.now(timezone.utc)
    result = await service.get_available_slots("d1", date)
    service.slot_repo.get_available_slots.assert_awaited_once_with("d1", date)
    assert len(result) == 2

@pytest.mark.asyncio
async def test_create_time_slots(service):
    date = datetime(2025, 1, 1, tzinfo=timezone.utc)
    # Echo the created slot
    # Echo the created slot for each call
    service.slot_repo.create.side_effect = lambda slot: slot
    result = await service.create_time_slots("d1", date)
    # 9 hours from 8 to 17 with 30-min slots = 18 slots
    assert len(result) == 18
    assert service.slot_repo.create.call_count == 18

# Appointment booking
@pytest.mark.asyncio
async def test_book_appointment_success(service):
    now = datetime.now(timezone.utc)
    slot = make_timeslot(id="ts1", date=now, start_time="08:00", end_time="08:30")
    doctor = make_doctor(id="d1", consultation_fee=100.0, is_available=True)
    service.slot_repo.get_available_slots.return_value = [slot]
    service.doctor_repo.get_by_id.return_value = doctor
    service.slot_repo.book_slot.return_value = None
    scheduled_at = datetime.combine(now.date(), datetime.strptime(slot.start_time, "%H:%M").time()).replace(tzinfo=timezone.utc)
    appointment = make_appointment(
        id="a1", patient_id="p1", doctor_id="d1", time_slot_id="ts1",
        scheduled_at=scheduled_at, appointment_type=ConsultationType.VIDEO
    )
    service.appointment_repo.create.return_value = appointment
    result = await service.book_appointment(
        patient_id="p1", doctor_id="d1", time_slot_id="ts1",
        appointment_type=ConsultationType.VIDEO, chief_complaint="Chest pain", symptoms=["pressure"]
    )
    service.slot_repo.book_slot.assert_awaited_once_with("ts1")
    service.appointment_repo.create.assert_awaited_once()
    assert result.time_slot_id == "ts1"
    assert result.fee == 100.0

@pytest.mark.asyncio
async def test_book_appointment_slot_not_available(service):
    service.slot_repo.get_available_slots.return_value = []
    with pytest.raises(ValueError, match="Time slot not available"):
        await service.book_appointment(
            patient_id="p1", doctor_id="d1", time_slot_id="ts1",
            appointment_type=ConsultationType.VIDEO, chief_complaint="Chest pain", symptoms=[]
        )

@pytest.mark.asyncio
async def test_book_appointment_doctor_not_available(service):
    slot = make_timeslot(id="ts1")
    service.slot_repo.get_available_slots.return_value = [slot]
    service.doctor_repo.get_by_id.return_value = make_doctor(id="d1", is_available=False)
    with pytest.raises(ValueError, match="Doctor not available"):
        await service.book_appointment(
            patient_id="p1", doctor_id="d1", time_slot_id="ts1",
            appointment_type=ConsultationType.VIDEO, chief_complaint="Chest pain", symptoms=[]
        )

# Confirm appointment
@pytest.mark.asyncio
async def test_confirm_appointment(service):
    updated_appointment = make_appointment(id="a1", status=AppointmentStatus.CONFIRMED)
    service.appointment_repo.update_status.return_value = updated_appointment
    result = await service.confirm_appointment("a1")
    service.appointment_repo.update_status.assert_awaited_once_with("a1", AppointmentStatus.CONFIRMED)
    assert result.status == AppointmentStatus.CONFIRMED

# Start consultation
@pytest.mark.asyncio
async def test_start_consultation_success(service):
    appointment = make_appointment(id="a1", status=AppointmentStatus.CONFIRMED)
    service.appointment_repo.get_by_id.return_value = appointment
    session = make_video_session(id="vs1", appointment_id="a1")
    service.session_repo.create.return_value = session
    result = await service.start_consultation("a1")
    service.appointment_repo.update_status.assert_awaited_once_with("a1", AppointmentStatus.IN_PROGRESS)
    assert result.appointment_id == "a1"

@pytest.mark.asyncio
async def test_start_consultation_appointment_not_found(service):
    service.appointment_repo.get_by_id.return_value = None
    with pytest.raises(ValueError, match="Appointment not found"):
        await service.start_consultation("nonexistent")

# End consultation
@pytest.mark.asyncio
async def test_end_consultation_success(service):
    appointment = make_appointment(id="a1", patient_id="p1", doctor_id="d1")
    started = datetime.now(timezone.utc) - timedelta(seconds=600)
    session = make_video_session(id="vs1", appointment_id="a1", started_at=started)
    service.appointment_repo.get_by_id.return_value = appointment
    service.session_repo.get_by_appointment.return_value = session
    record = make_medical_record(id="mr1", patient_id="p1", doctor_id="d1", appointment_id="a1")
    service.record_repo.create.return_value = record
    result = await service.end_consultation("a1", diagnosis="MI", treatment_plan="Surgery", prescription="Drug")
    service.session_repo.end_session.assert_awaited_once_with("vs1", 600)
    service.appointment_repo.update_status.assert_awaited_once_with("a1", AppointmentStatus.COMPLETED)
    assert result.diagnosis == "MI"

@pytest.mark.asyncio
async def test_end_consultation_appointment_not_found(service):
    service.appointment_repo.get_by_id.return_value = None
    with pytest.raises(ValueError, match="Appointment not found"):
        await service.end_consultation("a1", diagnosis="X", treatment_plan="Y", prescription="Z")

# Rate appointment
@pytest.mark.asyncio
async def test_rate_appointment_success(service):
    appointment = make_appointment(id="a1", doctor_id="d1", rating=None)
    doctor = make_doctor(id="d1", rating=4.5, total_reviews=2)
    service.appointment_repo.get_by_id.return_value = appointment
    service.doctor_repo.get_by_id.return_value = doctor
    await service.rate_appointment("a1", rating=5, review="Great")
    service.appointment_repo.update.assert_awaited_once_with("a1", {"rating": 5, "review": "Great"})
    expected_new_rating = ((4.5 * 2) + 5) / 3
    service.doctor_repo.update_rating.assert_awaited_once_with("d1", round(expected_new_rating, 1), 3)

@pytest.mark.asyncio
async def test_rate_appointment_invalid_rating(service):
    with pytest.raises(ValueError, match="Rating must be between 1 and 5"):
        await service.rate_appointment("a1", rating=0, review="Bad")
    with pytest.raises(ValueError, match="Rating must be between 1 and 5"):
        await service.rate_appointment("a1", rating=6, review="Bad")

@pytest.mark.asyncio
async def test_rate_appointment_not_found(service):
    service.appointment_repo.get_by_id.return_value = None
    with pytest.raises(ValueError, match="Appointment not found"):
        await service.rate_appointment("a1", rating=5, review="Great")

# Cancel appointment
@pytest.mark.asyncio
async def test_cancel_appointment_success(service):
    appointment = make_appointment(id="a1", status=AppointmentStatus.CONFIRMED)
    service.appointment_repo.get_by_id.return_value = appointment
    cancelled = make_appointment(id="a1", status=AppointmentStatus.CANCELLED)
    service.appointment_repo.update_status.return_value = cancelled
    result = await service.cancel_appointment("a1")
    service.appointment_repo.update_status.assert_awaited_once_with("a1", AppointmentStatus.CANCELLED)
    assert result.status == AppointmentStatus.CANCELLED

@pytest.mark.asyncio
async def test_cancel_appointment_not_found(service):
    service.appointment_repo.get_by_id.return_value = None
    with pytest.raises(ValueError, match="Appointment not found"):
        await service.cancel_appointment("a1")

@pytest.mark.asyncio
async def test_cancel_appointment_invalid_status(service):
    appointment = make_appointment(id="a1", status=AppointmentStatus.COMPLETED)
    service.appointment_repo.get_by_id.return_value = appointment
    with pytest.raises(ValueError, match="Cannot cancel this appointment"):
        await service.cancel_appointment("a1")

# Get patient/doctor appointments
@pytest.mark.asyncio
async def test_get_patient_appointments(service):
    appointments = [make_appointment(id="a1"), make_appointment(id="a2")]
    service.appointment_repo.get_by_patient.return_value = appointments
    result = await service.get_patient_appointments("p1")
    service.appointment_repo.get_by_patient.assert_awaited_once_with("p1")
    assert len(result) == 2

@pytest.mark.asyncio
async def test_get_doctor_appointments_no_date(service):
    appointments = [make_appointment(id="a1")]
    service.appointment_repo.get_by_doctor.return_value = appointments
    result = await service.get_doctor_appointments("d1")
    service.appointment_repo.get_by_doctor.assert_awaited_once_with("d1", None)
    assert len(result) == 1

@pytest.mark.asyncio
async def test_get_doctor_appointments_with_date(service):
    appointments = [make_appointment(id="a1")]
    service.appointment_repo.get_by_doctor.return_value = appointments
    date = datetime.now(timezone.utc).date()
    result = await service.get_doctor_appointments("d1", date)
    service.appointment_repo.get_by_doctor.assert_awaited_once_with("d1", date)
    assert len(result) == 1

# get_patient_records
@pytest.mark.asyncio
async def test_get_patient_records(service):
    records = [make_medical_record(id="mr1"), make_medical_record(id="mr2")]
    service.record_repo.get_by_patient.return_value = records
    result = await service.get_patient_records("p1")
    service.record_repo.get_by_patient.assert_awaited_once_with("p1")
    assert len(result) == 2

# get_video_session
@pytest.mark.asyncio
async def test_get_video_session_found(service):
    session = make_video_session(id="vs1")
    service.session_repo.get_by_id.return_value = session
    result = await service.get_video_session("vs1")
    service.session_repo.get_by_id.assert_awaited_once_with("vs1")
    assert result.id == "vs1"

@pytest.mark.asyncio
async def test_get_video_session_not_found(service):
    service.session_repo.get_by_id.return_value = None
    result = await service.get_video_session("nonexistent")
    assert result is None

# get_patient
@pytest.mark.asyncio
async def test_get_patient_found(service):
    patient = make_patient(id="p1")
    service.patient_repo.get_by_id.return_value = patient
    result = await service.get_patient("p1")
    service.patient_repo.get_by_id.assert_awaited_once_with("p1")
    assert result.id == "p1"

@pytest.mark.asyncio
async def test_get_patient_not_found(service):
    service.patient_repo.get_by_id.return_value = None
    result = await service.get_patient("nonexistent")
    assert result is None

# update_patient_profile
@pytest.mark.asyncio
async def test_update_patient_profile_success(service):
    # Initially, maybe we need to return the patient after update
    updated_patient = make_patient(id="p1", full_name="John Updated", phone="999999")
    service.patient_repo.update.return_value = None
    service.patient_repo.get_by_id.return_value = updated_patient
    result = await service.update_patient_profile("p1", full_name="John Updated", phone="999999")
    service.patient_repo.update.assert_awaited_once_with("p1", {"full_name": "John Updated", "phone": "999999"})
    assert result.full_name == "John Updated"
    assert result.phone == "999999"
