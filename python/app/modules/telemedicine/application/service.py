"""Application service for Telemedicine business logic."""
from typing import List, Optional
from datetime import datetime, date
from ..domain.repository import TelemedicineRepository
from ..domain.models import (
    Doctor, Appointment, Review, AppointmentCreate, AppointmentUpdate, ReviewCreate,
    AppointmentStatus, ConsultationType
)


class TelemedicineService:
    """Service layer encapsulating telemedicine business rules."""

    def __init__(self, repository: TelemedicineRepository):
        self.repo = repository

    # Doctor methods
    async def search_doctors(
        self,
        specialty: Optional[str] = None,
        language: Optional[str] = None,
        min_rating: Optional[float] = None,
        max_fee: Optional[float] = None,
        available: Optional[bool] = None
    ) -> List[Doctor]:
        return await self.repo.list_doctors(specialty, language, min_rating, max_fee, available)

    async def get_doctor(self, doctor_id: str) -> Optional[Doctor]:
        return await self.repo.get_doctor(doctor_id)

    async def create_doctor(self, doctor: Doctor) -> Doctor:
        return await self.repo.create_doctor(doctor)

    async def update_doctor(self, doctor: Doctor) -> Doctor:
        return await self.repo.update_doctor(doctor)

    async def delete_doctor(self, doctor_id: str) -> bool:
        return await self.repo.delete_doctor(doctor_id)

    # Appointment methods
    async def book_appointment(self, appointment_data: AppointmentCreate, patient_id: str) -> Appointment:
        """Book a new appointment with business validation."""
        # Ensure patient_id matches (prevents IDOR at service level)
        if appointment_data.patient_id != patient_id:
            raise PermissionError("Cannot create appointment for another patient")

        # Check doctor exists and is available
        doctor = await self.repo.get_doctor(appointment_data.doctor_id)
        if not doctor:
            raise ValueError("Doctor not found")
        if not doctor.is_available:
            raise ValueError("Doctor is not available")

        # Create appointment object
        appointment = Appointment(
            id=f"appt_{datetime.now().timestamp()}",
            doctor_id=appointment_data.doctor_id,
            patient_id=patient_id,
            scheduled_time=appointment_data.scheduled_time,
            duration_minutes=appointment_data.duration_minutes,
            consultation_type=appointment_data.consultation_type,
            status=AppointmentStatus.PENDING,
            symptoms=appointment_data.symptoms,
            notes=appointment_data.notes,
            created_at=datetime.now()
        )

        return await self.repo.create_appointment(appointment)

    async def get_appointment(self, appointment_id: str) -> Optional[Appointment]:
        return await self.repo.get_appointment(appointment_id)

    async def get_patient_appointments(self, patient_id: str, status: Optional[AppointmentStatus] = None) -> List[Appointment]:
        return await self.repo.list_appointments(patient_id=patient_id, status=status)

    async def get_doctor_appointments(self, doctor_id: str, date: Optional[date] = None) -> List[Appointment]:
        return await self.repo.list_appointments(doctor_id=doctor_id, date=date)

    async def update_appointment(self, appointment_id: str, update_data: AppointmentUpdate, user_id: str) -> Optional[Appointment]:
        """Update appointment with ownership check."""
        appointment = await self.repo.get_appointment(appointment_id)
        if not appointment:
            return None

        # Authorization: only patient or the assigned doctor can update
        is_patient = user_id == appointment.patient_id
        is_doctor = False
        if not is_patient:
            doctor = await self.repo.get_doctor(appointment.doctor_id)
            if doctor and doctor.user_id == user_id:
                is_doctor = True
        if not (is_patient or is_doctor):
            raise PermissionError("Not authorized to update this appointment")

        # Apply updates
        if update_data.status:
            appointment.status = update_data.status
            if update_data.status == AppointmentStatus.COMPLETED:
                appointment.completed_at = datetime.now()

        if update_data.diagnosis:
            appointment.diagnosis = update_data.diagnosis
        if update_data.prescription:
            appointment.prescription = update_data.prescription
        if update_data.notes:
            appointment.notes = update_data.notes
        if update_data.meeting_link:
            appointment.meeting_link = update_data.meeting_link

        return await self.repo.update_appointment(appointment)

    async def cancel_appointment(self, appointment_id: str, user_id: str) -> Optional[Appointment]:
        """Cancel an appointment with ownership check."""
        appointment = await self.repo.get_appointment(appointment_id)
        if not appointment:
            return None

        is_patient = user_id == appointment.patient_id
        is_doctor = False
        if not is_patient:
            doctor = await self.repo.get_doctor(appointment.doctor_id)
            if doctor and doctor.user_id == user_id:
                is_doctor = True
        if not (is_patient or is_doctor):
            raise PermissionError("Not authorized to cancel this appointment")

        if appointment.status in [AppointmentStatus.COMPLETED, AppointmentStatus.CANCELLED]:
            raise ValueError("Cannot cancel this appointment")

        return await self.repo.cancel_appointment(appointment_id)

    async def confirm_appointment(self, appointment_id: str, user_id: str) -> Optional[Appointment]:
        """Confirm a pending appointment (doctor only)."""
        appointment = await self.repo.get_appointment(appointment_id)
        if not appointment:
            return None

        doctor = await self.repo.get_doctor(appointment.doctor_id)
        if not doctor or doctor.user_id != user_id:
            raise PermissionError("Only the assigned doctor can confirm appointments")

        if appointment.status != AppointmentStatus.PENDING:
            raise ValueError("Only pending appointments can be confirmed")

        return await self.repo.confirm_appointment(appointment_id)

    async def get_doctor_availability(self, doctor_id: str, date: Optional[date] = None) -> List[str]:
        return await self.repo.get_doctor_availability(doctor_id, date)

    # Review methods
    async def create_review(self, review_data: ReviewCreate, patient_id: str) -> Review:
        """Create review for a completed appointment."""
        # Validate appointment
        appointment = await self.repo.get_appointment(review_data.appointment_id)
        if not appointment:
            raise ValueError("Appointment not found")

        if appointment.patient_id != patient_id:
            raise PermissionError("Only the patient can review the appointment")

        if appointment.status != AppointmentStatus.COMPLETED:
            raise ValueError("Can only review completed appointments")

        review = Review(
            id=f"review_{datetime.now().timestamp()}",
            appointment_id=review_data.appointment_id,
            doctor_id=appointment.doctor_id,
            patient_id=patient_id,
            rating=review_data.rating,
            comment=review_data.comment,
            created_at=datetime.now()
        )

        created_review = await self.repo.create_review(review)

        # Update doctor's average rating
        await self._recalculate_doctor_rating(appointment.doctor_id)

        return created_review

    async def get_doctor_reviews(self, doctor_id: str) -> List[Review]:
        return await self.repo.get_doctor_reviews(doctor_id)

    async def _recalculate_doctor_rating(self, doctor_id: str):
        """Recalculate doctor's average rating and review count."""
        reviews = await self.repo.get_doctor_reviews(doctor_id)
        doctor = await self.repo.get_doctor(doctor_id)
        if doctor and reviews:
            avg_rating = sum(r.rating for r in reviews) / len(reviews)
            doctor.rating = round(avg_rating, 1)
            doctor.review_count = len(reviews)
            await self.repo.update_doctor(doctor)
        elif doctor:
            doctor.rating = 0.0
            doctor.review_count = 0
            await self.repo.update_doctor(doctor)
