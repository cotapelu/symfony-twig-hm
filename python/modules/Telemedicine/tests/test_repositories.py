import pytest
from unittest.mock import AsyncMock, MagicMock, ANY
from datetime import datetime, timezone, timedelta
import uuid

from modules.Telemedicine.repository.repositories import (
    AppointmentRepository,
    TimeSlotRepository,
    PatientRepository,
    DoctorRepository,
    VideoSessionRepository,
    MedicalRecordRepository
)
from modules.Telemedicine.models.schemas import (
    Appointment, TimeSlot, Patient, Doctor, VideoSession, MedicalRecord,
    AppointmentStatus, ConsultationType, VideoSessionStatus
)

# Helper async cursor mock
class MockAsyncCursor:
    def __init__(self, items):
        self.items = items
        self._iter = None
    def sort(self, *args, **kwargs):
        return self
    def __aiter__(self):
        self._iter = iter(self.items)
        return self
    async def __anext__(self):
        try:
            return next(self._iter)
        except StopIteration:
            raise StopAsyncIteration

@pytest.fixture
def mock_db():
    db = MagicMock()
    db.appointments = MagicMock()
    db.time_slots = MagicMock()
    db.patients = MagicMock()
    db.doctors = MagicMock()
    db.video_sessions = MagicMock()
    db.medical_records = MagicMock()
    return db

@pytest.fixture
def appointment_repo(mock_db):
    return AppointmentRepository(mock_db)

@pytest.fixture
def slot_repo(mock_db):
    return TimeSlotRepository(mock_db)

@pytest.fixture
def patient_repo(mock_db):
    return PatientRepository(mock_db)

@pytest.fixture
def doctor_repo(mock_db):
    return DoctorRepository(mock_db)

@pytest.fixture
def session_repo(mock_db):
    return VideoSessionRepository(mock_db)

@pytest.fixture
def record_repo(mock_db):
    return MedicalRecordRepository(mock_db)

# --- AppointmentRepository ---
@pytest.mark.asyncio
async def test_appointment_create(appointment_repo):
    appointment = Appointment(
        id="",
        patient_id="p1",
        doctor_id="d1",
        time_slot_id="ts1",
        appointment_type=ConsultationType.VIDEO,
        chief_complaint="test",
        symptoms=[],
        fee=0,
        final_fee=0,
        scheduled_at=datetime.now(timezone.utc),
        status=AppointmentStatus.PENDING
    )
    appointment_repo.collection.insert_one = AsyncMock()
    result = await appointment_repo.create(appointment)
    assert result.id is not None
    assert result.created_at is not None
    appointment_repo.collection.insert_one.assert_awaited_once()

@pytest.mark.asyncio
async def test_appointment_get_by_id_found(appointment_repo):
    now = datetime.now(timezone.utc)
    data = {
        "id": "a1",
        "patient_id": "p1",
        "doctor_id": "d1",
        "time_slot_id": "ts1",
        "appointment_type": "video",
        "chief_complaint": "test",
        "symptoms": [],
        "fee": 0,
        "final_fee": 0,
        "scheduled_at": now,
        "status": "pending",
        "created_at": now
    }
    appointment_repo.collection.find_one = AsyncMock(return_value=data)
    result = await appointment_repo.get_by_id("a1")
    assert result is not None
    assert result.id == "a1"

@pytest.mark.asyncio
async def test_appointment_get_by_id_not_found(appointment_repo):
    appointment_repo.collection.find_one = AsyncMock(return_value=None)
    result = await appointment_repo.get_by_id("nonexistent")
    assert result is None

@pytest.mark.asyncio
async def test_appointment_get_by_patient_empty(appointment_repo):
    appointment_repo.collection.find.return_value = MockAsyncCursor([])
    result = await appointment_repo.get_by_patient("p1")
    assert result == []

