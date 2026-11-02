import type { Doctor, Appointment, MedicalRecord, TelemedicineSession, BookAppointmentRequest, StartTelemedicineRequest } from '../contracts/doctorTypes';

const API_BASE = '/api/doctor';

export class DoctorService {
  async getDoctors(specialty?: string, page?: number, pageSize?: number): Promise<{ doctors: Doctor[]; total: number }> {
    let url = `${API_BASE}/doctors`;
    const params = new URLSearchParams();
    if (specialty) params.set('specialty', specialty);
    if (page) params.set('page', String(page));
    if (pageSize) params.set('pageSize', String(pageSize));
    const queryString = params.toString();
    if (queryString) url += `?${queryString}`;
    
    const response = await fetch(url);
    if (!response.ok) throw new Error('Failed to fetch doctors');
    return response.json();
  }

  async getDoctor(id: string): Promise<Doctor> {
    const response = await fetch(`${API_BASE}/doctors/${id}`);
    if (!response.ok) throw new Error('Failed to fetch doctor');
    return response.json();
  }

  async searchDoctors(query: string): Promise<Doctor[]> {
    const response = await fetch(`${API_BASE}/doctors/search?q=${encodeURIComponent(query)}`);
    if (!response.ok) throw new Error('Failed to search doctors');
    return response.json();
  }

  async getMyAppointments(): Promise<Appointment[]> {
    const response = await fetch(`${API_BASE}/my-appointments`);
    if (!response.ok) throw new Error('Failed to fetch my appointments');
    return response.json();
  }

  async getAppointments(): Promise<Appointment[]> {
    const response = await fetch(`${API_BASE}/appointments`);
    if (!response.ok) throw new Error('Failed to fetch appointments');
    return response.json();
  }

  async getAppointment(id: string): Promise<Appointment> {
    const response = await fetch(`${API_BASE}/appointments/${id}`);
    if (!response.ok) throw new Error('Failed to fetch appointment');
    return response.json();
  }

  async bookAppointment(request: BookAppointmentRequest): Promise<Appointment> {
    const response = await fetch(`${API_BASE}/appointments`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to book appointment');
    return response.json();
  }

  async cancelAppointment(id: string, reason?: string): Promise<Appointment> {
    const response = await fetch(`${API_BASE}/appointments/${id}/cancel`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reason })
    });
    if (!response.ok) throw new Error('Failed to cancel appointment');
    return response.json();
  }

  async rescheduleAppointment(id: string, newDate: string): Promise<Appointment> {
    const response = await fetch(`${API_BASE}/appointments/${id}/reschedule`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ newDate })
    });
    if (!response.ok) throw new Error('Failed to reschedule appointment');
    return response.json();
  }

  async getMyMedicalRecords(): Promise<MedicalRecord[]> {
    const response = await fetch(`${API_BASE}/my-medical-records`);
    if (!response.ok) throw new Error('Failed to fetch my medical records');
    return response.json();
  }

  async getMedicalRecords(): Promise<MedicalRecord[]> {
    const response = await fetch(`${API_BASE}/medical-records`);
    if (!response.ok) throw new Error('Failed to fetch medical records');
    return response.json();
  }

  async getAvailableSlots(doctorId: string, date: string): Promise<string[]> {
    const response = await fetch(`${API_BASE}/doctors/${doctorId}/available-slots?date=${date}`);
    if (!response.ok) throw new Error('Failed to fetch available slots');
    return response.json();
  }

  async startTelemedicine(request: StartTelemedicineRequest): Promise<TelemedicineSession> {
    const response = await fetch(`${API_BASE}/telemedicine/start`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to start telemedicine');
    return response.json();
  }

  async endTelemedicine(sessionId: string): Promise<TelemedicineSession> {
    const response = await fetch(`${API_BASE}/telemedicine/${sessionId}/end`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to end telemedicine');
    return response.json();
  }

  async getTelemedicineSession(sessionId: string): Promise<TelemedicineSession> {
    const response = await fetch(`${API_BASE}/telemedicine/${sessionId}`);
    if (!response.ok) throw new Error('Failed to get telemedicine session');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {
    // No module-level cache
  }
};

export const doctorService = new DoctorService();
export default doctorService;
