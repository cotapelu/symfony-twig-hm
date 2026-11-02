"""
Unit tests for DoctorBooking repository layer
Target: Increase DoctorBooking coverage to >80%
"""

import pytest
from unittest.mock import AsyncMock, MagicMock
from datetime import datetime, timezone, timedelta
from modules.DoctorBooking.repository.repositories import (
    AppointmentRepository,
    TimeSlotRepository,
    DoctorRepository,
    HospitalRepository,
    PatientRepository
)
from modules.DoctorBooking.models.schemas import (
    Appointment,
    TimeSlot,
    Doctor,
    Hospital,
    PatientInfo,
    BookingStatus,
    PaymentStatus
)


@pytest.fixture
def mock_db():
    db = MagicMock()
    db.doctor_appointments = MagicMock()
    db.doctor_time_slots = MagicMock()
    db.doctors = MagicMock()
    db.hospitals = MagicMock()
    db.patients = MagicMock()
    return db


def make_async_cursor(docs):
    """Create a mock cursor that supports async iteration and optional sort chaining."""
    class AsyncCursor:
        def __init__(self, items):
            self.items = items[:]
            self.sort_called = False
            self.sort_args = None

        def sort(self, *args, **kwargs):
            self.sort_called = True
            self.sort_args = (args, kwargs)
            return self

        def __aiter__(self):
            return self

        async def __anext__(self):
            if not self.items:
                raise StopAsyncIteration
            return self.items.pop(0)

    return AsyncCursor(docs)


