"""Domain models for Telemedicine module."""
from datetime import datetime, date
from typing import List, Optional
from pydantic import BaseModel, Field
from enum import Enum


class AppointmentStatus(str, Enum):
    PENDING = "pending"
    CONFIRMED = "confirmed"
    COMPLETED = "completed"
    CANCELLED = "cancelled"
    NO_SHOW = "no_show"


class ConsultationType(str, Enum):
    VIDEO = "video"
    AUDIO = "audio"
    CHAT = "chat"


class Doctor(BaseModel):
    id: str
    user_id: str
    full_name: str
    specialty: str
    qualifications: List[str]
    experience_years: int
    rating: float = 0.0
    review_count: int = 0
    consultation_fee: float
    available_languages: List[str]
    profile_image: Optional[str] = None
    is_available: bool = True
    working_hours: dict
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class Patient(BaseModel):
    id: str
    user_id: str
    full_name: str
    date_of_birth: date
    gender: str
    phone: str
    email: Optional[str] = None
    medical_history: Optional[str] = None
    allergies: List[str] = []
    current_medications: List[str] = []
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class Appointment(BaseModel):
    id: str
    doctor_id: str
    patient_id: str
    scheduled_time: datetime
    duration_minutes: int = 30
    consultation_type: str  # ConsultationType value
    status: str  # AppointmentStatus value
    symptoms: Optional[str] = None
    diagnosis: Optional[str] = None
    prescription: Optional[str] = None
    notes: Optional[str] = None
    meeting_link: Optional[str] = None
    created_at: datetime
    completed_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class AppointmentCreate(BaseModel):
    doctor_id: str
    patient_id: str
    scheduled_time: datetime
    consultation_type: str
    duration_minutes: int = 30
    symptoms: Optional[str] = None
    notes: Optional[str] = None


class AppointmentUpdate(BaseModel):
    status: Optional[str] = None
    diagnosis: Optional[str] = None
    prescription: Optional[str] = None
    notes: Optional[str] = None
    meeting_link: Optional[str] = None


class Review(BaseModel):
    id: str
    appointment_id: str
    doctor_id: str
    patient_id: str
    rating: int = Field(..., ge=1, le=5)
    comment: Optional[str] = None
    created_at: datetime

    class Config:
        from_attributes = True


class ReviewCreate(BaseModel):
    appointment_id: str
    rating: int = Field(..., ge=1, le=5)
    comment: Optional[str] = None
