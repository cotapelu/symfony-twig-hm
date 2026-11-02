from datetime import datetime, timedelta, timezone
from typing import Optional, List
import uuid
from modules.DoctorBooking.models.schemas import Appointment, TimeSlot, Doctor, PatientInfo, Hospital, BookingStatus, PaymentStatus
from modules.DoctorBooking.repository.repositories import AppointmentRepository, TimeSlotRepository, DoctorRepository, HospitalRepository, PatientRepository

class DoctorBookingService:
    def __init__(self, db):
        self.db = db
        self.appointment_repo = AppointmentRepository(db)
        self.slot_repo = TimeSlotRepository(db)
        self.doctor_repo = DoctorRepository(db)
        self.hospital_repo = HospitalRepository(db)
        self.patient_repo = PatientRepository(db)

    async def register_patient(self, user_id: str, full_name: str, phone: str, **kwargs) -> PatientInfo:
        patient = PatientInfo(user_id=user_id, full_name=full_name, phone=phone, **kwargs)
        return await self.patient_repo.create(patient)

    async def register_doctor(self, hospital_id: str, department_id: str, user_id: str, full_name: str,
                              specializations: List[str], license_number: str, years_of_experience: int,
                              consultation_fee: float, **kwargs) -> Doctor:
        doctor = Doctor(
            hospital_id=hospital_id, department_id=department_id, user_id=user_id,
            full_name=full_name, specializations=specializations, license_number=license_number,
            years_of_experience=years_of_experience, consultation_fee=consultation_fee, **kwargs
        )
        return await self.doctor_repo.create(doctor)

    async def register_hospital(self, name: str, address: str, city: str, country: str, phone: str, **kwargs) -> Hospital:
        hospital = Hospital(name=name, address=address, city=city, country=country, phone=phone, **kwargs)
        return await self.hospital_repo.create(hospital)

    async def get_hospitals(self, query: Optional[str] = None) -> List[Hospital]:
        if query:
            return await self.hospital_repo.search(query)
        return await self.hospital_repo.get_all()

    async def get_hospital(self, hospital_id: str) -> Optional[Hospital]:
        return await self.hospital_repo.get_by_id(hospital_id)

    async def get_doctors(self, specialization: Optional[str] = None) -> List[Doctor]:
        return await self.doctor_repo.get_all(specialization)

    async def get_doctor(self, doctor_id: str) -> Optional[Doctor]:
        return await self.doctor_repo.get_by_id(doctor_id)

    async def get_doctors_by_hospital(self, hospital_id: str) -> List[Doctor]:
        return await self.doctor_repo.get_by_hospital(hospital_id)

    async def search_doctors(self, query: str, hospital_id: Optional[str] = None) -> List[Doctor]:
        return await self.doctor_repo.search(query, hospital_id)

    async def create_time_slots(self, doctor_id: str, date: datetime, slot_duration: int = 30) -> List[TimeSlot]:
        doctor = await self.doctor_repo.get_by_id(doctor_id)
        if not doctor:
            raise ValueError("Doctor not found")
        
        slots = []
        start_hour, end_hour = 8, 17
        current = date.replace(hour=start_hour, minute=0, second=0, microsecond=0)
        end = date.replace(hour=end_hour, minute=0, second=0, microsecond=0)
        
        while current < end:
            slot = TimeSlot(
                id=str(uuid.uuid4()),
                doctor_id=doctor_id,
                date=date,
                start_time=current.strftime("%H:%M"),
                end_time=(current + timedelta(minutes=slot_duration)).strftime("%H:%M"),
                is_available=True,
                fee=doctor.consultation_fee
            )
            slots.append(await self.slot_repo.create(slot))
            current += timedelta(minutes=slot_duration)
        
        return slots

    async def get_available_slots(self, doctor_id: str, date: datetime) -> List[TimeSlot]:
        return await self.slot_repo.get_available(doctor_id, date)

    async def book_appointment(self, patient_id: str, doctor_id: str, time_slot_id: str,
                                appointment_type: str, chief_complaint: str = None, notes: str = None) -> Appointment:
        # Validate doctor
        doctor = await self.doctor_repo.get_by_id(doctor_id)
        if not doctor or not doctor.is_available:
            raise ValueError("Doctor not available")
        
        # Validate the specific time slot
        slot = await self.slot_repo.get_by_id(time_slot_id)
        if not slot or not slot.is_available or slot.doctor_id != doctor_id:
            raise ValueError("Time slot not available")
        
        fee = slot.fee
        if fee <= 0:
            raise ValueError("Appointment fee must be positive")
        
        appointment = Appointment(
            id=str(uuid.uuid4()),
            patient_id=patient_id,
            doctor_id=doctor_id,
            hospital_id=doctor.hospital_id,
            department_id=doctor.department_id,
            time_slot_id=time_slot_id,
            appointment_type=appointment_type,
            chief_complaint=chief_complaint,
            notes=notes,
            booking_date=datetime.now(timezone.utc),
            fee=fee,
            final_fee=fee,
            status=BookingStatus.PENDING,
            payment_status=PaymentStatus.PENDING
        )
        
        await self.slot_repo.book(time_slot_id)
        return await self.appointment_repo.create(appointment)

    async def confirm_booking(self, appointment_id: str) -> Appointment:
        return await self.appointment_repo.update_status(appointment_id, BookingStatus.CONFIRMED)

    async def process_payment(self, appointment_id: str, transaction_id: str) -> Appointment:
        return await self.appointment_repo.update_payment(appointment_id, PaymentStatus.PAID, transaction_id)

    async def cancel_booking(self, appointment_id: str) -> Appointment:
        appointment = await self.appointment_repo.get_by_id(appointment_id)
        if not appointment:
            raise ValueError("Appointment not found")
        
        if appointment.status not in [BookingStatus.PENDING, BookingStatus.CONFIRMED]:
            raise ValueError("Cannot cancel this booking")
        
        await self.slot_repo.release(appointment.time_slot_id)
        return await self.appointment_repo.cancel(appointment_id)

    async def rate_appointment(self, appointment_id: str, rating: int, review: str) -> Appointment:
        if rating < 1 or rating > 5:
            raise ValueError("Rating must be between 1 and 5")
        
        appointment = await self.appointment_repo.get_by_id(appointment_id)
        if not appointment:
            raise ValueError("Appointment not found")
        
        await self.appointment_repo.update(appointment_id, {"rating": rating, "review": review})
        
        doctor = await self.doctor_repo.get_by_id(appointment.doctor_id)
        new_rating = ((doctor.rating * doctor.total_reviews) + rating) / (doctor.total_reviews + 1)
        await self.doctor_repo.update(appointment.doctor_id, {"rating": round(new_rating, 1), "total_reviews": doctor.total_reviews + 1})
        
        return await self.appointment_repo.get_by_id(appointment_id)

    async def get_patient_bookings(self, patient_id: str) -> List[Appointment]:
        return await self.appointment_repo.get_by_patient(patient_id)

    async def get_doctor_bookings(self, doctor_id: str, date: Optional[datetime] = None) -> List[Appointment]:
        return await self.appointment_repo.get_by_doctor(doctor_id, date)

    async def get_booking_details(self, appointment_id: str) -> Optional[Appointment]:
        return await self.appointment_repo.get_by_id(appointment_id)

    async def get_patient(self, patient_id: str) -> Optional[PatientInfo]:
        return await self.patient_repo.get_by_id(patient_id)

    async def update_patient(self, patient_id: str, **data) -> PatientInfo:
        await self.patient_repo.update(patient_id, data)
        return await self.patient_repo.get_by_id(patient_id)

    async def get_today_appointments(self, doctor_id: str) -> List[Appointment]:
        today = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0)
        return await self.appointment_repo.get_by_doctor(doctor_id, today)
