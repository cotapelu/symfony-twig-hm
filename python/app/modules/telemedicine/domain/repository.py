"""Abstract repository interface for Telemedicine module."""
from abc import ABC, abstractmethod
from typing import List, Optional
from datetime import date
from .models import (
    Doctor, Appointment, Review,
    AppointmentStatus, ConsultationType
)


class TelemedicineRepository(ABC):
    """Abstract repository for telemedicine data access."""

    # Doctor operations
    @abstractmethod
    async def create_doctor(self, doctor: Doctor) -> Doctor:
        pass

    @abstractmethod
    async def get_doctor(self, doctor_id: str) -> Optional[Doctor]:
        pass

    @abstractmethod
    async def list_doctors(
        self,
        specialty: Optional[str] = None,
        language: Optional[str] = None,
        min_rating: Optional[float] = None,
        max_fee: Optional[float] = None,
        available: Optional[bool] = None
    ) -> List[Doctor]:
        pass

    @abstractmethod
    async def update_doctor(self, doctor: Doctor) -> Doctor:
        pass

    @abstractmethod
    async def delete_doctor(self, doctor_id: str) -> bool:
        pass

    # Appointment operations
    @abstractmethod
    async def create_appointment(self, appointment: Appointment) -> Appointment:
        pass

    @abstractmethod
    async def get_appointment(self, appointment_id: str) -> Optional[Appointment]:
        pass

    @abstractmethod
    async def list_appointments(
        self,
        doctor_id: Optional[str] = None,
        patient_id: Optional[str] = None,
        status: Optional[AppointmentStatus] = None,
        date: Optional[date] = None
    ) -> List[Appointment]:
        pass

    @abstractmethod
    async def update_appointment(self, appointment: Appointment) -> Appointment:
        pass

    @abstractmethod
    async def cancel_appointment(self, appointment_id: str) -> Optional[Appointment]:
        pass

    @abstractmethod
    async def confirm_appointment(self, appointment_id: str, meeting_link: Optional[str] = None) -> Optional[Appointment]:
        pass

    # Review operations
    @abstractmethod
    async def create_review(self, review: Review) -> Review:
        pass

    @abstractmethod
    async def get_doctor_reviews(self, doctor_id: str) -> List[Review]:
        pass

    # Availability
    @abstractmethod
    async def get_doctor_availability(self, doctor_id: str, date: Optional[date] = None) -> List[str]:
        """Return list of available time slots (HH:MM format)."""
        pass
