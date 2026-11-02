"""In-memory repository implementation for Telemedicine (used in unit tests and dev)."""
from typing import List, Optional, Dict
from datetime import date, datetime
import copy
from ..domain.repository import TelemedicineRepository
from ..domain.models import (
    Doctor, Appointment, Review, AppointmentStatus, ConsultationType
)


class InMemoryTelemedicineRepository(TelemedicineRepository):
    """In-memory implementation using dictionaries. All methods are async for compatibility."""

    def __init__(self):
        self._doctors: Dict[str, Doctor] = {}
        self._appointments: Dict[str, Appointment] = {}
        self._reviews: Dict[str, Review] = {}
        self._init_sample_data()

    def _init_sample_data(self):
        """Initialize with sample doctors."""
        sample_doctors = [
            Doctor(
                id="doc_1",
                user_id="user_1",
                full_name="Dr. Nguyễn Văn An",
                specialty="General Practitioner",
                qualifications=["MD", "Board Certified"],
                experience_years=15,
                rating=4.8,
                review_count=120,
                consultation_fee=300000,
                available_languages=["Vietnamese", "English"],
                working_hours={"mon-fri": "08:00-17:00", "sat": "08:00-12:00"},
                created_at=datetime.now()
            ),
            Doctor(
                id="doc_2",
                user_id="user_2",
                full_name="Dr. Trần Thị Bình",
                specialty="Cardiology",
                qualifications=["MD", "FACC"],
                experience_years=20,
                rating=4.9,
                review_count=85,
                consultation_fee=500000,
                available_languages=["Vietnamese", "English", "French"],
                working_hours={"mon-fri": "09:00-18:00"},
                created_at=datetime.now()
            ),
            Doctor(
                id="doc_3",
                user_id="user_3",
                full_name="Dr. Lê Hoàng Cường",
                specialty="Dermatology",
                qualifications=["MD", "FAAD"],
                experience_years=12,
                rating=4.7,
                review_count=200,
                consultation_fee=400000,
                available_languages=["Vietnamese", "English"],
                working_hours={"mon-sat": "08:30-17:30"},
                created_at=datetime.now()
            )
        ]
        for doctor in sample_doctors:
            self._doctors[doctor.id] = doctor

    # Doctor operations (async wrapper around sync in-memory ops)
    async def create_doctor(self, doctor: Doctor) -> Doctor:
        if doctor.id in self._doctors:
            raise ValueError(f"Doctor with id {doctor.id} already exists")
        self._doctors[doctor.id] = doctor
        return doctor

    async def get_doctor(self, doctor_id: str) -> Optional[Doctor]:
        return self._doctors.get(doctor_id)

    async def list_doctors(
        self,
        specialty: Optional[str] = None,
        language: Optional[str] = None,
        min_rating: Optional[float] = None,
        max_fee: Optional[float] = None,
        available: Optional[bool] = None
    ) -> List[Doctor]:
        results = list(self._doctors.values())
        if specialty:
            results = [d for d in results if specialty.lower() in d.specialty.lower()]
        if language:
            results = [d for d in results if language in d.available_languages]
        if min_rating:
            results = [d for d in results if d.rating >= min_rating]
        if max_fee:
            results = [d for d in results if d.consultation_fee <= max_fee]
        if available is not None:
            results = [d for d in results if d.is_available == available]
        return results

    async def update_doctor(self, doctor: Doctor) -> Doctor:
        if doctor.id not in self._doctors:
            raise ValueError(f"Doctor with id {doctor.id} not found")
        self._doctors[doctor.id] = doctor
        return doctor

    async def delete_doctor(self, doctor_id: str) -> bool:
        if doctor_id in self._doctors:
            del self._doctors[doctor_id]
            return True
        return False

    # Appointment operations
    async def create_appointment(self, appointment: Appointment) -> Appointment:
        # Check doctor exists
        if appointment.doctor_id not in self._doctors:
            raise ValueError("Doctor not found")
        doctor = self._doctors[appointment.doctor_id]
        if not doctor.is_available:
            raise ValueError("Doctor is not available")
        # Check for double-booking
        for a in self._appointments.values():
            if (a.doctor_id == appointment.doctor_id and
                a.status not in [AppointmentStatus.CANCELLED, AppointmentStatus.NO_SHOW] and
                a.scheduled_time == appointment.scheduled_time):
                raise ValueError("Doctor already has an appointment at this time")
        self._appointments[appointment.id] = appointment
        return appointment

    async def get_appointment(self, appointment_id: str) -> Optional[Appointment]:
        return self._appointments.get(appointment_id)

    async def list_appointments(
        self,
        doctor_id: Optional[str] = None,
        patient_id: Optional[str] = None,
        status: Optional[AppointmentStatus] = None,
        date: Optional[date] = None
    ) -> List[Appointment]:
        results = list(self._appointments.values())
        if doctor_id:
            results = [a for a in results if a.doctor_id == doctor_id]
        if patient_id:
            results = [a for a in results if a.patient_id == patient_id]
        if status:
            results = [a for a in results if a.status == status]
        if date:
            results = [a for a in results if a.scheduled_time.date() == date]
        return sorted(results, key=lambda x: x.scheduled_time, reverse=True)

    async def update_appointment(self, appointment: Appointment) -> Appointment:
        if appointment.id not in self._appointments:
            raise ValueError("Appointment not found")
        self._appointments[appointment.id] = appointment
        return appointment

    async def cancel_appointment(self, appointment_id: str) -> Optional[Appointment]:
        if appointment_id not in self._appointments:
            return None
        appointment = self._appointments[appointment_id]
        if appointment.status in [AppointmentStatus.COMPLETED, AppointmentStatus.CANCELLED]:
            raise ValueError("Cannot cancel this appointment")
        appointment.status = AppointmentStatus.CANCELLED
        self._appointments[appointment_id] = appointment
        return appointment

    async def confirm_appointment(self, appointment_id: str, meeting_link: Optional[str] = None) -> Optional[Appointment]:
        if appointment_id not in self._appointments:
            return None
        appointment = self._appointments[appointment_id]
        if appointment.status != AppointmentStatus.PENDING:
            raise ValueError("Only pending appointments can be confirmed")
        appointment.status = AppointmentStatus.CONFIRMED
        if appointment.consultation_type == ConsultationType.VIDEO:
            appointment.meeting_link = meeting_link or f"https://meet.example.com/{appointment_id}"
        self._appointments[appointment_id] = appointment
        return appointment

    # Review operations
    async def create_review(self, review: Review) -> Review:
        self._reviews[review.id] = review
        return review

    async def get_doctor_reviews(self, doctor_id: str) -> List[Review]:
        reviews = [r for r in self._reviews.values() if r.doctor_id == doctor_id]
        return sorted(reviews, key=lambda x: x.created_at, reverse=True)

    # Availability
    async def get_doctor_availability(self, doctor_id: str, date: Optional[date] = None) -> List[str]:
        if doctor_id not in self._doctors:
            raise ValueError("Doctor not found")
        slots = [
            "09:00", "09:30", "10:00", "10:30", "11:00", "11:30",
            "14:00", "14:30", "15:00", "15:30", "16:00", "16:30"
        ]
        doctor_appointments = [
            a for a in self._appointments.values()
            if a.doctor_id == doctor_id and
            a.status not in [AppointmentStatus.CANCELLED, AppointmentStatus.NO_SHOW] and
            (date is None or a.scheduled_time.date() == date)
        ]
        booked_times = [a.scheduled_time.strftime("%H:%M") for a in doctor_appointments]
        return [s for s in slots if s not in booked_times]

    # Helper methods for testing
    async def clear(self):
        """Clear all data."""
        self._doctors.clear()
        self._appointments.clear()
        self._reviews.clear()
        self._init_sample_data()

    async def get_all_doctors(self) -> List[Doctor]:
        return list(self._doctors.values())

    async def get_all_appointments(self) -> List[Appointment]:
        return list(self._appointments.values())

    async def get_all_reviews(self) -> List[Review]:
        return list(self._reviews.values())
