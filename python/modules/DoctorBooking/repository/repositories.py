from datetime import datetime, timedelta, timezone
from typing import Optional, List
import uuid
from models.schemas import Appointment, TimeSlot, Doctor, PatientInfo, Hospital, Department, BookingStatus, PaymentStatus

class AppointmentRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.doctor_appointments

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
        cursor = self.collection.find({"patient_id": patient_id}).sort("booking_date", -1)
        appointments = []
        async for doc in cursor:
            appointments.append(Appointment(**doc))
        return appointments

    async def get_by_doctor(self, doctor_id: str, date: Optional[datetime] = None) -> List[Appointment]:
        query = {"doctor_id": doctor_id}
        if date:
            start = date.replace(hour=0, minute=0, second=0, microsecond=0)
            end = start + timedelta(days=1)
            query["booking_date"] = {"$gte": start, "$lt": end}
        cursor = self.collection.find(query).sort("booking_date", 1)
        appointments = []
        async for doc in cursor:
            appointments.append(Appointment(**doc))
        return appointments

    async def update_status(self, id: str, status: BookingStatus) -> Optional[Appointment]:
        await self.collection.update_one({"id": id}, {"$set": {"status": status.value}})
        return await self.get_by_id(id)

    async def update_payment(self, id: str, payment_status: PaymentStatus, transaction_id: str) -> Optional[Appointment]:
        await self.collection.update_one({"id": id}, {"$set": {"payment_status": payment_status.value, "transaction_id": transaction_id}})
        return await self.get_by_id(id)

    async def cancel(self, id: str) -> Optional[Appointment]:
        await self.collection.update_one({"id": id}, {"$set": {"status": "cancelled"}})
        return await self.get_by_id(id)

class TimeSlotRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.doctor_time_slots

    async def create(self, slot: TimeSlot) -> TimeSlot:
        slot.id = str(uuid.uuid4())
        await self.collection.insert_one(slot.dict())
        return slot

    async def bulk_create(self, slots: List[TimeSlot]) -> List[TimeSlot]:
        created = []
        for slot in slots:
            slot.id = str(uuid.uuid4())
            await self.collection.insert_one(slot.dict())
            created.append(slot)
        return created

    async def get_by_id(self, id: str) -> Optional[TimeSlot]:
        data = await self.collection.find_one({"id": id})
        if data:
            return TimeSlot(**data)
        return None

    async def get_available(self, doctor_id: str, date: datetime) -> List[TimeSlot]:
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

    async def book(self, slot_id: str) -> bool:
        result = await self.collection.update_one(
            {"id": slot_id, "is_available": True},
            {"$set": {"is_available": False}}
        )
        return result.modified_count > 0

    async def release(self, slot_id: str) -> bool:
        result = await self.collection.update_one(
            {"id": slot_id},
            {"$set": {"is_available": True}}
        )
        return result.modified_count > 0

class DoctorRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.doctors

    async def create(self, doctor: Doctor) -> Doctor:
        doctor.id = str(uuid.uuid4())
        await self.collection.insert_one(doctor.dict())
        return doctor

    async def get_by_id(self, id: str) -> Optional[Doctor]:
        data = await self.collection.find_one({"id": id})
        if data:
            return Doctor(**data)
        return None

    async def get_by_hospital(self, hospital_id: str) -> List[Doctor]:
        cursor = self.collection.find({"hospital_id": hospital_id, "is_available": True})
        doctors = []
        async for doc in cursor:
            doctors.append(Doctor(**doc))
        return doctors

    async def get_by_department(self, department_id: str) -> List[Doctor]:
        cursor = self.collection.find({"department_id": department_id, "is_available": True})
        doctors = []
        async for doc in cursor:
            doctors.append(Doctor(**doc))
        return doctors

    async def search(self, query: str, hospital_id: Optional[str] = None) -> List[Doctor]:
        q = {"is_available": True}
        if hospital_id:
            q["hospital_id"] = hospital_id
        q["$or"] = [
            {"full_name": {"$regex": query, "$options": "i"}},
            {"specializations": {"$regex": query, "$options": "i"}}
        ]
        cursor = self.collection.find(q).sort("rating", -1)
        doctors = []
        async for doc in cursor:
            doctors.append(Doctor(**doc))
        return doctors

    async def get_all(self, specialization: Optional[str] = None) -> List[Doctor]:
        query = {"is_available": True}
        if specialization:
            query["specializations"] = specialization
        cursor = self.collection.find(query).sort("rating", -1)
        doctors = []
        async for doc in cursor:
            doctors.append(Doctor(**doc))
        return doctors

class HospitalRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.hospitals

    async def create(self, hospital: Hospital) -> Hospital:
        hospital.id = str(uuid.uuid4())
        await self.collection.insert_one(hospital.dict())
        return hospital

    async def get_by_id(self, id: str) -> Optional[Hospital]:
        data = await self.collection.find_one({"id": id})
        if data:
            return Hospital(**data)
        return None

    async def get_all(self) -> List[Hospital]:
        cursor = self.collection.find({"is_active": True}).sort("rating", -1)
        hospitals = []
        async for doc in cursor:
            hospitals.append(Hospital(**doc))
        return hospitals

    async def search(self, query: str) -> List[Hospital]:
        cursor = self.collection.find({
            "is_active": True,
            "name": {"$regex": query, "$options": "i"}
        }).sort("rating", -1)
        hospitals = []
        async for doc in cursor:
            hospitals.append(Hospital(**doc))
        return hospitals

class PatientRepository:
    def __init__(self, db):
        self.db = db
        self.collection = db.patients

    async def create(self, patient: PatientInfo) -> PatientInfo:
        patient.id = str(uuid.uuid4())
        await self.collection.insert_one(patient.dict())
        return patient

    async def get_by_id(self, id: str) -> Optional[PatientInfo]:
        data = await self.collection.find_one({"id": id})
        if data:
            return PatientInfo(**data)
        return None

    async def get_by_user_id(self, user_id: str) -> Optional[PatientInfo]:
        data = await self.collection.find_one({"user_id": user_id})
        if data:
            return PatientInfo(**data)
        return None

    async def update(self, id: str, data: dict) -> Optional[PatientInfo]:
        await self.collection.update_one({"id": id}, {"$set": data})
        return await self.get_by_id(id)
