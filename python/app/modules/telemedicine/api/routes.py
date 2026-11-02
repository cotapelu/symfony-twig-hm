"""Telemedicine API routes with repository abstraction for integration testing."""
from fastapi import APIRouter, HTTPException, Depends, Request
from typing import List, Optional, Dict
from datetime import datetime, date, timedelta
import time
import asyncio
from collections import defaultdict, deque

from app.shared.middleware.auth import get_current_user_id

# Import domain models and repository
from ..domain.models import (
    Doctor, Appointment, Review, AppointmentCreate, AppointmentUpdate, ReviewCreate,
    AppointmentStatus, ConsultationType
)
from ..domain.repository import TelemedicineRepository
from ..infrastructure.in_memory_repository import InMemoryTelemedicineRepository
from ..application.service import TelemedicineService

router = APIRouter(prefix="/api/telemedicine", tags=["telemedicine"])

# Rate limiting
_telemedicine_rate_data: Dict[str, deque] = defaultdict(deque)
_telemedicine_lock = asyncio.Lock()

async def telemedicine_user_with_rate_limit(user_id: str = Depends(get_current_user_id)) -> str:
    async with _telemedicine_lock:
        now = time.time()
        timestamps = _telemedicine_rate_data[user_id]
        while timestamps and timestamps[0] < now - 60:
            timestamps.popleft()
        if len(timestamps) >= 60:
            raise HTTPException(
                status_code=429,
                detail="Rate limit exceeded. Maximum 60 telemedicine requests per minute.",
                headers={
                    "X-RateLimit-Limit": "60",
                    "X-RateLimit-Remaining": "0",
                    "Retry-After": "60"
                }
            )
        timestamps.append(now)
    return user_id

# Global lock for appointment creation
_appointment_creation_lock = asyncio.Lock()

# Repository and Service (default InMemory)
_repository: TelemedicineRepository = InMemoryTelemedicineRepository()
service = TelemedicineService(_repository)

def set_repository(repo: TelemedicineRepository):
    """Override repository (used by integration tests)."""
    global _repository, service
    _repository = repo
    service = TelemedicineService(_repository)

@router.get("/doctors", response_model=List[Doctor])
async def search_doctors(
    specialty: Optional[str] = None,
    language: Optional[str] = None,
    min_rating: Optional[float] = None,
    max_fee: Optional[float] = None,
    available: Optional[bool] = None,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
):
    """Search for doctors with filters"""
    return await service.search_doctors(specialty, language, min_rating, max_fee, available)

@router.get("/doctors/{doctor_id}", response_model=Doctor)
async def get_doctor(
    doctor_id: str,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
):
    """Get doctor details"""
    doctor = await service.get_doctor(doctor_id)
    if not doctor:
        raise HTTPException(status_code=404, detail="Doctor not found")
    return doctor

@router.get("/doctors/{doctor_id}/availability")
async def get_doctor_availability(
    doctor_id: str,
    date: Optional[date] = None,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
):
    """Get doctor's available time slots"""
    if await service.get_doctor(doctor_id) is None:
        raise HTTPException(status_code=404, detail="Doctor not found")
    slots = await service.get_doctor_availability(doctor_id, date)
    return {"date": date, "available_slots": slots}

@router.post("/appointments", response_model=Appointment, status_code=201)
async def create_appointment(
    appointment: AppointmentCreate,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
) -> Appointment:
    """Book a new appointment"""
    try:
        return await service.book_appointment(appointment, current_user_id)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    except PermissionError as e:
        raise HTTPException(status_code=403, detail=str(e))

@router.get("/appointments/{appointment_id}", response_model=Appointment)
async def get_appointment(
    appointment_id: str,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
) -> Appointment:
    """Get appointment details"""
    appointment = await service.get_appointment(appointment_id)
    if not appointment:
        raise HTTPException(status_code=404, detail="Appointment not found")
    # Authorize: patient (by patient_id) or doctor (by doctor.user_id)
    if current_user_id == appointment.patient_id:
        return appointment
    doctor = await service.get_doctor(appointment.doctor_id)
    if doctor and doctor.user_id == current_user_id:
        return appointment
    raise HTTPException(status_code=403, detail="Not authorized to view this appointment")

