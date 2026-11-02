"""Database-backed repository implementation for Telemedicine using SQLAlchemy async."""
from typing import List, Optional
from datetime import date, datetime
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, and_, func
import json

from ..domain.repository import TelemedicineRepository
from ..domain.models import (
    Doctor, Appointment, Review,
    AppointmentStatus, ConsultationType
)
from .models import (
    DoctorModel, AppointmentModel, ReviewModel
)


class DatabaseTelemedicineRepository(TelemedicineRepository):
    """Async SQLAlchemy repository."""

    def __init__(self, db_session: AsyncSession):
        self.db = db_session

    # Doctor operations
    async def create_doctor(self, doctor: Doctor) -> Doctor:
        model = self._to_doctor_model(doctor)
        self.db.add(model)
        await self.db.commit()
        await self.db.refresh(model)
        return self._from_doctor_model(model)

    async def get_doctor(self, doctor_id: str) -> Optional[Doctor]:
        model = await self.db.get(DoctorModel, doctor_id)
        return self._from_doctor_model(model) if model else None

    async def list_doctors(
        self,
        specialty: Optional[str] = None,
        language: Optional[str] = None,
        min_rating: Optional[float] = None,
        max_fee: Optional[float] = None,
        available: Optional[bool] = None
    ) -> List[Doctor]:
        stmt = select(DoctorModel)

        if specialty:
            stmt = stmt.where(DoctorModel.specialty.ilike(f"%{specialty.lower()}%"))
        if language:
            stmt = stmt.where(DoctorModel.available_languages.contains(language))
        if min_rating:
            stmt = stmt.where(DoctorModel.rating >= min_rating)
        if max_fee:
            stmt = stmt.where(DoctorModel.consultation_fee <= max_fee)
        if available is not None:
            stmt = stmt.where(DoctorModel.is_available == available)

        result = await self.db.execute(stmt)
        models = result.scalars().all()
        return [self._from_doctor_model(m) for m in models]

    async def update_doctor(self, doctor: Doctor) -> Doctor:
        model = await self.db.get(DoctorModel, doctor.id)
        if not model:
            raise ValueError(f"Doctor with id {doctor.id} not found")
        self._update_doctor_model(model, doctor)
        await self.db.commit()
        await self.db.refresh(model)
        return self._from_doctor_model(model)

    async def delete_doctor(self, doctor_id: str) -> bool:
        model = await self.db.get(DoctorModel, doctor_id)
        if model:
            await self.db.delete(model)
            await self.db.commit()
            return True
        return False

    # Appointment operations
    async def create_appointment(self, appointment: Appointment) -> Appointment:
        # Check doctor exists
        doctor = await self.get_doctor(appointment.doctor_id)
        if not doctor:
            raise ValueError("Doctor not found")

        if not doctor.is_available:
            raise ValueError("Doctor is not available")

        # Check for double-booking
        existing = await self._find_conflicting_appointment(appointment.doctor_id, appointment.scheduled_time)
        if existing:
            raise ValueError("Doctor already has an appointment at this time")

        model = self._to_appointment_model(appointment)
        self.db.add(model)
        await self.db.commit()
        await self.db.refresh(model)
        return self._from_appointment_model(model)

    async def get_appointment(self, appointment_id: str) -> Optional[Appointment]:
        model = await self.db.get(AppointmentModel, appointment_id)
        return self._from_appointment_model(model) if model else None

    async def list_appointments(
        self,
        doctor_id: Optional[str] = None,
        patient_id: Optional[str] = None,
        status: Optional[AppointmentStatus] = None,
        date: Optional[date] = None
    ) -> List[Appointment]:
        stmt = select(AppointmentModel)

        if doctor_id:
            stmt = stmt.where(AppointmentModel.doctor_id == doctor_id)
        if patient_id:
            stmt = stmt.where(AppointmentModel.patient_id == patient_id)
        if status:
            stmt = stmt.where(AppointmentModel.status == status)
        if date:
            stmt = stmt.where(func.date(AppointmentModel.scheduled_time) == date)

        stmt = stmt.order_by(AppointmentModel.scheduled_time.desc())

        result = await self.db.execute(stmt)
        models = result.scalars().all()
        return [self._from_appointment_model(m) for m in models]

    async def update_appointment(self, appointment: Appointment) -> Appointment:
        model = await self.db.get(AppointmentModel, appointment.id)
        if not model:
            raise ValueError("Appointment not found")
        self._update_appointment_model(model, appointment)
        await self.db.commit()
        await self.db.refresh(model)
        return self._from_appointment_model(model)

    async def cancel_appointment(self, appointment_id: str) -> Optional[Appointment]:
        model = await self.db.get(AppointmentModel, appointment_id)
        if not model:
            return None
        if model.status in [AppointmentStatus.COMPLETED, AppointmentStatus.CANCELLED]:
            raise ValueError("Cannot cancel this appointment")
        model.status = AppointmentStatus.CANCELLED
        await self.db.commit()
        await self.db.refresh(model)
        return self._from_appointment_model(model)

    async def confirm_appointment(self, appointment_id: str, meeting_link: Optional[str] = None) -> Optional[Appointment]:
        model = await self.db.get(AppointmentModel, appointment_id)
        if not model:
            return None
        if model.status != AppointmentStatus.PENDING:
            raise ValueError("Only pending appointments can be confirmed")
        model.status = AppointmentStatus.CONFIRMED
        if model.consultation_type == ConsultationType.VIDEO:
            model.meeting_link = meeting_link or f"https://meet.example.com/{appointment_id}"
        await self.db.commit()
        await self.db.refresh(model)
        return self._from_appointment_model(model)

    # Review operations
    async def create_review(self, review: Review) -> Review:
        model = self._to_review_model(review)
        self.db.add(model)
        await self.db.commit()
        await self.db.refresh(model)
        return self._from_review_model(model)

    async def get_doctor_reviews(self, doctor_id: str) -> List[Review]:
        stmt = select(ReviewModel).where(ReviewModel.doctor_id == doctor_id).order_by(ReviewModel.created_at.desc())
        result = await self.db.execute(stmt)
        models = result.scalars().all()
        return [self._from_review_model(m) for m in models]

    # Availability
    async def get_doctor_availability(self, doctor_id: str, date: Optional[date] = None) -> List[str]:
        if not await self._doctor_exists(doctor_id):
            raise ValueError("Doctor not found")

        slots = [
            "09:00", "09:30", "10:00", "10:30", "11:00", "11:30",
            "14:00", "14:30", "15:00", "15:30", "16:00", "16:30"
        ]

        stmt = select(AppointmentModel).where(
            and_(
                AppointmentModel.doctor_id == doctor_id,
                AppointmentModel.status.in_([AppointmentStatus.CONFIRMED, AppointmentStatus.PENDING])
            )
        )
        if date:
            stmt = stmt.where(func.date(AppointmentModel.scheduled_time) == date)

        result = await self.db.execute(stmt)
        appointments = result.scalars().all()

        booked_times = [a.scheduled_time.strftime("%H:%M") for a in appointments]
        return [s for s in slots if s not in booked_times]

    async def _doctor_exists(self, doctor_id: str) -> bool:
        result = await self.db.execute(select(DoctorModel.id).where(DoctorModel.id == doctor_id))
        return result.scalar_one_or_none() is not None

    async def _find_conflicting_appointment(self, doctor_id: str, scheduled_time: datetime) -> Optional[AppointmentModel]:
        stmt = select(AppointmentModel).where(
            and_(
                AppointmentModel.doctor_id == doctor_id,
                AppointmentModel.scheduled_time == scheduled_time,
                AppointmentModel.status.in_([AppointmentStatus.CONFIRMED, AppointmentStatus.PENDING])
            )
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    # Mappers
    def _to_doctor_model(self, doctor: Doctor) -> DoctorModel:
        return DoctorModel(
            id=doctor.id,
            user_id=doctor.user_id,
            full_name=doctor.full_name,
            specialty=doctor.specialty,
            qualifications=json.dumps(doctor.qualifications),
            experience_years=doctor.experience_years,
            rating=doctor.rating,
            review_count=doctor.review_count,
            consultation_fee=doctor.consultation_fee,
            available_languages=json.dumps(doctor.available_languages),
            profile_image=doctor.profile_image,
            is_available=doctor.is_available,
            working_hours=json.dumps(doctor.working_hours),
            created_at=doctor.created_at or datetime.utcnow(),
            updated_at=doctor.updated_at or datetime.utcnow()
        )

    def _from_doctor_model(self, model: DoctorModel) -> Doctor:
        return Doctor(
            id=model.id,
            user_id=model.user_id,
            full_name=model.full_name,
            specialty=model.specialty,
            qualifications=json.loads(model.qualifications) if model.qualifications else [],
            experience_years=model.experience_years,
            rating=model.rating,
            review_count=model.review_count,
            consultation_fee=float(model.consultation_fee),
            available_languages=json.loads(model.available_languages) if model.available_languages else [],
            profile_image=model.profile_image,
            is_available=model.is_available,
            working_hours=json.loads(model.working_hours) if model.working_hours else {},
            created_at=model.created_at,
            updated_at=model.updated_at
        )

    def _to_appointment_model(self, appointment: Appointment) -> AppointmentModel:
        return AppointmentModel(
            id=appointment.id,
            doctor_id=appointment.doctor_id,
            patient_id=appointment.patient_id,
            scheduled_time=appointment.scheduled_time,
            duration_minutes=appointment.duration_minutes,
            consultation_type=appointment.consultation_type,
            status=appointment.status,
            symptoms=appointment.symptoms,
            diagnosis=appointment.diagnosis,
            prescription=appointment.prescription,
            notes=appointment.notes,
            meeting_link=appointment.meeting_link,
            created_at=appointment.created_at,
            completed_at=appointment.completed_at
        )

    def _from_appointment_model(self, model: AppointmentModel) -> Appointment:
        return Appointment(
            id=model.id,
            doctor_id=model.doctor_id,
            patient_id=model.patient_id,
            scheduled_time=model.scheduled_time,
            duration_minutes=model.duration_minutes,
            consultation_type=model.consultation_type,
            status=model.status,
            symptoms=model.symptoms,
            diagnosis=model.diagnosis,
            prescription=model.prescription,
            notes=model.notes,
            meeting_link=model.meeting_link,
            created_at=model.created_at,
            completed_at=model.completed_at
        )

    def _to_review_model(self, review: Review) -> ReviewModel:
        return ReviewModel(
            id=review.id,
            appointment_id=review.appointment_id,
            doctor_id=review.doctor_id,
            patient_id=review.patient_id,
            rating=review.rating,
            comment=review.comment,
            created_at=review.created_at
        )

    def _from_review_model(self, model: ReviewModel) -> Review:
        return Review(
            id=model.id,
            appointment_id=model.appointment_id,
            doctor_id=model.doctor_id,
            patient_id=model.patient_id,
            rating=model.rating,
            comment=model.comment,
            created_at=model.created_at
        )

    def _update_doctor_model(self, model: DoctorModel, doctor: Doctor):
        model.full_name = doctor.full_name
        model.specialty = doctor.specialty
        model.qualifications = json.dumps(doctor.qualifications)
        model.experience_years = doctor.experience_years
        model.rating = doctor.rating
        model.review_count = doctor.review_count
        model.consultation_fee = doctor.consultation_fee
        model.available_languages = json.dumps(doctor.available_languages)
        model.profile_image = doctor.profile_image
        model.is_available = doctor.is_available
        model.working_hours = json.dumps(doctor.working_hours)
        model.updated_at = datetime.utcnow()

    def _update_appointment_model(self, model: AppointmentModel, appointment: Appointment):
        model.doctor_id = appointment.doctor_id
        model.patient_id = appointment.patient_id
        model.scheduled_time = appointment.scheduled_time
        model.duration_minutes = appointment.duration_minutes
        model.consultation_type = appointment.consultation_type
        model.status = appointment.status
        model.symptoms = appointment.symptoms
        model.diagnosis = appointment.diagnosis
        model.prescription = appointment.prescription
        model.notes = appointment.notes
        model.meeting_link = appointment.meeting_link
        model.completed_at = appointment.completed_at
        model.updated_at = datetime.utcnow()