@pytest.mark.asyncio
async def test_appointment_get_by_patient_multiple(appointment_repo):
    now = datetime.now(timezone.utc)
    data1 = {"id": "a1", "patient_id": "p1", "doctor_id": "d1", "time_slot_id": "ts1", "appointment_type": "video", "scheduled_at": now, "status": "pending", "created_at": now, "fee": 0, "final_fee": 0}
    data2 = {"id": "a2", "patient_id": "p1", "doctor_id": "d1", "time_slot_id": "ts1", "appointment_type": "video", "scheduled_at": now, "status": "confirmed", "created_at": now, "fee": 0, "final_fee": 0}
    appointment_repo.collection.find.return_value = MockAsyncCursor([data1, data2])
    result = await appointment_repo.get_by_patient("p1")
    assert len(result) == 2

@pytest.mark.asyncio
async def test_appointment_get_by_doctor_no_date(appointment_repo):
    doctor_id = "d1"
    appointment_repo.collection.find.return_value = MockAsyncCursor([])
    result = await appointment_repo.get_by_doctor(doctor_id, date=None)
    assert result == []
    query_sent = appointment_repo.collection.find.call_args[0][0]
    assert query_sent == {"doctor_id": doctor_id}

@pytest.mark.asyncio
async def test_appointment_get_by_doctor_with_date(appointment_repo):
    doctor_id = "d1"
    date = datetime(2025, 1, 1, tzinfo=timezone.utc)
    start = date.replace(hour=0, minute=0, second=0, microsecond=0)
    end = start + timedelta(days=1)
    appointment_repo.collection.find.return_value = MockAsyncCursor([])
    result = await appointment_repo.get_by_doctor(doctor_id, date=date)
    assert result == []
    query_sent = appointment_repo.collection.find.call_args[0][0]
    assert query_sent["doctor_id"] == doctor_id
    assert query_sent["scheduled_at"] == {"$gte": start, "$lt": end}

@pytest.mark.asyncio
async def test_appointment_update_status(appointment_repo):
    appointment_repo.collection.update_one = AsyncMock()
    now = datetime.now(timezone.utc)
    appointment_repo.collection.find_one = AsyncMock(return_value={"id": "a1", "patient_id": "p1", "doctor_id": "d1", "time_slot_id": "ts1", "appointment_type": "video", "status": "confirmed", "created_at": now, "fee": 0, "final_fee": 0, "scheduled_at": now})
    result = await appointment_repo.update_status("a1", AppointmentStatus.CONFIRMED)
    appointment_repo.collection.update_one.assert_awaited_once_with(
        {"id": "a1"}, {"$set": {"status": "confirmed"}}
    )
    appointment_repo.collection.find_one.assert_awaited_once_with({"id": "a1"})
    assert result.id == "a1"

@pytest.mark.asyncio
async def test_appointment_update(appointment_repo):
    appointment_repo.collection.update_one = AsyncMock()
    now = datetime.now(timezone.utc)
    appointment_repo.collection.find_one = AsyncMock(return_value={"id": "a1", "fee": 200, "created_at": now, "final_fee": 200, "patient_id": "p1", "doctor_id": "d1", "time_slot_id": "ts1", "appointment_type": "video", "scheduled_at": now})
    result = await appointment_repo.update("a1", {"fee": 200})
    appointment_repo.collection.update_one.assert_awaited_once_with({"id": "a1"}, {"$set": {"fee": 200}})
    assert result.id == "a1"

# --- TimeSlotRepository ---
@pytest.mark.asyncio
async def test_timeslot_create(slot_repo):
    slot = TimeSlot(id="", doctor_id="d1", date=datetime.now(timezone.utc), start_time="08:00", end_time="08:30", is_available=True)
    slot_repo.collection.insert_one = AsyncMock()
    result = await slot_repo.create(slot)
    assert result.id is not None
    slot_repo.collection.insert_one.assert_awaited_once()

