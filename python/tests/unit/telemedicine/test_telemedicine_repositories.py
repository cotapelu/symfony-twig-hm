"""
Unit tests for Telemedicine Repository classes
Target: Increase coverage from 25.6% to >80%
"""

import pytest
from unittest.mock import AsyncMock, MagicMock
from datetime import datetime, timezone, timedelta
import uuid

from modules.Telemedicine.repository.repositories import (
    AppointmentRepository, TimeSlotRepository, PatientRepository,
    DoctorRepository, VideoSessionRepository
)
from modules.Telemedicine.models.schemas import (
    Appointment, TimeSlot, Patient, Doctor, VideoSession, AppointmentStatus, VideoSessionStatus, ConsultationType
)

# Helper
def make_async_cursor(data_list):
    """Create a mock cursor that yields items from data_list over async iteration."""
    async def async_gen():
        for item in data_list:
            yield item
    cursor = MagicMock()
    cursor.__aiter__ = MagicMock(return_value=async_gen())
    return cursor

# Fixtures
@pytest.fixture
def mock_db():
    db = MagicMock()
    db.appointments = MagicMock()
    db.appointments.find_one = AsyncMock()
    db.appointments.insert_one = AsyncMock()
    db.appointments.update_one = AsyncMock()
    db.appointments.find = MagicMock()

    db.time_slots = MagicMock()
    db.time_slots.find_one = AsyncMock()
    db.time_slots.insert_one = AsyncMock()
    db.time_slots.update_one = AsyncMock()
    db.time_slots.find = MagicMock()

    db.patients = MagicMock()
    db.patients.find_one = AsyncMock()
    db.patients.insert_one = AsyncMock()
    db.patients.update_one = AsyncMock()
    db.patients.find = MagicMock()

    db.doctors = MagicMock()
    db.doctors.find_one = AsyncMock()
    db.doctors.insert_one = AsyncMock()
    db.doctors.update_one = AsyncMock()
    db.doctors.find = MagicMock()

    db.video_sessions = MagicMock()
    db.video_sessions.find_one = AsyncMock()
    db.video_sessions.insert_one = AsyncMock()
    db.video_sessions.update_one = AsyncMock()
    db.video_sessions.find = MagicMock()
    return db

@pytest.fixture
def appointment_repo(mock_db):
    return AppointmentRepository(mock_db)

@pytest.fixture
def time_slot_repo(mock_db):
    return TimeSlotRepository(mock_db)

@pytest.fixture
def patient_repo(mock_db):
    return PatientRepository(mock_db)

@pytest.fixture
def doctor_repo(mock_db):
    return DoctorRepository(mock_db)

@pytest.fixture
def video_session_repo(mock_db):
    return VideoSessionRepository(mock_db)

@pytest.fixture
def now():
    return datetime.now(timezone.utc)

def full_appointment_data(now_ts, patient_id="pat123", doctor_id="doc123"):
    return {
        "id": "app123",
        "patient_id": patient_id,
        "doctor_id": doctor_id,
        "time_slot_id": "slot123",
        "appointment_type": ConsultationType.VIDEO.value,
        "status": AppointmentStatus.CONFIRMED.value,
        "scheduled_at": now_ts,
        "fee": 100.0,
        "final_fee": 100.0,
        "created_at": now_ts
    }

def full_patient_data(now_ts, user_id="user123"):
    return {
        "id": "patient123",
        "user_id": user_id,
        "full_name": "John Doe",
        "date_of_birth": datetime(1990,1,1, tzinfo=timezone.utc),
        "gender": "MALE",
        "created_at": now_ts
    }

def full_doctor_data(now_ts):
    return {
        "id": "doctor123",
        "user_id": "user456",
        "full_name": "Dr. Smith",
        "specializations": ["Cardiology"],
        "license_number": "LIC123",
        "years_of_experience": 10,
        "consultation_fee": 200.0,
        "is_available": True,
        "created_at": now_ts
    }

def full_video_session_data(now_ts):
    return {
        "id": "session123",
        "appointment_id": "app123",
        "room_id": "room_abc",
        "token": "token_xyz",
        "status": VideoSessionStatus.WAITING.value,
        "started_at": None,
        "ended_at": None,
        "duration_seconds": 0,
        "created_at": now_ts
    }

# AppointmentRepository Tests
@pytest.mark.asyncio
async def test_appointment_create(appointment_repo, mock_db, now):
    mock_db.appointments.insert_one.return_value = None
    appointment = Appointment(
        id="",
        patient_id="pat123",
        doctor_id="doc123",
        time_slot_id="slot123",
        appointment_type=ConsultationType.VIDEO,
        scheduled_at=now,
        fee=100.0,
        final_fee=100.0
    )

    result = await appointment_repo.create(appointment)
    assert result.id is not None
    mock_db.appointments.insert_one.assert_called_once()

