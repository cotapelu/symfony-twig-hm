from fastapi import FastAPI, HTTPException, Depends, Request
from pydantic import BaseModel
from typing import Optional, List
from datetime import datetime
from service import DoctorBookingService
from auth_utils import get_current_user, require_patient, require_doctor, authorize_user_resource

app = FastAPI(title="Doctor Booking API", version="1.0.0")
service = DoctorBookingService()

class BookRequest(BaseModel):
    patient_id: str
    doctor_id: str
    time_slot_id: str
    appointment_type: str
    chief_complaint: Optional[str] = None
    notes: Optional[str] = None

class RegisterPatientRequest(BaseModel):
    user_id: str
    full_name: str
    phone: str
    date_of_birth: Optional[datetime] = None
    gender: Optional[str] = None
    email: Optional[str] = None
    address: Optional[str] = None
    emergency_contact: Optional[str] = None
    insurance_number: Optional[str] = None
    insurance_provider: Optional[str] = None

class RegisterDoctorRequest(BaseModel):
    hospital_id: str
    department_id: str
    user_id: str
    full_name: str
    specializations: List[str]
    license_number: str
    years_of_experience: int
    consultation_fee: float
    education: Optional[str] = None
    bio: Optional[str] = None

class RegisterHospitalRequest(BaseModel):
    name: str
    address: str
    city: str
    country: str
    phone: str
    email: Optional[str] = None
    website: Optional[str] = None
    specialties: List[str]
    facilities: List[str] = []

class CreateSlotsRequest(BaseModel):
    doctor_id: str
    date: datetime
    slot_duration_minutes: int = 30

class RateRequest(BaseModel):
    rating: int
    review: str

class PaymentRequest(BaseModel):
    transaction_id: str

@app.post("/api/booking/patients")
async def register_patient(req: RegisterPatientRequest, current_user: dict = Depends(get_current_user)):
	# Authorization: users can only register themselves (or admin can register any)
	if current_user.get("role") != "admin" and current_user["user_id"] != req.user_id:
		raise HTTPException(status_code=403, detail="Can only register yourself or admin access required")
	patient = await service.register_patient(req.user_id, req.full_name, req.phone,
        date_of_birth=req.date_of_birth, gender=req.gender, email=req.email, address=req.address,
        emergency_contact=req.emergency_contact, insurance_number=req.insurance_number,
        insurance_provider=req.insurance_provider)
	return {"patient_id": patient.id}

@app.post("/api/booking/doctors")
async def register_doctor(req: RegisterDoctorRequest):
    doctor = await service.register_doctor(req.hospital_id, req.department_id, req.user_id,
        req.full_name, req.specializations, req.license_number, req.years_of_experience,
        req.consultation_fee, education=req.education, bio=req.bio)
    return {"doctor_id": doctor.id}

@app.post("/api/booking/hospitals")
async def register_hospital(req: RegisterHospitalRequest):
    hospital = await service.register_hospital(req.name, req.address, req.city, req.country, req.phone,
        email=req.email, website=req.website, specialties=req.specialities, facilities=req.facilities)
    return {"hospital_id": hospital.id}

@app.get("/api/booking/hospitals")
async def get_hospitals(query: Optional[str] = None):
    hospitals = await service.get_hospitals(query)
    return {"hospitals": [{"id": h.id, "name": h.name, "city": h.city, "rating": h.rating,
                          "specialties": h.specialties} for h in hospitals]}

@app.get("/api/booking/hospitals/{hospital_id}")
async def get_hospital(hospital_id: str):
    hospital = await service.get_hospital(hospital_id)
    if not hospital:
        raise HTTPException(status_code=404, detail="Hospital not found")
    return hospital

@app.get("/api/booking/doctors")
async def get_doctors(specialization: Optional[str] = None):
    doctors = await service.get_doctors(specialization)
    return {"doctors": [{"id": d.id, "full_name": d.full_name, "specializations": d.specializations,
                        "rating": d.rating, "consultation_fee": d.consultation_fee} for d in doctors]}

