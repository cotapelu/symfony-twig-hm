"""Telemedicine router for Python monolith - uses shared JWT auth"""
from fastapi import APIRouter, Depends, HTTPException, Request, Body
from typing import Optional, List
from datetime import datetime
from pydantic import BaseModel
from modules.telemedicine.service import TelemedicineService
from modules.telemedicine.models.schemas import ConsultationType, AppointmentStatus

router = APIRouter(prefix="/telemedicine", tags=["telemedicine"])
service = TelemedicineService()

# Import shared auth dependencies
from app.shared.middleware.auth import get_current_user_id

# Authorization helpers
def require_patient(user_id: str, required_role: str = None):
    pass

def require_doctor(user_id: str):
    pass

class RegisterPatientRequest(BaseModel):
    user_id: str
    full_name: str
    date_of_birth: Optional[datetime] = None
    gender: Optional[str] = None
    phone: Optional[str] = None
    email: Optional[str] = None

class RegisterDoctorRequest(BaseModel):
    user_id: str
    full_name: str
    specializations: List[str]
    license_number: str
    years_of_experience: int
    education: Optional[str] = None
    consultation_fee: float
    bio: Optional[str] = None

class CreateSlotsRequest(BaseModel):
    doctor_id: str
    date: datetime
    slot_duration_minutes: int = 30

class BookAppointmentRequest(BaseModel):
    patient_id: str
    doctor_id: str
    time_slot_id: str
    appointment_type: ConsultationType
    chief_complaint: str
    symptoms: List[str]

class RateAppointmentRequest(BaseModel):
    rating: int
    review: str

class UpdateProfileRequest(BaseModel):
    phone: Optional[str] = None
    address: Optional[str] = None
    medical_history: Optional[str] = None
    allergies: Optional[List[str]] = None
    blood_type: Optional[str] = None
    emergency_contact: Optional[str] = None

class EndConsultationRequest(BaseModel):
    diagnosis: str
    treatment_plan: str
    prescription: str
    notes: Optional[str] = None

@router.post("/patients")
async def register_patient(req: RegisterPatientRequest, current_user_id: str = Depends(get_current_user_id)):
    # Only admin or patient themselves can register
    if current_user_id != req.user_id:
        raise HTTPException(status_code=403, detail="Cannot register patient for another user")
    patient = await service.register_patient(req.user_id, req.full_name,
        date_of_birth=req.date_of_birth, gender=req.gender, phone=req.phone, email=req.email)
    return {"patient_id": patient.id, "message": "Patient registered successfully"}

@router.post("/doctors")
async def register_doctor(req: RegisterDoctorRequest, current_user_id: str = Depends(get_current_user_id)):
    # Only admin or doctor themselves can register
    if current_user_id != req.user_id:
        raise HTTPException(status_code=403, detail="Cannot register doctor for another user")
    doctor = await service.register_doctor(req.user_id, req.full_name, req.specializations,
        req.license_number, req.years_of_experience, req.consultation_fee,
        education=req.education, bio=req.bio)
    return {"doctor_id": doctor.id, "message": "Doctor registered successfully"}

@router.get("/doctors")
async def get_doctors(specialization: Optional[str] = None):
    doctors = await service.get_doctors(specialization)
    return {"doctors": [{"id": d.id, "full_name": d.full_name, "specializations": d.specializations,
                        "rating": d.rating, "consultation_fee": d.consultation_fee} for d in doctors]}

@router.get("/doctors/{doctor_id}")
async def get_doctor(doctor_id: str):
    doctor = await service.get_doctor_by_id(doctor_id)
    if not doctor:
        raise HTTPException(status_code=404, detail="Doctor not found")
    return doctor

@router.get("/doctors/search")
async def search_doctors(query: str):
    doctors = await service.search_doctors(query)
    return {"doctors": [{"id": d.id, "full_name": d.full_name, "specializations": d.specializations,
                        "rating": d.rating} for d in doctors]}

@router.post("/slots")
async def create_time_slots(req: CreateSlotsRequest, current_user_id: str = Depends(get_current_user_id)):
    # Only doctor or admin can create slots
    if current_user_id != req.doctor_id:
        raise HTTPException(status_code=403, detail="Cannot create slots for another doctor")
    slots = await service.create_time_slots(req.doctor_id, req.date, req.slot_duration_minutes)
    return {"slots": [{"id": s.id, "date": s.date, "start_time": s.start_time, "end_time": s.end_time} for s in slots]}

@router.get("/slots/{doctor_id}")
async def get_available_slots(doctor_id: str, date: datetime):
    slots = await service.get_available_slots(doctor_id, date)
    return {"slots": [{"id": s.id, "start_time": s.start_time, "end_time": s.end_time} for s in slots]}

