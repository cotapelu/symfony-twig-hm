from datetime import datetime
from typing import Optional, List
from enum import Enum
from pydantic import BaseModel, Field

class AppointmentStatus(str, Enum):
    PENDING = "pending"
    CONFIRMED = "confirmed"
    IN_PROGRESS = "in_progress"
    COMPLETED = "completed"
    CANCELLED = "cancelled"
    NO_SHOW = "no_show"

class VideoSessionStatus(str, Enum):
    WAITING = "waiting"
    ACTIVE = "active"
    ENDED = "ended"

class ConsultationType(str, Enum):
    VIDEO = "video"
    AUDIO = "audio"
    CHAT = "chat"
    TEXT = "text"

class Patient(BaseModel):
    id: str
    user_id: str
    full_name: str
    date_of_birth: Optional[datetime] = None
    gender: Optional[str] = None
    phone: Optional[str] = None
    email: Optional[str] = None
    address: Optional[str] = None
    medical_history: Optional[str] = None
    allergies: Optional[List[str]] = []
    blood_type: Optional[str] = None
    emergency_contact: Optional[str] = None
    created_at: datetime = Field(default_factory=datetime.utcnow)

class Doctor(BaseModel):
    id: str
    user_id: str
    full_name: str
    specializations: List[str]
    license_number: str
    years_of_experience: int
    education: Optional[str] = None
    languages: List[str] = ["Vietnamese", "English"]
    rating: float = 0.0
    total_reviews: int = 0
    consultation_fee: float
    is_available: bool = True
    bio: Optional[str] = None
    avatar_url: Optional[str] = None
    created_at: datetime = Field(default_factory=datetime.utcnow)

class TimeSlot(BaseModel):
    id: str
    doctor_id: str
    date: datetime
    start_time: str
    end_time: str
    is_available: bool = True

class Appointment(BaseModel):
    id: str
    patient_id: str
    doctor_id: str
    time_slot_id: str
    appointment_type: ConsultationType
    status: AppointmentStatus = AppointmentStatus.PENDING
    chief_complaint: Optional[str] = None
    symptoms: Optional[List[str]] = []
    notes: Optional[str] = None
    diagnosis: Optional[str] = None
    prescription: Optional[str] = None
    follow_up_date: Optional[datetime] = None
    fee: float
    discount: float = 0
    final_fee: float
    rating: Optional[int] = None
    review: Optional[str] = None
    scheduled_at: datetime
    started_at: Optional[datetime] = None
    ended_at: Optional[datetime] = None
    created_at: datetime = Field(default_factory=datetime.utcnow)

class VideoSession(BaseModel):
    id: str
    appointment_id: str
    room_id: str
    token: str
    status: VideoSessionStatus = VideoSessionStatus.WAITING
    started_at: Optional[datetime] = None
    ended_at: Optional[datetime] = None
    duration_seconds: int = 0
    created_at: datetime = Field(default_factory=datetime.utcnow)

class MedicalRecord(BaseModel):
    id: str
    patient_id: str
    appointment_id: str
    doctor_id: str
    diagnosis: str
    treatment_plan: Optional[str] = None
    prescription: Optional[str] = None
    lab_results: Optional[dict] = None
    vital_signs: Optional[dict] = None
    notes: Optional[str] = None
    attachments: Optional[List[str]] = []
    created_at: datetime = Field(default_factory=datetime.utcnow)

class Prescription(BaseModel):
    id: str
    patient_id: str
    doctor_id: str
    appointment_id: str
    medications: List[dict]
    instructions: Optional[str] = None
    valid_until: Optional[datetime] = None
    is_active: bool = True
    created_at: datetime = Field(default_factory=datetime.utcnow)

class ChatMessage(BaseModel):
    id: str
    sender_id: str
    receiver_id: str
    appointment_id: Optional[str] = None
    content: str
    message_type: str = "text"
    attachments: Optional[List[str]] = []
    is_read: bool = False
    created_at: datetime = Field(default_factory=datetime.utcnow)