class TestAppointmentRepository:
    @pytest.fixture
    def repo(self, mock_db):
        return AppointmentRepository(mock_db)

    @pytest.mark.asyncio
    async def test_create(self, repo):
        appointment = Appointment(
            id="",
            patient_id="pat123",
            doctor_id="doc123",
            hospital_id="hosp123",
            department_id="dept123",
            time_slot_id="slot123",
            appointment_type="consultation",
            fee=100.0,
            final_fee=100.0,
            status=BookingStatus.PENDING,
            payment_status=PaymentStatus.PENDING,
            booking_date=datetime.now(timezone.utc)
        )
        repo.collection.insert_one = AsyncMock()
        result = await repo.create(appointment)
        assert result.id is not None
        assert result.patient_id == "pat123"
        repo.collection.insert_one.assert_called_once()

    @pytest.mark.asyncio
    async def test_get_by_id_found(self, repo):
        doctor = Doctor(
            id="doc123",
            hospital_id="hosp123",
            department_id="dept123",
            user_id="user123",
            full_name="Dr. Smith",
            specializations=["cardiology"],
            license_number="MD123",
            years_of_experience=10,
            consultation_fee=150.0
        )
        appointment = Appointment(
            id="apt123",
            patient_id="pat123",
            doctor_id="doc123",
            hospital_id="hosp123",
            department_id="dept123",
            time_slot_id="slot123",
            appointment_type="consultation",
            fee=100.0,
            final_fee=100.0,
            status=BookingStatus.CONFIRMED,
            booking_date=datetime.now(timezone.utc)
        )
        data = appointment.dict()
        repo.collection.find_one = AsyncMock(return_value=data)
        result = await repo.get_by_id("apt123")
        assert result is not None
        assert result.id == "apt123"

    @pytest.mark.asyncio
    async def test_get_by_id_not_found(self, repo):
        repo.collection.find_one = AsyncMock(return_value=None)
        result = await repo.get_by_id("missing")
        assert result is None

    @pytest.mark.asyncio
    async def test_get_by_patient(self, repo):
        appointment = Appointment(
            id="1",
            patient_id="pat123",
            doctor_id="doc123",
            hospital_id="hosp123",
            department_id="dept123",
            time_slot_id="slot123",
            appointment_type="consultation",
            fee=100.0,
            final_fee=100.0,
            status=BookingStatus.PENDING,
            booking_date=datetime.now(timezone.utc)
        )
        docs = [appointment.dict()]
        repo.collection.find = MagicMock(return_value=make_async_cursor(docs.copy()))
        result = await repo.get_by_patient("pat123")
        assert len(result) == 1

    @pytest.mark.asyncio
    async def test_get_by_doctor_without_date(self, repo):
        appointment = Appointment(
            id="doc123",
            patient_id="pat123",
            doctor_id="doc123",
            hospital_id="hosp123",
            department_id="dept123",
            time_slot_id="slot123",
            appointment_type="consultation",
            fee=100.0,
            final_fee=100.0,
            status=BookingStatus.PENDING,
            booking_date=datetime.now(timezone.utc)
        )
        docs = [appointment.dict()]
        repo.collection.find = MagicMock(return_value=make_async_cursor(docs.copy()))
        result = await repo.get_by_doctor("doc123")
        assert len(result) == 1

    @pytest.mark.asyncio
    async def test_get_by_doctor_with_date(self, repo):
        date = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
        appointment = Appointment(
            id="doc123",
            patient_id="pat123",
            doctor_id="doc123",
            hospital_id="hosp123",
            department_id="dept123",
            time_slot_id="slot123",
            appointment_type="consultation",
            fee=100.0,
            final_fee=100.0,
            status=BookingStatus.PENDING,
            booking_date=date
        )
        docs = [appointment.dict()]
        repo.collection.find = MagicMock(return_value=make_async_cursor(docs.copy()))
        result = await repo.get_by_doctor("doc123", date)
        repo.collection.find.assert_called_once()
        query_arg = repo.collection.find.call_args[0][0]
        assert "booking_date" in query_arg

    @pytest.mark.asyncio
    async def test_update_status(self, repo):
        appointment = Appointment(
            id="apt",
            patient_id="pat123",
            doctor_id="doc123",
            hospital_id="hosp123",
            department_id="dept123",
            time_slot_id="slot123",
            appointment_type="consultation",
            fee=100.0,
            final_fee=100.0,
            status=BookingStatus.CONFIRMED,
            booking_date=datetime.now(timezone.utc)
        )
        repo.collection.update_one = AsyncMock(return_value=MagicMock(modified_count=1))
        repo.get_by_id = AsyncMock(return_value=appointment)
        result = await repo.update_status("apt", BookingStatus.CONFIRMED)
        assert result is not None

    @pytest.mark.asyncio
    async def test_update_payment(self, repo):
        appointment = Appointment(
            id="apt",
            patient_id="pat123",
            doctor_id="doc123",
            hospital_id="hosp123",
            department_id="dept123",
            time_slot_id="slot123",
            appointment_type="consultation",
            fee=100.0,
            final_fee=100.0,
            status=BookingStatus.PENDING,
            payment_status=PaymentStatus.PAID,
            booking_date=datetime.now(timezone.utc)
        )
        repo.collection.update_one = AsyncMock(return_value=MagicMock(modified_count=1))
        repo.get_by_id = AsyncMock(return_value=appointment)
        result = await repo.update_payment("apt", PaymentStatus.PAID, "txn123")
        assert result is not None

    @pytest.mark.asyncio
    async def test_cancel(self, repo):
        appointment = Appointment(
            id="apt",
            patient_id="pat123",
            doctor_id="doc123",
            hospital_id="hosp123",
            department_id="dept123",
            time_slot_id="slot123",
            appointment_type="consultation",
            fee=100.0,
            final_fee=100.0,
            status=BookingStatus.CANCELLED,
            booking_date=datetime.now(timezone.utc)
        )
        repo.collection.update_one = AsyncMock(return_value=MagicMock(modified_count=1))
        repo.get_by_id = AsyncMock(return_value=appointment)
        result = await repo.cancel("apt")
        assert result is not None


