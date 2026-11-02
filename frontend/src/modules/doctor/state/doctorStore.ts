import { writable, derived, get } from 'svelte/store';
import type { DoctorState, Doctor, Appointment, MedicalRecord, TelemedicineSession } from '../contracts/doctorTypes';
import { doctorService } from '../services/doctorService';

const initialState: DoctorState = {
  doctors: [],
  appointments: [],
  medicalRecords: [],
  currentSession: null,
  loading: false,
  error: null,
  currentUserId: null,
  pagination: { page: 1, pageSize: 10, total: 0, totalPages: 0 }
};

function createDoctorStore() {
  const { subscribe, set, update } = writable<DoctorState>(initialState);

  return {
    subscribe,

    setCurrentUser(userId: string) {
      update(s => ({ ...s, currentUserId: userId }));
    },

    async loadDoctors(specialty?: string, page?: number, pageSize?: number) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const result = await doctorService.getDoctors(specialty, page, pageSize);
        update(s => ({ 
          ...s, 
          doctors: result.doctors, 
          loading: false,
          pagination: { page: page || 1, pageSize: pageSize || 10, total: result.total, totalPages: Math.ceil(result.total / (pageSize || 10)) }
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadDoctor(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const doctor = await doctorService.getDoctor(id);
        update(s => ({
          ...s,
          doctors: s.doctors.some(d => d.id === id) ? s.doctors.map(d => d.id === id ? doctor : d) : [...s.doctors, doctor],
          loading: false
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async searchDoctors(query: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const doctors = await doctorService.searchDoctors(query);
        update(s => ({ ...s, doctors, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadMyAppointments() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const appointments = await doctorService.getMyAppointments();
        update(s => ({ ...s, appointments, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadAppointments() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const state = get({ subscribe });
        const appointments = state.currentUserId 
          ? await doctorService.getMyAppointments()
          : await doctorService.getAppointments();
        update(s => ({ ...s, appointments, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadAppointment(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const state = get({ subscribe });
        const appointment = await doctorService.getAppointment(id);
        
        if (state.currentUserId && appointment.patientId !== state.currentUserId && appointment.doctorId !== state.currentUserId) {
          throw new Error('Unauthorized: You can only access your own appointments');
        }
        
        update(s => ({
          ...s,
          appointments: s.appointments.some(a => a.id === id) 
            ? s.appointments.map(a => a.id === id ? appointment : a) 
            : [...s.appointments, appointment],
          loading: false
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async bookAppointment(doctorId: string, type: 'consultation' | 'followup' | 'emergency', scheduledAt: string, symptoms?: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const appointment = await doctorService.bookAppointment({ doctorId, type, scheduledAt, symptoms });
        update(s => ({ ...s, appointments: [...s.appointments, appointment], loading: false }));
        return appointment;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async cancelAppointment(id: string, reason?: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const appointment = await doctorService.cancelAppointment(id, reason);
        update(s => ({
          ...s,
          appointments: s.appointments.map(a => a.id === id ? appointment : a),
          loading: false
        }));
        return appointment;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async rescheduleAppointment(id: string, newDate: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const appointment = await doctorService.rescheduleAppointment(id, newDate);
        update(s => ({
          ...s,
          appointments: s.appointments.map(a => a.id === id ? appointment : a),
          loading: false
        }));
        return appointment;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadMedicalRecords() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const state = get({ subscribe });
        const medicalRecords = state.currentUserId 
          ? await doctorService.getMyMedicalRecords()
          : await doctorService.getMedicalRecords();
        update(s => ({ ...s, medicalRecords, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async getAvailableSlots(doctorId: string, date: string) {
      try {
        return await doctorService.getAvailableSlots(doctorId, date);
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message }));
        throw error;
      }
    },

    async startTelemedicine(appointmentId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const session = await doctorService.startTelemedicine({ appointmentId });
        update(s => ({ ...s, currentSession: session, loading: false }));
        return session;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async endTelemedicine(sessionId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        await doctorService.endTelemedicine(sessionId);
        update(s => ({ ...s, currentSession: null, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const doctorStore = createDoctorStore();

export const doctors = derived(doctorStore, $s => $s.doctors);
export const availableDoctors = derived(doctorStore, $s => $s.doctors.filter(d => d.available));
export const appointments = derived(doctorStore, $s => $s.appointments);
export const upcomingAppointments = derived(doctorStore, $s =>
  $s.appointments.filter(a => a.status === 'confirmed' && new Date(a.scheduledAt) > new Date())
);
export const pastAppointments = derived(doctorStore, $s =>
  $s.appointments.filter(a => a.status === 'completed' || new Date(a.scheduledAt) < new Date())
);
export const medicalRecords = derived(doctorStore, $s => $s.medicalRecords);
export const currentSession = derived(doctorStore, $s => $s.currentSession);
export const doctorLoading = derived(doctorStore, $s => $s.loading);
export const doctorError = derived(doctorStore, $s => $s.error);
