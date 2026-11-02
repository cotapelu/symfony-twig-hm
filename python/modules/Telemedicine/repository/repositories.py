from datetime import datetime, timedelta, timezone
from typing import Optional, List
import uuid
from modules.Telemedicine.models.schemas import Appointment, TimeSlot, Patient, Doctor, VideoSession, MedicalRecord, ConsultationType, AppointmentStatus

class AppointmentRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.appointments

    async def create(self, appointment: Appointment) -> Appointment:
        appointment.id = str(uuid.uuid4())
        appointment.created_at = datetime.now(timezone.utc)
        await self.collection.insert_one(appointment.dict())
        return appointment

    async def get_by_id(self, id: str) -> Optional[Appointment]:
        data = await self.collection.find_one({"id": id})
        if data:
            return Appointment(**data)
        return None

    async def get_by_patient(self, patient_id: str) -> List[Appointment]:
        cursor = self.collection.find({"patient_id": patient_id}).sort("created_at", -1)
        appointments = []
        async for doc in cursor:
            appointments.append(Appointment(**doc))
        return appointments

    async def get_by_doctor(self, doctor_id: str, date: Optional[datetime] = None) -> List[Appointment]:
        query = {"doctor_id": doctor_id}
        if date:
            start = date.replace(hour=0, minute=0, second=0, microsecond=0)
            end = start + timedelta(days=1)
            query["scheduled_at"] = {"$gte": start, "$lt": end}
        cursor = self.collection.find(query).sort("scheduled_at", 1)
        appointments = []
        async for doc in cursor:
            appointments.append(Appointment(**doc))
        return appointments

    async def update_status(self, id: str, status: AppointmentStatus) -> Optional[Appointment]:
        await self.collection.update_one({"id": id}, {"$set": {"status": status.value}})
        return await self.get_by_id(id)

    async def update(self, id: str, data: dict) -> Optional[Appointment]:
        await self.collection.update_one({"id": id}, {"$set": data})
        return await self.get_by_id(id)

class TimeSlotRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.time_slots

    async def create(self, slot: TimeSlot) -> TimeSlot:
        slot.id = str(uuid.uuid4())
        await self.collection.insert_one(slot.dict())
        return slot

    async def get_by_doctor(self, doctor_id: str, date: datetime) -> List[TimeSlot]:
        start = date.replace(hour=0, minute=0, second=0, microsecond=0)
        end = start + timedelta(days=1)
        cursor = self.collection.find({"doctor_id": doctor_id, "date": {"$gte": start, "$lt": end}, "is_available": True})
        slots = []
        async for doc in cursor:
            slots.append(TimeSlot(**doc))
        return slots

    async def get_available_slots(self, doctor_id: str, date: datetime) -> List[TimeSlot]:
        start = date.replace(hour=0, minute=0, second=0, microsecond=0)
        end = start + timedelta(days=1)
        cursor = self.collection.find({
            "doctor_id": doctor_id,
            "date": {"$gte": start, "$lt": end},
            "is_available": True
        })
        slots = []
        async for doc in cursor:
            slots.append(TimeSlot(**doc))
        return slots

    async def book_slot(self, slot_id: str) -> bool:
        result = await self.collection.update_one(
            {"id": slot_id, "is_available": True},
            {"$set": {"is_available": False}}
        )
        return result.modified_count > 0

class PatientRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.patients

    async def create(self, patient: Patient) -> Patient:
        patient.id = str(uuid.uuid4())
        patient.created_at = datetime.now(timezone.utc)
        await self.collection.insert_one(patient.dict())
        return patient

    async def get_by_id(self, id: str) -> Optional[Patient]:
        data = await self.collection.find_one({"id": id})
        if data:
            return Patient(**data)
        return None

    async def get_by_user_id(self, user_id: str) -> Optional[Patient]:
        data = await self.collection.find_one({"user_id": user_id})
        if data:
            return Patient(**data)
        return None

    async def update(self, id: str, data: dict) -> Optional[Patient]:
        await self.collection.update_one({"id": id}, {"$set": data})
        return await self.get_by_id(id)

class DoctorRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.doctors

    async def create(self, doctor: Doctor) -> Doctor:
        doctor.id = str(uuid.uuid4())
        doctor.created_at = datetime.now(timezone.utc)
        await self.collection.insert_one(doctor.dict())
        return doctor

    async def get_by_id(self, id: str) -> Optional[Doctor]:
        data = await self.collection.find_one({"id": id})
        if data:
            return Doctor(**data)
        return None

    async def get_all(self, specialization: Optional[str] = None) -> List[Doctor]:
        query = {"is_available": True}
        if specialization:
            query["specializations"] = specialization
        cursor = self.collection.find(query).sort("rating", -1)
        doctors = []
        async for doc in cursor:
            doctors.append(Doctor(**doc))
        return doctors

    async def search(self, query: str) -> List[Doctor]:
        cursor = self.collection.find({
            "is_available": True,
            "$or": [
                {"full_name": {"$regex": query, "$options": "i"}},
                {"specializations": {"$regex": query, "$options": "i"}}
            ]
        }).sort("rating", -1)
        doctors = []
        async for doc in cursor:
            doctors.append(Doctor(**doc))
        return doctors

    async def update_rating(self, id: str, rating: float, total_reviews: int) -> None:
        await self.collection.update_one(
            {"id": id},
            {"$set": {"rating": rating, "total_reviews": total_reviews}}
        )

class VideoSessionRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.video_sessions

    async def create(self, session: VideoSession) -> VideoSession:
        session.id = str(uuid.uuid4())
        session.room_id = f"room_{uuid.uuid4().hex[:12]}"
        session.token = f"token_{uuid.uuid4().hex}"
        session.created_at = datetime.now(timezone.utc)
        await self.collection.insert_one(session.dict())
        return session

    async def get_by_id(self, id: str) -> Optional[VideoSession]:
        data = await self.collection.find_one({"id": id})
        if data:
            return VideoSession(**data)
        return None

    async def get_by_appointment(self, appointment_id: str) -> Optional[VideoSession]:
        data = await self.collection.find_one({"appointment_id": appointment_id})
        if data:
            return VideoSession(**data)
        return None

    async def start_session(self, id: str) -> Optional[VideoSession]:
        await self.collection.update_one(
            {"id": id},
            {"$set": {"status": "active", "started_at": datetime.now(timezone.utc)}}
        )
        return await self.get_by_id(id)

    async def end_session(self, id: str, duration: int) -> Optional[VideoSession]:
        await self.collection.update_one(
            {"id": id},
            {"$set": {"status": "ended", "ended_at": datetime.now(timezone.utc), "duration_seconds": duration}}
        )
        return await self.get_by_id(id)

class MedicalRecordRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.medical_records

    async def create(self, record: MedicalRecord) -> MedicalRecord:
        record.id = str(uuid.uuid4())
        record.created_at = datetime.now(timezone.utc)
        await self.collection.insert_one(record.dict())
        return record

    async def get_by_patient(self, patient_id: str) -> List[MedicalRecord]:
        cursor = self.collection.find({"patient_id": patient_id}).sort("created_at", -1)
        records = []
        async for doc in cursor:
            records.append(MedicalRecord(**doc))
        return records

    async def get_by_id(self, id: str) -> Optional[MedicalRecord]:
        data = await self.collection.find_one({"id": id})
        if data:
            return MedicalRecord(**data)
        return None