class TestTimeSlotRepository:
    @pytest.fixture
    def repo(self, mock_db):
        return TimeSlotRepository(mock_db)

    @pytest.mark.asyncio
    async def test_create(self, repo):
        slot = TimeSlot(
            id="",
            doctor_id="doc123",
            date=datetime.now(timezone.utc),
            start_time="08:00",
            end_time="08:30",
            is_available=True,
            fee=100.0
        )
        repo.collection.insert_one = AsyncMock()
        result = await repo.create(slot)
        assert result.id is not None
        repo.collection.insert_one.assert_called_once()

    @pytest.mark.asyncio
    async def test_bulk_create(self, repo):
        slots = [
            TimeSlot(id="", doctor_id="doc123", date=datetime.now(timezone.utc), start_time="08:00", end_time="08:30", is_available=True, fee=100.0),
            TimeSlot(id="", doctor_id="doc123", date=datetime.now(timezone.utc), start_time="08:30", end_time="09:00", is_available=True, fee=100.0)
        ]
        repo.collection.insert_one = AsyncMock()
        result = await repo.bulk_create(slots)
        assert len(result) == 2
        assert repo.collection.insert_one.call_count == 2

    @pytest.mark.asyncio
    async def test_get_by_id_found(self, repo):
        slot = TimeSlot(
            id="slot123",
            doctor_id="doc123",
            date=datetime.now(timezone.utc),
            start_time="08:00",
            end_time="08:30",
            is_available=True,
            fee=100.0
        )
        data = slot.dict()
        repo.collection.find_one = AsyncMock(return_value=data)
        result = await repo.get_by_id("slot123")
        assert result is not None
        assert result.id == "slot123"

    @pytest.mark.asyncio
    async def test_get_by_id_not_found(self, repo):
        repo.collection.find_one = AsyncMock(return_value=None)
        result = await repo.get_by_id("missing")
        assert result is None

    @pytest.mark.asyncio
    async def test_get_available(self, repo):
        date = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
        slot = TimeSlot(
            id="slot123",
            doctor_id="doc123",
            date=date,
            start_time="08:00",
            end_time="08:30",
            is_available=True,
            fee=100.0
        )
        docs = [slot.dict()]
        repo.collection.find = MagicMock(return_value=make_async_cursor(docs.copy()))
        result = await repo.get_available("doc123", date)
        assert len(result) == 1
        repo.collection.find.assert_called_once()
        query_arg = repo.collection.find.call_args[0][0]
        assert "doctor_id" in query_arg
        assert "is_available" in query_arg
        assert "$gte" in query_arg["date"]
        assert "$lt" in query_arg["date"]

    @pytest.mark.asyncio
    async def test_book(self, repo):
        repo.collection.update_one = AsyncMock(return_value=MagicMock(modified_count=1))
        result = await repo.book("slot123")
        assert result is True
        repo.collection.update_one.assert_called_once()

    @pytest.mark.asyncio
    async def test_release(self, repo):
        repo.collection.update_one = AsyncMock(return_value=MagicMock(modified_count=1))
        result = await repo.release("slot123")
        assert result is True
        repo.collection.update_one.assert_called_once()


class TestDoctorRepository:
    @pytest.fixture
    def repo(self, mock_db):
        return DoctorRepository(mock_db)

    @pytest.mark.asyncio
    async def test_create(self, repo):
        doctor = Doctor(
            id="",
            hospital_id="hosp123",
            department_id="dept123",
            user_id="user123",
            full_name="Dr. Smith",
            specializations=["cardiology"],
            license_number="MD123",
            years_of_experience=10,
            consultation_fee=150.0,
            is_available=True
        )
        repo.collection.insert_one = AsyncMock()
        result = await repo.create(doctor)
        assert result.id is not None
        repo.collection.insert_one.assert_called_once()

    @pytest.mark.asyncio
    async def test_get_by_id_found(self, repo):
        doctor = Doctor(
            id="doc123",
            hospital_id="hosp123",
            department_id="dept123",
            user_id="user123",
            full_name="Dr. Smith",
            specializations=["cardiology"],
            license_number="MD123",
            years_of_experience=10,
            consultation_fee=150.0,
            is_available=True
        )
        data = doctor.dict()
        repo.collection.find_one = AsyncMock(return_value=data)
        result = await repo.get_by_id("doc123")
        assert result is not None
        assert result.id == "doc123"

    @pytest.mark.asyncio
    async def test_get_by_hospital(self, repo):
        doctor = Doctor(
            id="doc1",
            hospital_id="hosp123",
            department_id="dept123",
            user_id="user123",
            full_name="Dr. Smith",
            specializations=["cardiology"],
            license_number="MD123",
            years_of_experience=10,
            consultation_fee=150.0,
            is_available=True
        )
        docs = [doctor.dict()]
        repo.collection.find = MagicMock(return_value=make_async_cursor(docs.copy()))
        result = await repo.get_by_hospital("hosp123")
        assert len(result) == 1

    @pytest.mark.asyncio
    async def test_get_all(self, repo):
        doctor1 = Doctor(
            id="doc1",
            hospital_id="hosp123",
            department_id="dept123",
            user_id="user1",
            full_name="Dr. One",
            specializations=[],
            license_number="L1",
            years_of_experience=5,
            consultation_fee=100.0,
            is_available=True
        )
        doctor2 = Doctor(
            id="doc2",
            hospital_id="hosp123",
            department_id="dept123",
            user_id="user2",
            full_name="Dr. Two",
            specializations=[],
            license_number="L2",
            years_of_experience=8,
            consultation_fee=120.0,
            is_available=True
        )
        docs = [doctor1.dict(), doctor2.dict()]
        repo.collection.find = MagicMock(return_value=make_async_cursor(docs.copy()))
        result = await repo.get_all()
        assert len(result) == 2

    @pytest.mark.asyncio
    async def test_search(self, repo):
        doctor = Doctor(
            id="doc1",
            hospital_id="hosp123",
            department_id="dept123",
            user_id="user1",
            full_name="Cardiologist",
            specializations=["cardiology"],
            license_number="L1",
            years_of_experience=5,
            consultation_fee=100.0,
            is_available=True
        )
        docs = [doctor.dict()]
        repo.collection.find = MagicMock(return_value=make_async_cursor(docs.copy()))
        result = await repo.search("cardio", hospital_id="hosp123")
        assert len(result) == 1


