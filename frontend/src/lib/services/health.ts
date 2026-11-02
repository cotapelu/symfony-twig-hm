// Health/Doctor Service - Connects to Python Telemedicine
import { getApiUrl } from '../config/api';

export interface Doctor {
  id: string;
  name: string;
  specialty: string;
  image: string;
  rating: number;
  experience: number;
  hospital: string;
  availability: string[];
}

export interface Appointment {
  id: string;
  doctorId: string;
  doctorName: string;
  patientId: string;
  date: string;
  time: string;
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled';
  symptoms?: string;
  notes?: string;
}

export interface MedicalRecord {
  id: string;
  patientId: string;
  doctorId: string;
  diagnosis: string;
  prescription: string;
  date: string;
}

class HealthService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getDoctors(specialty?: string): Promise<Doctor[]> {
    const url = getApiUrl('telemedicine', `/api/doctors${specialty ? `?specialty=${encodeURIComponent(specialty)}` : ''}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch doctors');
    const data = await response.json();
    return data.doctors || [];
  }

  async getDoctor(id: string): Promise<Doctor> {
    const url = getApiUrl('telemedicine', `/api/doctors/${id}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch doctor');
    return response.json();
  }

  async bookAppointment(doctorId: string, date: string, time: string, symptoms?: string): Promise<Appointment> {
    const url = getApiUrl('telemedicine', '/api/appointments');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ doctorId, date, time, symptoms }),
    });
    if (!response.ok) throw new Error('Failed to book appointment');
    return response.json();
  }

  async getMyAppointments(): Promise<Appointment[]> {
    const url = getApiUrl('telemedicine', '/api/appointments/my');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch appointments');
    const data = await response.json();
    return data.appointments || [];
  }

  async cancelAppointment(id: string): Promise<Appointment> {
    const url = getApiUrl('telemedicine', `/api/appointments/${id}/cancel`);
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
    });
    if (!response.ok) throw new Error('Failed to cancel appointment');
    return response.json();
  }

  async getMedicalRecords(): Promise<MedicalRecord[]> {
    const url = getApiUrl('telemedicine', '/api/medical-records');
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch medical records');
    const data = await response.json();
    return data.records || [];
  }
}

export const healthService = new HealthService();