@pytest.mark.asyncio
async def test_appointment_get_by_id_found(appointment_repo, mock_db, now):
    mock_db.appointments.find_one.return_value = full_appointment_data(now)
    result = await appointment_repo.get_by_id("app123")
    assert result is not None
    assert result.id == "app123"

@pytest.mark.asyncio
async def test_appointment_get_by_id_not_found(appointment_repo, mock_db):
    mock_db.appointments.find_one.return_value = None
    assert await appointment_repo.get_by_id("nonexistent") is None

@pytest.mark.asyncio
async def test_appointment_get_by_patient(appointment_repo, mock_db, now):
    base = now
    samples = [
        full_appointment_data(base, patient_id="pat123"),
        full_appointment_data(base + timedelta(days=1), patient_id="pat123")
    ]
    mock_cursor = make_async_cursor(samples)
    mock_db.appointments.find.return_value = mock_cursor
    mock_db.appointments.find.return_value.sort.return_value = mock_cursor

    result = await appointment_repo.get_by_patient("pat123")
    assert len(result) == 2

@pytest.mark.asyncio
async def test_appointment_get_by_doctor(appointment_repo, mock_db, now):
    date = datetime(2024,1,1, tzinfo=timezone.utc)
    data = full_appointment_data(now, doctor_id="doc123")
    data["scheduled_at"] = date
    mock_cursor = make_async_cursor([data])
    mock_db.appointments.find.return_value = mock_cursor
    mock_db.appointments.find.return_value.sort.return_value = mock_cursor

    result = await appointment_repo.get_by_doctor("doc123", date)
    assert len(result) == 1

@pytest.mark.asyncio
async def test_appointment_update_status(appointment_repo, mock_db, now):
    mock_db.appointments.update_one.return_value = None
    data = full_appointment_data(now)
    data["status"] = AppointmentStatus.CANCELLED.value
    mock_db.appointments.find_one.return_value = data

    result = await appointment_repo.update_status("app123", AppointmentStatus.CANCELLED)
    assert result is not None

@pytest.mark.asyncio
async def test_appointment_update(appointment_repo, mock_db, now):
    mock_db.appointments.update_one.return_value = None
    data = full_appointment_data(now)
    data["status"] = AppointmentStatus.CONFIRMED.value  # Keep valid
    mock_db.appointments.find_one.return_value = data

    result = await appointment_repo.update("app123", {"status": "updated"})
    assert result is not None

# TimeSlotRepository Tests
@pytest.mark.asyncio
async def test_time_slot_create(time_slot_repo, mock_db):
    mock_db.time_slots.insert_one.return_value = None
    slot = TimeSlot(
        id="",
        doctor_id="doc123",
        date=datetime.now(timezone.utc),
        start_time="09:00",
        end_time="10:00",
        is_available=True
    )
    result = await time_slot_repo.create(slot)
    assert result.id is not None
    mock_db.time_slots.insert_one.assert_called_once()

@pytest.mark.asyncio
async def test_time_slot_get_by_doctor(time_slot_repo, mock_db):
    date = datetime(2024,1,1, tzinfo=timezone.utc)
    sample = {
        "id": "slot1",
        "doctor_id": "doc123",
        "date": date,
        "start_time": "09:00",
        "end_time": "10:00",
        "is_available": True
    }
    mock_cursor = make_async_cursor([sample])
    mock_db.time_slots.find.return_value = mock_cursor

    result = await time_slot_repo.get_by_doctor("doc123", date)
    assert len(result) == 1

@pytest.mark.asyncio
async def test_time_slot_book_success(time_slot_repo, mock_db):
    mock_result = MagicMock()
    mock_result.modified_count = 1
    mock_db.time_slots.update_one.return_value = mock_result
    assert await time_slot_repo.book_slot("slot123") is True

@pytest.mark.asyncio
async def test_time_slot_book_failure(time_slot_repo, mock_db):
    mock_result = MagicMock()
    mock_result.modified_count = 0
    mock_db.time_slots.update_one.return_value = mock_result
    assert await time_slot_repo.book_slot("slot123") is False

# PatientRepository Tests
@pytest.mark.asyncio
async def test_patient_create(patient_repo, mock_db):
    mock_db.patients.insert_one.return_value = None
    patient = Patient(
        id="",
        user_id="user123",
        full_name="John Doe",
        date_of_birth=datetime(1990,1,1, tzinfo=timezone.utc),
        gender="MALE"
    )
    result = await patient_repo.create(patient)
    assert result.id is not None
    mock_db.patients.insert_one.assert_called_once()

