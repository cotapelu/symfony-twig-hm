from datetime import datetime, timedelta
from typing import Optional, List
import uuid
from pydantic import BaseModel, Field
from enum import Enum

class BookingStatus(str, Enum):
    PENDING = "pending"
    CONFIRMED = "confirmed"
    CANCELLED = "cancelled"
    COMPLETED = "completed"
    NO_SHOW = "no_show"

class PaymentStatus(str, Enum):
    PENDING = "pending"
    PAID = "paid"
    REFUNDED = "refunded"
    FAILED = "failed"

class Hospital(BaseModel):
    id: str
    name: str
    address: str
    city: str
    country: str
    phone: str
    email: Optional[str] = None
    website: Optional[str] = None
    rating: float = 0.0
    total_reviews: int = 0
    specialties: List[str] = []
    facilities: List[str] = []
    opening_hours: Optional[dict] = None
    logo_url: Optional[str] = None
    is_active: bool = True

class Department(BaseModel):
    id: str
    hospital_id: str
    name: str
    description: Optional[str] = None
    specialties: List[str] = []

class Doctor(BaseModel):
    id: str
    hospital_id: str
    department_id: str
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

class TimeSlot(BaseModel):
    id: str
    doctor_id: str
    date: datetime
    start_time: str
    end_time: str
    is_available: bool = True
    fee: float

class Appointment(BaseModel):
    id: str
    patient_id: str
    doctor_id: str
    hospital_id: str
    department_id: str
    time_slot_id: str
    appointment_type: str
    status: BookingStatus = BookingStatus.PENDING
    chief_complaint: Optional[str] = None
    notes: Optional[str] = None
    booking_date: datetime
    fee: float
    discount: float = 0
    final_fee: float
    payment_status: PaymentStatus = PaymentStatus.PENDING
    transaction_id: Optional[str] = None
    rating: Optional[int] = None
    review: Optional[str] = None
    created_at: datetime = Field(default_factory=datetime.utcnow)

class PatientInfo(BaseModel):
    id: str
    user_id: str
    full_name: str
    date_of_birth: Optional[datetime] = None
    gender: Optional[str] = None
    phone: str
    email: Optional[str] = None
    address: Optional[str] = None
    emergency_contact: Optional[str] = None
    insurance_number: Optional[str] = None
    insurance_provider: Optional[str] = None

class BookingRequest(BaseModel):
    patient_id: str
    doctor_id: str
    time_slot_id: str
    appointment_type: str
    chief_complaint: Optional[str] = None
    notes: Optional[str] = None