class TestHospitalRepository:
    @pytest.fixture
    def repo(self, mock_db):
        return HospitalRepository(mock_db)

    @pytest.mark.asyncio
    async def test_create(self, repo):
        hospital = Hospital(
            id="",
            name="General Hospital",
            address="123 Main St",
            city="Hanoi",
            country="Vietnam",
            phone="123456789",
            is_active=True
        )
        repo.collection.insert_one = AsyncMock()
        result = await repo.create(hospital)
        assert result.id is not None
        repo.collection.insert_one.assert_called_once()

    @pytest.mark.asyncio
    async def test_get_by_id_found(self, repo):
        hospital = Hospital(
            id="hosp123",
            name="General Hospital",
            address="123 Main St",
            city="Hanoi",
            country="Vietnam",
            phone="123456789",
            is_active=True
        )
        data = hospital.dict()
        repo.collection.find_one = AsyncMock(return_value=data)
        result = await repo.get_by_id("hosp123")
        assert result is not None

    @pytest.mark.asyncio
    async def test_get_all(self, repo):
        h1 = Hospital(id="h1", name="Hospital 1", address="Addr1", city="City1", country="C1", phone="111", is_active=True)
        h2 = Hospital(id="h2", name="Hospital 2", address="Addr2", city="City2", country="C2", phone="222", is_active=True)
        docs = [h1.dict(), h2.dict()]
        repo.collection.find = MagicMock(return_value=make_async_cursor(docs.copy()))
        result = await repo.get_all()
        assert len(result) == 2

    @pytest.mark.asyncio
    async def test_search(self, repo):
        hospital = Hospital(
            id="h1",
            name="General Hospital",
            address="123 Main St",
            city="Hanoi",
            country="Vietnam",
            phone="123456789",
            is_active=True
        )
        docs = [hospital.dict()]
        repo.collection.find = MagicMock(return_value=make_async_cursor(docs.copy()))
        result = await repo.search("General")
        assert len(result) == 1


class TestPatientRepository:
    @pytest.fixture
    def repo(self, mock_db):
        return PatientRepository(mock_db)

    @pytest.mark.asyncio
    async def test_create(self, repo):
        patient = PatientInfo(
            id="",
            user_id="user123",
            full_name="John Doe",
            phone="123456789"
        )
        repo.collection.insert_one = AsyncMock()
        result = await repo.create(patient)
        assert result.id is not None
        repo.collection.insert_one.assert_called_once()

    @pytest.mark.asyncio
    async def test_get_by_id_found(self, repo):
        patient = PatientInfo(
            id="pat123",
            user_id="user123",
            full_name="John",
            phone="123456789"
        )
        data = patient.dict()
        repo.collection.find_one = AsyncMock(return_value=data)
        result = await repo.get_by_id("pat123")
        assert result is not None

    @pytest.mark.asyncio
    async def test_get_by_user_id_found(self, repo):
        patient = PatientInfo(
            id="pat123",
            user_id="user123",
            full_name="John",
            phone="123456789"
        )
        data = patient.dict()
        repo.collection.find_one = AsyncMock(return_value=data)
        result = await repo.get_by_user_id("user123")
        assert result is not None
        repo.collection.find_one.assert_called_once_with({"user_id": "user123"})

    @pytest.mark.asyncio
    async def test_update(self, repo):
        repo.collection.update_one = AsyncMock()
        patient = PatientInfo(
            id="pat123",
            user_id="user123",
            full_name="John",
            phone="123456789"
        )
        repo.get_by_id = AsyncMock(return_value=patient)
        result = await repo.update("pat123", {"full_name": "Jane"})
        assert result is not None
        repo.collection.update_one.assert_called_once()
