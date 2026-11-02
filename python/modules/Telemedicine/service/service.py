from datetime import datetime, timedelta, timezone
from typing import Optional, List
from modules.Telemedicine.models.schemas import Appointment, TimeSlot, Doctor, Patient, VideoSession, MedicalRecord, ConsultationType, AppointmentStatus, VideoSessionStatus
from modules.Telemedicine.repository.repositories import AppointmentRepository, TimeSlotRepository, DoctorRepository, PatientRepository, VideoSessionRepository, MedicalRecordRepository

class TelemedicineService:
    def __init__(self, db):
        self.db = db
        self.appointment_repo = AppointmentRepository(db)
        self.slot_repo = TimeSlotRepository(db)
        self.doctor_repo = DoctorRepository(db)
        self.patient_repo = PatientRepository(db)
        self.session_repo = VideoSessionRepository(db)
        self.record_repo = MedicalRecordRepository(db)

    async def register_patient(self, user_id: str, full_name: str, **kwargs) -> Patient:
        patient = Patient(id="", user_id=user_id, full_name=full_name, **kwargs)
        return await self.patient_repo.create(patient)

    async def register_doctor(self, user_id: str, full_name: str, specializations: List[str], license_number: str, 
                              years_of_experience: int, consultation_fee: float, **kwargs) -> Doctor:
        doctor = Doctor(
            id="", user_id=user_id, full_name=full_name, specializations=specializations,
            license_number=license_number, years_of_experience=years_of_experience,
            consultation_fee=consultation_fee, **kwargs
        )
        return await self.doctor_repo.create(doctor)

    async def get_doctors(self, specialization: Optional[str] = None) -> List[Doctor]:
        return await self.doctor_repo.get_all(specialization)

    async def search_doctors(self, query: str) -> List[Doctor]:
        return await self.doctor_repo.search(query)

    async def get_doctor_by_id(self, doctor_id: str) -> Optional[Doctor]:
        return await self.doctor_repo.get_by_id(doctor_id)

    async def get_available_slots(self, doctor_id: str, date: datetime) -> List[TimeSlot]:
        return await self.slot_repo.get_available_slots(doctor_id, date)

    async def create_time_slots(self, doctor_id: str, date: datetime, slot_duration_minutes: int = 30) -> List[TimeSlot]:
        slots = []
        start_hour, end_hour = 8, 17
        current = date.replace(hour=start_hour, minute=0, second=0, microsecond=0)
        end = date.replace(hour=end_hour, minute=0, second=0, microsecond=0)
        
        while current < end:
            slot = TimeSlot(
                id="", doctor_id=doctor_id,
                date=date,
                start_time=current.strftime("%H:%M"),
                end_time=(current + timedelta(minutes=slot_duration_minutes)).strftime("%H:%M"),
                is_available=True
            )
            slots.append(await self.slot_repo.create(slot))
            current += timedelta(minutes=slot_duration_minutes)
        
        return slots

    async def book_appointment(self, patient_id: str, doctor_id: str, time_slot_id: str, 
                               appointment_type: ConsultationType, chief_complaint: str, symptoms: List[str]) -> Appointment:
        slot = await self.slot_repo.get_available_slots(doctor_id, datetime.now(timezone.utc))
        valid_slot = next((s for s in slot if s.id == time_slot_id), None)
        if not valid_slot:
            raise ValueError("Time slot not available")
        
        doctor = await self.doctor_repo.get_by_id(doctor_id)
        if not doctor or not doctor.is_available:
            raise ValueError("Doctor not available")
        
        fee = doctor.consultation_fee
        appointment = Appointment(
            id="",
            patient_id=patient_id,
            doctor_id=doctor_id,
            time_slot_id=time_slot_id,
            appointment_type=appointment_type,
            chief_complaint=chief_complaint,
            symptoms=symptoms,
            fee=fee,
            final_fee=fee,
            scheduled_at=datetime.combine(valid_slot.date, datetime.strptime(valid_slot.start_time, "%H:%M").time())
        )
        
        await self.slot_repo.book_slot(time_slot_id)
        return await self.appointment_repo.create(appointment)

    async def confirm_appointment(self, appointment_id: str) -> Appointment:
        return await self.appointment_repo.update_status(appointment_id, AppointmentStatus.CONFIRMED)

    async def start_consultation(self, appointment_id: str) -> VideoSession:
        appointment = await self.appointment_repo.get_by_id(appointment_id)
        if not appointment:
            raise ValueError("Appointment not found")
        
        session = VideoSession(id="", appointment_id=appointment_id, room_id="", token="")
        session = await self.session_repo.create(session)
        
        await self.appointment_repo.update_status(appointment_id, AppointmentStatus.IN_PROGRESS)
        await self.appointment_repo.update(appointment_id, {"started_at": datetime.now(timezone.utc)})
        
        return session

    async def end_consultation(self, appointment_id: str, diagnosis: str, treatment_plan: str, 
                               prescription: str, notes: str = None) -> MedicalRecord:
        appointment = await self.appointment_repo.get_by_id(appointment_id)
        if not appointment:
            raise ValueError("Appointment not found")
        
        session = await self.session_repo.get_by_appointment(appointment_id)
        duration = 0
        if session and session.started_at:
            duration = int((datetime.now(timezone.utc) - session.started_at).total_seconds())
            await self.session_repo.end_session(session.id, duration)
        
        record = MedicalRecord(
            id="",
            patient_id=appointment.patient_id,
            appointment_id=appointment_id,
            doctor_id=appointment.doctor_id,
            diagnosis=diagnosis,
            treatment_plan=treatment_plan,
            prescription=prescription,
            notes=notes
        )
        record = await self.record_repo.create(record)
        
        await self.appointment_repo.update_status(appointment_id, AppointmentStatus.COMPLETED)
        await self.appointment_repo.update(appointment_id, {
            "ended_at": datetime.now(timezone.utc),
            "diagnosis": diagnosis,
            "prescription": prescription
        })
        
        return record

    async def rate_appointment(self, appointment_id: str, rating: int, review: str) -> Appointment:
        if rating < 1 or rating > 5:
            raise ValueError("Rating must be between 1 and 5")
        
        appointment = await self.appointment_repo.get_by_id(appointment_id)
        if not appointment:
            raise ValueError("Appointment not found")
        
        await self.appointment_repo.update(appointment_id, {"rating": rating, "review": review})
        
        doctor = await self.doctor_repo.get_by_id(appointment.doctor_id)
        new_rating = ((doctor.rating * doctor.total_reviews) + rating) / (doctor.total_reviews + 1)
        await self.doctor_repo.update_rating(appointment.doctor_id, round(new_rating, 1), doctor.total_reviews + 1)
        
        return await self.appointment_repo.get_by_id(appointment_id)

    async def cancel_appointment(self, appointment_id: str) -> Appointment:
        appointment = await self.appointment_repo.get_by_id(appointment_id)
        if not appointment:
            raise ValueError("Appointment not found")
        
        if appointment.status not in [AppointmentStatus.PENDING, AppointmentStatus.CONFIRMED]:
            raise ValueError("Cannot cancel this appointment")
        
        return await self.appointment_repo.update_status(appointment_id, AppointmentStatus.CANCELLED)

    async def get_patient_appointments(self, patient_id: str) -> List[Appointment]:
        return await self.appointment_repo.get_by_patient(patient_id)

    async def get_doctor_appointments(self, doctor_id: str, date: Optional[datetime] = None) -> List[Appointment]:
        return await self.appointment_repo.get_by_doctor(doctor_id, date)

    async def get_patient_records(self, patient_id: str) -> List[MedicalRecord]:
        return await self.record_repo.get_by_patient(patient_id)

    async def get_video_session(self, session_id: str) -> Optional[VideoSession]:
        return await self.session_repo.get_by_id(session_id)

    async def get_patient(self, patient_id: str) -> Optional[Patient]:
        return await self.patient_repo.get_by_id(patient_id)

    async def update_patient_profile(self, patient_id: str, **data) -> Patient:
        await self.patient_repo.update(patient_id, data)
        return await self.patient_repo.get_by_id(patient_id)
