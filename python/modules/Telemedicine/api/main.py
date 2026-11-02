from fastapi import FastAPI, HTTPException, Depends, Request, Body
from pydantic import BaseModel, validator
from typing import Optional, List
from datetime import datetime
from service import TelemedicineService
from models.schemas import ConsultationType, AppointmentStatus

app = FastAPI(title="Telemedicine API", version="1.0.0")
service = TelemedicineService()

# Authentication dependency
async def get_current_user(request: Request):
	authorization = request.headers.get("Authorization")
	if not authorization or not authorization.startswith("Bearer "):
		raise HTTPException(status_code=401, detail="Authentication required")
	token = authorization.split(" ")[1]
	# In production: validate JWT and extract user_id, role
	return {"user_id": "extracted_user_id", "token": token, "role": "patient"}  # mock

# Authorization helpers
def require_patient(user: dict):
	if user.get("role") != "patient":
		raise HTTPException(status_code=403, detail="Patient access required")

def require_doctor(user: dict):
	if user.get("role") != "doctor":
		raise HTTPException(status_code=403, detail="Doctor access required")

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

class EndConsultationRequest(BaseModel):
    diagnosis: str
    treatment_plan: str
    prescription: str
    notes: Optional[str] = None

@app.post("/api/telemedicine/patients")
async def register_patient(req: RegisterPatientRequest):
    patient = await service.register_patient(req.user_id, req.full_name, 
        date_of_birth=req.date_of_birth, gender=req.gender, phone=req.phone, email=req.email)
    return {"patient_id": patient.id, "message": "Patient registered successfully"}

@app.post("/api/telemedicine/doctors")
async def register_doctor(req: RegisterDoctorRequest):
    doctor = await service.register_doctor(req.user_id, req.full_name, req.specializations,
        req.license_number, req.years_of_experience, req.consultation_fee,
        education=req.education, bio=req.bio)
    return {"doctor_id": doctor.id, "message": "Doctor registered successfully"}

@app.get("/api/telemedicine/doctors")
async def get_doctors(specialization: Optional[str] = None):
    doctors = await service.get_doctors(specialization)
    return {"doctors": [{"id": d.id, "full_name": d.full_name, "specializations": d.specializations,
                        "rating": d.rating, "consultation_fee": d.consultation_fee} for d in doctors]}

@app.get("/api/telemedicine/doctors/{doctor_id}")
async def get_doctor(doctor_id: str):
    doctor = await service.get_doctor_by_id(doctor_id)
    if not doctor:
        raise HTTPException(status_code=404, detail="Doctor not found")
    return doctor

@app.get("/api/telemedicine/doctors/search")
async def search_doctors(query: str):
    doctors = await service.search_doctors(query)
    return {"doctors": [{"id": d.id, "full_name": d.full_name, "specializations": d.specializations,
                        "rating": d.rating} for d in doctors]}

@app.post("/api/telemedicine/slots")
async def create_time_slots(req: CreateSlotsRequest):
    slots = await service.create_time_slots(req.doctor_id, req.date, req.slot_duration_minutes)
    return {"slots": [{"id": s.id, "date": s.date, "start_time": s.start_time, "end_time": s.end_time} for s in slots]}

@app.get("/api/telemedicine/slots/{doctor_id}")
async def get_available_slots(doctor_id: str, date: datetime):
    slots = await service.get_available_slots(doctor_id, date)
    return {"slots": [{"id": s.id, "start_time": s.start_time, "end_time": s.end_time} for s in slots]}

@app.post("/api/telemedicine/appointments")
async def book_appointment(req: BookAppointmentRequest):
    appointment = await service.book_appointment(req.patient_id, req.doctor_id, req.time_slot_id,
        req.appointment_type, req.chief_complaint, req.symptoms)
    return {"appointment_id": appointment.id, "message": "Appointment booked successfully"}

@app.post("/api/telemedicine/appointments/{appointment_id}/confirm")
async def confirm_appointment(appointment_id: str):
    appointment = await service.confirm_appointment(appointment_id)
    return {"appointment_id": appointment.id, "status": appointment.status}

@app.post("/api/telemedicine/appointments/{appointment_id}/start")
async def start_consultation(appointment_id: str):
    session = await service.start_consultation(appointment_id)
    return {"session_id": session.id, "room_id": session.room_id, "token": session.token}

@app.post("/api/telemedicine/appointments/{appointment_id}/end")
async def end_consultation(appointment_id: str, req: EndConsultationRequest):
    record = await service.end_consultation(appointment_id, req.diagnosis, req.treatment_plan,
        req.prescription, req.notes)
    return {"record_id": record.id, "message": "Consultation ended successfully"}

@app.post("/api/telemedicine/appointments/{appointment_id}/rate")
async def rate_appointment(appointment_id: str, req: RateAppointmentRequest):
    appointment = await service.rate_appointment(appointment_id, req.rating, req.review)
    return {"appointment_id": appointment.id, "rating": appointment.rating}

@app.post("/api/telemedicine/appointments/{appointment_id}/cancel")
async def cancel_appointment(appointment_id: str):
    appointment = await service.cancel_appointment(appointment_id)
    return {"appointment_id": appointment.id, "status": appointment.status}

@app.get("/api/telemedicine/patients/{patient_id}/appointments")
async def get_patient_appointments(patient_id: str):
    appointments = await service.get_patient_appointments(patient_id)
    return {"appointments": [{"id": a.id, "doctor_id": a.doctor_id, "status": a.status,
                             "scheduled_at": a.scheduled_at, "type": a.appointment_type} for a in appointments]}

@app.get("/api/telemedicine/patients/{patient_id}/records")
async def get_patient_records(patient_id: str):
    records = await service.get_patient_records(patient_id)
    return {"records": [{"id": r.id, "doctor_id": r.doctor_id, "diagnosis": r.diagnosis,
                        "created_at": r.created_at} for r in records]}

@app.get("/api/telemedicine/patients/{patient_id}")
async def get_patient(patient_id: str):
    patient = await service.get_patient(patient_id)
    if not patient:
        raise HTTPException(status_code=404, detail="Patient not found")
    return patient

@app.put("/api/telemedicine/patients/{patient_id}")
async def update_patient_profile(patient_id: str, req: UpdateProfileRequest):
    data = {k: v for k, v in req.dict().items() if v is not None}
    patient = await service.update_patient_profile(patient_id, **data)
    return {"patient_id": patient.id, "message": "Profile updated successfully"}

@app.get("/api/telemedicine/doctors/{doctor_id}/appointments")
async def get_doctor_appointments(doctor_id: str, date: Optional[datetime] = None):
    appointments = await service.get_doctor_appointments(doctor_id, date)
    return {"appointments": [{"id": a.id, "patient_id": a.patient_id, "status": a.status,
                             "scheduled_at": a.scheduled_at, "type": a.appointment_type} for a in appointments]}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8004)