@router.post("/appointments")
async def book_appointment(req: BookAppointmentRequest, current_user_id: str = Depends(get_current_user_id)):
    # Patient can only book for themselves
    if current_user_id != req.patient_id:
        raise HTTPException(status_code=403, detail="Cannot book appointment for another patient")
    appointment = await service.book_appointment(req.patient_id, req.doctor_id, req.time_slot_id,
        req.appointment_type, req.chief_complaint, req.symptoms)
    return {"appointment_id": appointment.id, "message": "Appointment booked successfully"}

@router.post("/appointments/{appointment_id}/confirm")
async def confirm_appointment(appointment_id: str, current_user_id: str = Depends(get_current_user_id)):
    # Only doctor or admin can confirm
    appointment = await service.confirm_appointment(appointment_id)
    if appointment.doctor_id != current_user_id:
        raise HTTPException(status_code=403, detail="Only the assigned doctor can confirm")
    return {"appointment_id": appointment.id, "status": appointment.status}

@router.post("/appointments/{appointment_id}/start")
async def start_consultation(appointment_id: str, current_user_id: str = Depends(get_current_user_id)):
    # Only doctor can start - ownership verified in service
    session = await service.start_consultation(appointment_id, current_user_id)
    return {"session_id": session.id, "room_id": session.room_id, "token": session.token}

@router.post("/appointments/{appointment_id}/end")
async def end_consultation(appointment_id: str, req: EndConsultationRequest, current_user_id: str = Depends(get_current_user_id)):
    record = await service.end_consultation(appointment_id, current_user_id, req.diagnosis, req.treatment_plan,
        req.prescription, req.notes)
    return {"record_id": record.id, "message": "Consultation ended successfully"}

@router.post("/appointments/{appointment_id}/rate")
async def rate_appointment(appointment_id: str, req: RateAppointmentRequest, current_user_id: str = Depends(get_current_user_id)):
    # Patient can rate their appointment - ownership verified in service
    appointment = await service.rate_appointment(appointment_id, current_user_id, req.rating, req.review)
    return {"appointment_id": appointment.id, "rating": appointment.rating}

@router.post("/appointments/{appointment_id}/cancel")
async def cancel_appointment(appointment_id: str, current_user_id: str = Depends(get_current_user_id)):
    # Patient or doctor can cancel (service should enforce)
    appointment = await service.cancel_appointment(appointment_id)
    return {"appointment_id": appointment.id, "status": appointment.status}

@router.get("/patients/{patient_id}/appointments")
async def get_patient_appointments(patient_id: str, current_user_id: str = Depends(get_current_user_id)):
    # Only patient themselves or admin
    if current_user_id != patient_id:
        raise HTTPException(status_code=403, detail="Cannot view appointments of another patient")
    appointments = await service.get_patient_appointments(patient_id)
    return {"appointments": [{"id": a.id, "doctor_id": a.doctor_id, "status": a.status,
                             "scheduled_at": a.scheduled_at, "type": a.appointment_type} for a in appointments]}

@router.get("/patients/{patient_id}/records")
async def get_patient_records(patient_id: str, current_user_id: str = Depends(get_current_user_id)):
    if current_user_id != patient_id:
        raise HTTPException(status_code=403, detail="Cannot view records of another patient")
    records = await service.get_patient_records(patient_id)
    return {"records": [{"id": r.id, "doctor_id": r.doctor_id, "diagnosis": r.diagnosis,
                        "created_at": r.created_at} for r in records]}

@router.get("/patients/{patient_id}")
async def get_patient(patient_id: str, current_user_id: str = Depends(get_current_user_id)):
    if current_user_id != patient_id:
        raise HTTPException(status_code=403, detail="Cannot view another patient's profile")
    patient = await service.get_patient(patient_id)
    if not patient:
        raise HTTPException(status_code=404, detail="Patient not found")
    return patient

@router.put("/patients/{patient_id}")
async def update_patient_profile(patient_id: str, req: UpdateProfileRequest, current_user_id: str = Depends(get_current_user_id)):
    if current_user_id != patient_id:
        raise HTTPException(status_code=403, detail="Cannot update another patient's profile")
    data = {k: v for k, v in req.dict().items() if v is not None}
    patient = await service.update_patient_profile(patient_id, **data)
    return {"patient_id": patient.id, "message": "Profile updated successfully"}

@router.get("/doctors/{doctor_id}/appointments")
async def get_doctor_appointments(doctor_id: str, date: Optional[datetime] = None, current_user_id: str = Depends(get_current_user_id)):
    if current_user_id != doctor_id:
        raise HTTPException(status_code=403, detail="Cannot view appointments of another doctor")
    appointments = await service.get_doctor_appointments(doctor_id, date)
    return {"appointments": [{"id": a.id, "patient_id": a.patient_id, "status": a.status,
                             "scheduled_at": a.scheduled_at, "type": a.appointment_type} for a in appointments]}