@pytest.mark.asyncio
async def test_timeslot_get_by_doctor_empty(slot_repo):
    slot_repo.collection.find.return_value = MockAsyncCursor([])
    date = datetime.now(timezone.utc)
    result = await slot_repo.get_by_doctor("d1", date)
    assert result == []

@pytest.mark.asyncio
async def test_timeslot_get_available_slots(slot_repo):
    slot_repo.collection.find.return_value = MockAsyncCursor([])
    date = datetime.now(timezone.utc)
    result = await slot_repo.get_available_slots("d1", date)
    assert result == []
    query_sent = slot_repo.collection.find.call_args[0][0]
    assert query_sent["doctor_id"] == "d1"
    assert query_sent["is_available"] is True
    assert "$gte" in query_sent["date"]
    assert "$lt" in query_sent["date"]

@pytest.mark.asyncio
async def test_slot_book_success(slot_repo):
    slot_id = "ts1"
    slot_repo.collection.update_one = AsyncMock(return_value=MagicMock(modified_count=1))
    result = await slot_repo.book_slot(slot_id)
    assert result is True
    slot_repo.collection.update_one.assert_awaited_once_with(
        {"id": slot_id, "is_available": True},
        {"$set": {"is_available": False}}
    )

@pytest.mark.asyncio
async def test_slot_book_failure(slot_repo):
    slot_id = "ts1"
    slot_repo.collection.update_one = AsyncMock(return_value=MagicMock(modified_count=0))
    result = await slot_repo.book_slot(slot_id)
    assert result is False

# --- PatientRepository ---
@pytest.mark.asyncio
async def test_patient_create(patient_repo):
    patient = Patient(id="", user_id="u1", full_name="John", gender="M", date_of_birth="1990-01-01")
    patient_repo.collection.insert_one = AsyncMock()
    result = await patient_repo.create(patient)
    assert result.id is not None
    assert result.created_at is not None

@pytest.mark.asyncio
async def test_patient_get_by_id_found(patient_repo):
    data = {"id": "p1", "user_id": "u1", "full_name": "John", "created_at": datetime.now(timezone.utc)}
    patient_repo.collection.find_one = AsyncMock(return_value=data)
    result = await patient_repo.get_by_id("p1")
    assert result is not None
    assert result.id == "p1"

@pytest.mark.asyncio
async def test_patient_get_by_id_not_found(patient_repo):
    patient_repo.collection.find_one = AsyncMock(return_value=None)
    result = await patient_repo.get_by_id("nonexistent")
    assert result is None

@pytest.mark.asyncio
async def test_patient_get_by_user_id_found(patient_repo):
    data = {"id": "p1", "user_id": "u1", "full_name": "John", "created_at": datetime.now(timezone.utc)}
    patient_repo.collection.find_one = AsyncMock(return_value=data)
    result = await patient_repo.get_by_user_id("u1")
    assert result is not None
    assert result.user_id == "u1"

@pytest.mark.asyncio
async def test_patient_get_by_user_id_not_found(patient_repo):
    patient_repo.collection.find_one = AsyncMock(return_value=None)
    result = await patient_repo.get_by_user_id("nonexistent")
    assert result is None

@pytest.mark.asyncio
async def test_patient_update(patient_repo):
    patient_repo.collection.update_one = AsyncMock()
    now = datetime.now(timezone.utc)
    patient_repo.collection.find_one = AsyncMock(return_value={"id": "p1", "full_name": "Updated", "created_at": now, "user_id": "u1", "gender": "M", "date_of_birth": "1990-01-01"})
    result = await patient_repo.update("p1", {"full_name": "Updated"})
    patient_repo.collection.update_one.assert_awaited_once_with({"id": "p1"}, {"$set": {"full_name": "Updated"}})
    assert result.id == "p1"

# --- DoctorRepository ---
@pytest.mark.asyncio
async def test_doctor_create(doctor_repo):
    doctor = Doctor(
        id="",
        user_id="u2",
        full_name="Dr. Smith",
        specializations=["Cardiology"],
        license_number="LIC123",
        years_of_experience=5,
        consultation_fee=100.0,
        is_available=True
    )
    doctor_repo.collection.insert_one = AsyncMock()
    result = await doctor_repo.create(doctor)
    assert result.id is not None