@router.get("/appointments/patient/{patient_id}", response_model=List[Appointment])
async def get_patient_appointments(
    patient_id: str,
    status: Optional[AppointmentStatus] = None,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
) -> List[Appointment]:
    """Get all appointments for a patient"""
    if patient_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other patients' appointments")
    return await service.get_patient_appointments(patient_id, status)

@router.get("/appointments/doctor/{doctor_id}", response_model=List[Appointment])
async def get_doctor_appointments(
    doctor_id: str,
    status: Optional[AppointmentStatus] = None,
    date: Optional[date] = None,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
) -> List[Appointment]:
    """Get all appointments for a doctor"""
    doctor = await service.get_doctor(doctor_id)
    if not doctor:
        raise HTTPException(status_code=404, detail="Doctor not found")
    if doctor.user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other doctors' appointments")
    return await service.get_doctor_appointments(doctor_id, date)

@router.put("/appointments/{appointment_id}", response_model=Appointment)
async def update_appointment(
    appointment_id: str,
    update_data: AppointmentUpdate,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
) -> Appointment:
    """Update appointment details"""
    try:
        result = await service.update_appointment(appointment_id, update_data, current_user_id)
        if result is None:
            raise HTTPException(status_code=404, detail="Appointment not found")
        return result
    except PermissionError as e:
        raise HTTPException(status_code=403, detail=str(e))
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

@router.post("/appointments/{appointment_id}/cancel")
async def cancel_appointment(
    appointment_id: str,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
) -> dict:
    """Cancel an appointment"""
    try:
        result = await service.cancel_appointment(appointment_id, current_user_id)
        if result is None:
            raise HTTPException(status_code=404, detail="Appointment not found")
        return {"message": "Appointment cancelled successfully"}
    except PermissionError as e:
        raise HTTPException(status_code=403, detail=str(e))
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

@router.post("/appointments/{appointment_id}/confirm")
async def confirm_appointment(
    appointment_id: str,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
) -> dict:
    """Confirm a pending appointment (doctor only)"""
    try:
        result = await service.confirm_appointment(appointment_id, current_user_id)
        if result is None:
            raise HTTPException(status_code=404, detail="Appointment not found")
        return {"message": "Appointment confirmed", "meeting_link": result.meeting_link}
    except PermissionError as e:
        raise HTTPException(status_code=403, detail=str(e))
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

@router.post("/reviews", response_model=Review)
async def create_review(
    review: ReviewCreate,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
) -> Review:
    """Create a review for a completed appointment"""
    try:
        return await service.create_review(review, current_user_id)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    except PermissionError as e:
        raise HTTPException(status_code=403, detail=str(e))

@router.get("/doctors/{doctor_id}/reviews", response_model=List[Review])
async def get_doctor_reviews(
    doctor_id: str,
    current_user_id: str = Depends(telemedicine_user_with_rate_limit)
) -> List[Review]:
    """Get all reviews for a doctor"""
    if await service.get_doctor(doctor_id) is None:
        raise HTTPException(status_code=404, detail="Doctor not found")
    return await service.get_doctor_reviews(doctor_id)

@router.get("/specialties")
async def get_specialties():
    """Get list of available medical specialties"""
    specialties = [
        "General Practitioner", "Cardiology", "Dermatology", "Neurology",
        "Pediatrics", "Psychiatry", "Orthopedics", "Gynecology",
        "Ophthalmology", "ENT", "Dentistry", "Nutrition"
    ]
    return {"specialties": specialties}

# Auto-initialize sample data for in-memory repo upon module import
# (Database repo should be seeded separately in tests)
# No explicit init needed because InMemoryTelemedicineRepository auto-seeds.