@pytest.mark.asyncio
async def test_patient_get_by_id(patient_repo, mock_db, now):
    mock_db.patients.find_one.return_value = full_patient_data(now)
    result = await patient_repo.get_by_id("patient123")
    assert result is not None

@pytest.mark.asyncio
async def test_patient_get_by_user_id(patient_repo, mock_db, now):
    mock_db.patients.find_one.return_value = full_patient_data(now, user_id="user123")
    result = await patient_repo.get_by_user_id("user123")
    assert result is not None

@pytest.mark.asyncio
async def test_patient_update(patient_repo, mock_db, now):
    mock_db.patients.update_one.return_value = None
    data = full_patient_data(now)
    data["full_name"] = "Jane"
    mock_db.patients.find_one.return_value = data
    result = await patient_repo.update("patient123", {"full_name": "Jane"})
    assert result is not None

# DoctorRepository Tests
@pytest.mark.asyncio
async def test_doctor_create(doctor_repo, mock_db):
    mock_db.doctors.insert_one.return_value = None
    doctor = Doctor(
        id="",
        user_id="user456",
        full_name="Dr. Smith",
        specializations=["Cardiology"],
        license_number="LIC123",
        years_of_experience=10,
        consultation_fee=200.0
    )
    result = await doctor_repo.create(doctor)
    assert result.id is not None
    mock_db.doctors.insert_one.assert_called_once()

@pytest.mark.asyncio
async def test_doctor_get_by_id(doctor_repo, mock_db, now):
    mock_db.doctors.find_one.return_value = full_doctor_data(now)
    result = await doctor_repo.get_by_id("doctor123")
    assert result is not None

@pytest.mark.asyncio
async def test_doctor_get_all(doctor_repo, mock_db, now):
    sample_docs = [full_doctor_data(now), full_doctor_data(now)]
    mock_cursor = make_async_cursor(sample_docs)
    mock_db.doctors.find.return_value = mock_cursor
    mock_db.doctors.find.return_value.sort.return_value = mock_cursor
    result = await doctor_repo.get_all()
    assert len(result) == 2

@pytest.mark.asyncio
async def test_doctor_search(doctor_repo, mock_db, now):
    mock_cursor = make_async_cursor([full_doctor_data(now)])
    mock_db.doctors.find.return_value = mock_cursor
    mock_db.doctors.find.return_value.sort.return_value = mock_cursor
    result = await doctor_repo.search("cardio")
    assert len(result) == 1

@pytest.mark.asyncio
async def test_doctor_update_rating(doctor_repo, mock_db):
    mock_db.doctors.update_one.return_value = None
    await doctor_repo.update_rating("doctor123", 4.8, 20)
    mock_db.doctors.update_one.assert_called_once()

# VideoSessionRepository Tests
@pytest.mark.asyncio
async def test_video_session_create(video_session_repo, mock_db):
    mock_db.video_sessions.insert_one.return_value = None
    session = VideoSession(id="", appointment_id="app123", room_id="room_abc", token="token_xyz")
    result = await video_session_repo.create(session)
    assert result.id is not None
    assert result.status == VideoSessionStatus.WAITING
    mock_db.video_sessions.insert_one.assert_called_once()

@pytest.mark.asyncio
async def test_video_session_get_by_id(video_session_repo, mock_db, now):
    mock_db.video_sessions.find_one.return_value = full_video_session_data(now)
    result = await video_session_repo.get_by_id("session123")
    assert result is not None

@pytest.mark.asyncio
async def test_video_session_get_by_appointment(video_session_repo, mock_db, now):
    mock_db.video_sessions.find_one.return_value = full_video_session_data(now)
    result = await video_session_repo.get_by_appointment("app123")
    assert result is not None

@pytest.mark.asyncio
async def test_video_session_start(video_session_repo, mock_db, now):
    mock_db.video_sessions.update_one.return_value = None
    data = full_video_session_data(now)
    data["status"] = VideoSessionStatus.ACTIVE.value
    data["started_at"] = now
    mock_db.video_sessions.find_one.return_value = data
    result = await video_session_repo.start_session("session123")
    assert result.status == VideoSessionStatus.ACTIVE

@pytest.mark.asyncio
async def test_video_session_end(video_session_repo, mock_db, now):
    mock_db.video_sessions.update_one.return_value = None
    data = full_video_session_data(now)
    data["status"] = VideoSessionStatus.ENDED.value
    data["started_at"] = now - timedelta(hours=1)
    data["ended_at"] = now
    data["duration_seconds"] = 3600
    mock_db.video_sessions.find_one.return_value = data
    result = await video_session_repo.end_session("session123", 3600)
    assert result.status == VideoSessionStatus.ENDED
    assert result.duration_seconds == 3600