@pytest.mark.asyncio
async def test_doctor_get_by_id_found(doctor_repo):
    data = {"id": "d1", "user_id": "u2", "full_name": "Dr. Smith", "specializations": ["Cardiology"], "license_number": "LIC123", "years_of_experience": 5, "consultation_fee": 100.0, "is_available": True, "created_at": datetime.now(timezone.utc)}
    doctor_repo.collection.find_one = AsyncMock(return_value=data)
    result = await doctor_repo.get_by_id("d1")
    assert result is not None

@pytest.mark.asyncio
async def test_doctor_get_all_no_filter(doctor_repo):
    doctor_repo.collection.find.return_value = MockAsyncCursor([{"id": "d1", "user_id": "u2", "full_name": "Dr. Smith", "specializations": ["Cardiology"], "license_number": "LIC123", "years_of_experience": 5, "consultation_fee": 100.0, "is_available": True, "created_at": datetime.now(timezone.utc)}, {"id": "d2", "user_id": "u2", "full_name": "Dr. Jones", "specializations": ["Neurology"], "license_number": "LIC456", "years_of_experience": 3, "consultation_fee": 150.0, "is_available": True, "created_at": datetime.now(timezone.utc)}])
    result = await doctor_repo.get_all()
    assert len(result) == 2
    query_sent = doctor_repo.collection.find.call_args[0][0]
    assert query_sent == {"is_available": True}

@pytest.mark.asyncio
async def test_doctor_get_all_with_specialization(doctor_repo):
    doctor_repo.collection.find.return_value = MockAsyncCursor([{"id": "d1", "user_id": "u2", "full_name": "Dr. Smith", "specializations": ["Cardiology"], "license_number": "LIC123", "years_of_experience": 5, "consultation_fee": 100.0, "is_available": True, "created_at": datetime.now(timezone.utc)}])
    result = await doctor_repo.get_all(specialization="Cardiology")
    assert len(result) == 1
    query_sent = doctor_repo.collection.find.call_args[0][0]
    assert query_sent["is_available"] is True
    assert query_sent["specializations"] == "Cardiology"

@pytest.mark.asyncio
async def test_doctor_search(doctor_repo):
    doctor_repo.collection.find.return_value = MockAsyncCursor([{"id": "d1", "user_id": "u2", "full_name": "Dr. Smith", "specializations": ["Cardiology"], "license_number": "LIC123", "years_of_experience": 5, "consultation_fee": 100.0, "is_available": True, "created_at": datetime.now(timezone.utc)}])
    result = await doctor_repo.search("Smith")
    assert len(result) == 1
    query_sent = doctor_repo.collection.find.call_args[0][0]
    assert "$or" in query_sent
    # The query should combine is_available and $or; check presence
    assert "is_available" in query_sent

@pytest.mark.asyncio
async def test_doctor_update_rating(doctor_repo):
    doctor_repo.collection.update_one = AsyncMock()
    await doctor_repo.update_rating("d1", 4.5, 10)
    doctor_repo.collection.update_one.assert_awaited_once_with(
        {"id": "d1"},
        {"$set": {"rating": 4.5, "total_reviews": 10}}
    )

# --- VideoSessionRepository ---
@pytest.mark.asyncio
async def test_video_session_create(session_repo):
    session = VideoSession(
        id="",
        appointment_id="a1",
        room_id="",
        token="",
        started_at=None,
        ended_at=None
    )
    session_repo.collection.insert_one = AsyncMock()
    result = await session_repo.create(session)
    assert result.id is not None
    assert result.room_id is not None
    assert result.token is not None
    assert result.created_at is not None