@app.get("/api/booking/doctors/{doctor_id}")
async def get_doctor(doctor_id: str):
    doctor = await service.get_doctor(doctor_id)
    if not doctor:
        raise HTTPException(status_code=404, detail="Doctor not found")
    return doctor

@app.get("/api/booking/hospitals/{hospital_id}/doctors")
async def get_hospital_doctors(hospital_id: str):
    doctors = await service.get_doctors_by_hospital(hospital_id)
    return {"doctors": [{"id": d.id, "full_name": d.full_name, "specializations": d.specializations,
                        "rating": d.rating} for d in doctors]}

@app.get("/api/booking/doctors/search")
async def search_doctors(query: str, hospital_id: Optional[str] = None):
    doctors = await service.search_doctors(query, hospital_id)
    return {"doctors": [{"id": d.id, "full_name": d.full_name, "specializations": d.specializations,
                        "rating": d.rating} for d in doctors]}

@app.post("/api/booking/slots")
async def create_slots(req: CreateSlotsRequest):
    slots = await service.create_time_slots(req.doctor_id, req.date, req.slot_duration_minutes)
    return {"slots": [{"id": s.id, "start_time": s.start_time, "end_time": s.end_time, "fee": s.fee} for s in slots]}

@app.get("/api/booking/slots/{doctor_id}")
async def get_available_slots(doctor_id: str, date: datetime):
    slots = await service.get_available_slots(doctor_id, date)
    return {"slots": [{"id": s.id, "start_time": s.start_time, "end_time": s.end_time, "fee": s.fee} for s in slots]}

@app.post("/api/booking/appointments")
async def book_appointment(req: BookRequest):
    appointment = await service.book_appointment(req.patient_id, req.doctor_id, req.time_slot_id,
        req.appointment_type, req.chief_complaint, req.notes)
    return {"appointment_id": appointment.id, "final_fee": appointment.final_fee}

@app.post("/api/booking/appointments/{appointment_id}/confirm")
async def confirm_booking(appointment_id: str):
    appointment = await service.confirm_booking(appointment_id)
    return {"appointment_id": appointment.id, "status": appointment.status}

@app.post("/api/booking/appointments/{appointment_id}/pay")
async def process_payment(appointment_id: str, req: PaymentRequest):
    appointment = await service.process_payment(appointment_id, req.transaction_id)
    return {"appointment_id": appointment.id, "payment_status": appointment.payment_status}

@app.post("/api/booking/appointments/{appointment_id}/cancel")
async def cancel_booking(appointment_id: str):
    appointment = await service.cancel_booking(appointment_id)
    return {"appointment_id": appointment.id, "status": appointment.status}

@app.post("/api/booking/appointments/{appointment_id}/rate")
async def rate_appointment(appointment_id: str, req: RateRequest):
    appointment = await service.rate_appointment(appointment_id, req.rating, req.review)
    return {"appointment_id": appointment.id, "rating": appointment.rating}

@app.get("/api/booking/patients/{patient_id}/appointments")
async def get_patient_appointments(patient_id: str):
    appointments = await service.get_patient_bookings(patient_id)
    return {"appointments": [{"id": a.id, "doctor_id": a.doctor_id, "status": a.status,
                             "booking_date": a.booking_date, "fee": a.final_fee,
                             "payment_status": a.payment_status} for a in appointments]}

@app.get("/api/booking/appointments/{appointment_id}")
async def get_appointment_details(appointment_id: str):
    appointment = await service.get_booking_details(appointment_id)
    if not appointment:
        raise HTTPException(status_code=404, detail="Appointment not found")
    return appointment

@app.get("/api/booking/patients/{patient_id}")
async def get_patient(patient_id: str):
    patient = await service.get_patient(patient_id)
    if not patient:
        raise HTTPException(status_code=404, detail="Patient not found")
    return patient

@app.get("/api/booking/doctors/{doctor_id}/today")
async def get_today_appointments(doctor_id: str):
    appointments = await service.get_today_appointments(doctor_id)
    return {"appointments": [{"id": a.id, "patient_id": a.patient_id, "status": a.status,
                             "booking_date": a.booking_date, "type": a.appointment_type} for a in appointments]}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8005)