@pytest.mark.asyncio
async def test_video_session_get_by_id_found(session_repo):
    data = {"id": "vs1", "appointment_id": "a1", "room_id": "room1", "token": "token1", "created_at": datetime.now(timezone.utc)}
    session_repo.collection.find_one = AsyncMock(return_value=data)
    result = await session_repo.get_by_id("vs1")
    assert result is not None

@pytest.mark.asyncio
async def test_video_session_get_by_appointment_found(session_repo):
    data = {"id": "vs1", "appointment_id": "a1", "room_id": "room1", "token": "token1", "created_at": datetime.now(timezone.utc)}
    session_repo.collection.find_one = AsyncMock(return_value=data)
    result = await session_repo.get_by_appointment("a1")
    assert result is not None

@pytest.mark.asyncio
async def test_video_session_start(session_repo):
    session_repo.collection.update_one = AsyncMock()
    now = datetime.now(timezone.utc)
    session_repo.collection.find_one = AsyncMock(return_value={"id": "vs1", "appointment_id": "a1", "room_id": "room1", "token": "token1", "status": "waiting", "started_at": None, "created_at": now})
    result = await session_repo.start_session("vs1")
    session_repo.collection.update_one.assert_awaited_once_with(
        {"id": "vs1"},
        {"$set": {"status": "active", "started_at": ANY}}
    )
    assert result is not None

@pytest.mark.asyncio
async def test_video_session_end(session_repo):
    session_repo.collection.update_one = AsyncMock()
    now = datetime.now(timezone.utc)
    session_repo.collection.find_one = AsyncMock(return_value={"id": "vs1", "appointment_id": "a1", "room_id": "room1", "token": "token1", "status": "ended", "ended_at": None, "duration_seconds": 60, "created_at": now})
    result = await session_repo.end_session("vs1", 60)
    session_repo.collection.update_one.assert_awaited_once_with(
        {"id": "vs1"},
        {"$set": {"status": "ended", "ended_at": ANY, "duration_seconds": 60}}
    )
    assert result is not None

# --- MedicalRecordRepository ---
@pytest.mark.asyncio
async def test_medical_record_create(record_repo):
    record = MedicalRecord(
        id="",
        patient_id="p1",
        appointment_id="a1",
        doctor_id="d1",
        diagnosis="Flu",
        treatment_plan="Rest",
        prescription="None"
    )
    record_repo.collection.insert_one = AsyncMock()
    result = await record_repo.create(record)
    assert result.id is not None
    assert result.created_at is not None

@pytest.mark.asyncio
async def test_medical_record_get_by_patient_empty(record_repo):
    record_repo.collection.find.return_value = MockAsyncCursor([])
    result = await record_repo.get_by_patient("p1")
    assert result == []

@pytest.mark.asyncio
async def test_medical_record_get_by_patient_multiple(record_repo):
    record_repo.collection.find.return_value = MockAsyncCursor([{"id": "mr1", "patient_id": "p1", "appointment_id": "a1", "doctor_id": "d1", "diagnosis": "Flu", "created_at": datetime.now(timezone.utc)}, {"id": "mr2", "patient_id": "p1", "appointment_id": "a1", "doctor_id": "d1", "diagnosis": "Cold", "created_at": datetime.now(timezone.utc)}])
    result = await record_repo.get_by_patient("p1")
    assert len(result) == 2

@pytest.mark.asyncio
async def test_medical_record_get_by_id_found(record_repo):
    data = {"id": "mr1", "patient_id": "p1", "appointment_id": "a1", "doctor_id": "d1", "diagnosis": "Flu", "created_at": datetime.now(timezone.utc), "fee": 0, "final_fee": 0}
    record_repo.collection.find_one = AsyncMock(return_value=data)
    result = await record_repo.get_by_id("mr1")
    assert result is not None

@pytest.mark.asyncio
async def test_medical_record_get_by_id_not_found(record_repo):
    record_repo.collection.find_one = AsyncMock(return_value=None)
    result = await record_repo.get_by_id("nonexistent")
    assert result is None