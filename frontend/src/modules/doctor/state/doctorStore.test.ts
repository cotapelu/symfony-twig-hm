import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  doctorStore,
  doctors,
  availableDoctors,
  appointments,
  upcomingAppointments,
  pastAppointments,
  medicalRecords,
  currentSession,
  doctorLoading,
  doctorError
} from './doctorStore';
import { doctorService } from '../services/doctorService';
import type { Doctor, Appointment, MedicalRecord, TelemedicineSession } from '../contracts/doctorTypes';

function getCurrentState() {
  let current: any;
  const unsub = doctorStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();
const future = new Date(Date.now() + 86400000);
const past = new Date(Date.now() - 86400000);

const mockDoctor = (overrides: Partial<Doctor> = {}): Doctor => ({
  id: `doc-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-doc',
  name: 'Dr. Jane',
  title: 'MD',
  specialty: 'cardiology',
  hospital: 'General Hospital',
  rating: 4.8,
  reviewCount: 100,
  experience: 10,
  education: ['Harvard Med'],
  certifications: ['Board Certified'],
  languages: ['English'],
  bio: 'Experienced cardiologist',
  consultationFee: 100,
  available: true,
  ...overrides
});

const mockAppointment = (overrides: Partial<Appointment> = {}): Appointment => ({
  id: `appt-${Math.random().toString(36).substr(2, 5)}`,
  patientId: 'user-pat',
  doctorId: 'doc-1',
  doctorName: 'Dr. Jane',
  type: 'consultation' as const,
  status: 'confirmed' as const,
  scheduledAt: future.toISOString(),
  duration: 30,
  symptoms: 'Chest pain',
  createdAt: now.toISOString(),
  ...overrides
});

const mockMedicalRecord = (overrides: Partial<MedicalRecord> = {}): MedicalRecord => ({
  id: `rec-${Math.random().toString(36).substr(2, 5)}`,
  patientId: 'user-pat',
  doctorId: 'doc-1',
  diagnosis: 'Hypertension',
  prescription: 'Lisinopril',
  notes: 'Follow up in 1 month',
  createdAt: now.toISOString(),
  ...overrides
});

const mockTelemedicineSession = (overrides: Partial<TelemedicineSession> = {}): TelemedicineSession => ({
  id: `sess-${Math.random().toString(36).substr(2, 5)}`,
  appointmentId: 'appt-1',
  patientId: 'user-pat',
  doctorId: 'doc-1',
  status: 'waiting' as const,
  startedAt: now.toISOString(),
  roomUrl: 'https://teleme.example.com/room',
  ...overrides
});

describe('DoctorStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    doctorStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and defaults', () => {
      const state = getCurrentState();
      expect(state.doctors).toEqual([]);
      expect(state.appointments).toEqual([]);
      expect(state.medicalRecords).toEqual([]);
      expect(state.currentSession).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.currentUserId).toBeNull();
      expect(state.pagination).toEqual({ page: 1, pageSize: 10, total: 0, totalPages: 0 });
    });

    it('derived stores', () => {
      let docs: Doctor[] = [], appts: Appointment[] = [], recs: MedicalRecord[] = [], sess: TelemedicineSession | null = null, load = false, err: string | null = null;
      const unsubD = doctors.subscribe(s => { docs = s; });
      const unsubA = appointments.subscribe(s => { appts = s; });
      const unsubR = medicalRecords.subscribe(s => { recs = s; });
      const unsubS = currentSession.subscribe(s => { sess = s; });
      const unsubL = doctorLoading.subscribe(s => { load = s; });
      const unsubE = doctorError.subscribe(s => { err = s; });
      expect(docs).toEqual([]);
      expect(appts).toEqual([]);
      expect(recs).toEqual([]);
      expect(sess).toBeNull();
      expect(load).toBe(false);
      expect(err).toBeNull();
      unsubD(); unsubA(); unsubR(); unsubS(); unsubL(); unsubE();
    });

    it('availableDoctors filters by available flag', () => {
      let avail: Doctor[] = [];
      const unsub = availableDoctors.subscribe(s => { avail = s; });
      expect(avail).toEqual([]);
      unsub();
    });

    it('upcomingAppointments and pastAppointments derived', () => {
      let up: Appointment[] = [], past: Appointment[] = [];
      const unsubU = upcomingAppointments.subscribe(s => { up = s; });
      const unsubP = pastAppointments.subscribe(s => { past = s; });
      expect(up).toEqual([]);
      expect(past).toEqual([]);
      unsubU(); unsubP();
    });
  });

  describe('setCurrentUser', () => {
    it('sets currentUserId', () => {
      doctorStore.setCurrentUser('user-123');
      let state = getCurrentState();
      expect(state.currentUserId).toBe('user-123');
    });
  });

  describe('loadDoctors', () => {
    it('loads doctors with default page and pageSize', async () => {
      const docs = [mockDoctor(), mockDoctor()];
      vi.spyOn(doctorService, 'getDoctors').mockResolvedValue({ doctors: docs, total: docs.length });
      await doctorStore.loadDoctors();
      let state = getCurrentState();
      expect(state.doctors).toEqual(docs);
      expect(state.pagination).toEqual({ page: 1, pageSize: 10, total: docs.length, totalPages: 1 });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads with specialty and custom pagination', async () => {
      const docs = [mockDoctor()];
      vi.spyOn(doctorService, 'getDoctors').mockResolvedValue({ doctors: docs, total: 50 });
      await doctorStore.loadDoctors('cardiology', 2, 20);
      let state = getCurrentState();
      expect(state.pagination.page).toBe(2);
      expect(state.pagination.pageSize).toBe(20);
      expect(state.pagination.total).toBe(50);
      expect(state.pagination.totalPages).toBe(3);
      expect(doctorService.getDoctors).toHaveBeenCalledWith('cardiology', 2, 20);
    });

    it('handles error', async () => {
      vi.spyOn(doctorService, 'getDoctors').mockRejectedValue(new Error('Service down'));
      await doctorStore.loadDoctors();
      let state = getCurrentState();
      expect(state.error).toBe('Service down');
      expect(state.doctors).toEqual([]);
    });
  });

  describe('loadDoctor', () => {
    it('adds new doctor to list if not present', async () => {
      const doc = mockDoctor({ id: 'd1' });
      vi.spyOn(doctorService, 'getDoctor').mockResolvedValue(doc);
      await doctorStore.loadDoctor('d1');
      let state = getCurrentState();
      expect(state.doctors.find(d => d.id === 'd1')).toEqual(doc);
    });

    it('updates doctor if already in list', async () => {
      const original = mockDoctor({ id: 'd1', name: 'Dr. One' });
      const updated = mockDoctor({ id: 'd1', name: 'Dr. Updated' });
      const spy = vi.spyOn(doctorService, 'getDoctor');
      spy.mockResolvedValueOnce(original);
      await doctorStore.loadDoctor('d1');
      spy.mockResolvedValueOnce(updated);
      await doctorStore.loadDoctor('d1');
      let state = getCurrentState();
      expect(state.doctors.find(d => d.id === 'd1')?.name).toBe('Dr. Updated');
    });

    it('handles error', async () => {
      vi.spyOn(doctorService, 'getDoctor').mockRejectedValue(new Error('Not found'));
      try {
        await doctorStore.loadDoctor('missing');
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Not found');
    });
  });

  describe('searchDoctors', () => {
    it('replaces doctors list with search results', async () => {
      const results = [mockDoctor({ name: 'Dr. A' }), mockDoctor({ name: 'Dr. B' })];
      vi.spyOn(doctorService, 'searchDoctors').mockResolvedValue(results);
      await doctorStore.searchDoctors('cardio');
      let state = getCurrentState();
      expect(state.doctors).toEqual(results);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(doctorService, 'searchDoctors').mockRejectedValue(new Error('Search failed'));
      await doctorStore.searchDoctors('');
      let state = getCurrentState();
      expect(state.error).toBe('Search failed');
    });
  });

  describe('appointments loading', () => {
    it('loadMyAppointments fetches my-appointments endpoint', async () => {
      const appts = [mockAppointment()];
      vi.spyOn(doctorService, 'getMyAppointments').mockResolvedValue(appts);
      await doctorStore.loadMyAppointments();
      let state = getCurrentState();
      expect(state.appointments).toEqual(appts);
      expect(doctorService.getMyAppointments).toHaveBeenCalled();
    });

    it('loadAppointments uses getMyAppointments if currentUserId set', async () => {
      doctorStore.setCurrentUser('user-1');
      const appts = [mockAppointment()];
      vi.spyOn(doctorService, 'getMyAppointments').mockResolvedValue(appts);
      vi.spyOn(doctorService, 'getAppointments');
      await doctorStore.loadAppointments();
      let state = getCurrentState();
      expect(state.appointments).toEqual(appts);
      expect(doctorService.getMyAppointments).toHaveBeenCalled();
      expect(doctorService.getAppointments).not.toHaveBeenCalled();
    });

    it('loadAppointments uses getAppointments if no currentUserId', async () => {
      // currentUserId remains null
      const appts = [mockAppointment()];
      vi.spyOn(doctorService, 'getAppointments').mockResolvedValue(appts);
      await doctorStore.loadAppointments();
      let state = getCurrentState();
      expect(state.appointments).toEqual(appts);
      expect(doctorService.getAppointments).toHaveBeenCalled();
      expect(doctorService.getMyAppointments).not.toHaveBeenCalled();
    });

    it('loadAppointment adds/updates appointment and checks authorization', async () => {
      doctorStore.setCurrentUser('user-1');
      const appt = mockAppointment({ id: 'a1', patientId: 'user-1', doctorId: 'd1' });
      vi.spyOn(doctorService, 'getAppointment').mockResolvedValue(appt);
      await doctorStore.loadAppointment('a1');
      let state = getCurrentState();
      expect(state.appointments.find(a => a.id === 'a1')).toEqual(appt);
      // Now test unauthorized: different patientId
      const otherAppt = mockAppointment({ id: 'a2', patientId: 'other' });
      vi.spyOn(doctorService, 'getAppointment').mockResolvedValue(otherAppt);
      try {
        await doctorStore.loadAppointment('a2');
      } catch (e: any) {
        expect(e.message).toBe('Unauthorized: You can only access your own appointments');
      }
      // state unchanged
      state = getCurrentState();
      expect(state.appointments.find(a => a.id === 'a2')).toBeUndefined();
      expect(state.error).toContain('Unauthorized');
    });
  });

  describe('appointment operations', () => {
    beforeEach(async () => {
      doctorStore.setCurrentUser('user-1');
      const appts = [mockAppointment({ id: 'a1', status: 'confirmed' })];
      vi.spyOn(doctorService, 'getMyAppointments').mockResolvedValue(appts);
      await doctorStore.loadMyAppointments();
    });

    it('bookAppointment adds new appointment', async () => {
      const newAppt = mockAppointment({ id: 'a2' });
      vi.spyOn(doctorService, 'bookAppointment').mockResolvedValue(newAppt);
      const result = await doctorStore.bookAppointment('d1', 'consultation', future.toISOString(), 'symptoms');
      expect(result).toEqual(newAppt);
      let state = getCurrentState();
      expect(state.appointments.find(a => a.id === 'a2')).toEqual(newAppt);
    });

    it('cancelAppointment updates appointment', async () => {
      const cancelled = mockAppointment({ id: 'a1', status: 'cancelled' });
      vi.spyOn(doctorService, 'cancelAppointment').mockResolvedValue(cancelled);
      const result = await doctorStore.cancelAppointment('a1', 'changed mind');
      expect(result).toEqual(cancelled);
      let state = getCurrentState();
      expect(state.appointments.find(a => a.id === 'a1')?.status).toBe('cancelled');
    });

    it('rescheduleAppointment updates appointment', async () => {
      const resched = mockAppointment({ id: 'a1', scheduledAt: future.toISOString() });
      vi.spyOn(doctorService, 'rescheduleAppointment').mockResolvedValue(resched);
      const result = await doctorStore.rescheduleAppointment('a1', future.toISOString());
      expect(result).toEqual(resched);
      let state = getCurrentState();
      expect(state.appointments.find(a => a.id === 'a1')?.scheduledAt).toBe(future.toISOString());
    });
  });

  describe('loadMedicalRecords', () => {
    it('loads records based on currentUserId', async () => {
      doctorStore.setCurrentUser('user-1');
      const recs = [mockMedicalRecord()];
      vi.spyOn(doctorService, 'getMyMedicalRecords').mockResolvedValue(recs);
      await doctorStore.loadMedicalRecords();
      let state = getCurrentState();
      expect(state.medicalRecords).toEqual(recs);
      expect(doctorService.getMyMedicalRecords).toHaveBeenCalled();
    });

    it('uses getMedicalRecords if no currentUserId', async () => {
      // currentUserId is null
      const recs = [mockMedicalRecord()];
      vi.spyOn(doctorService, 'getMedicalRecords').mockResolvedValue(recs);
      await doctorStore.loadMedicalRecords();
      let state = getCurrentState();
      expect(state.medicalRecords).toEqual(recs);
      expect(doctorService.getMedicalRecords).toHaveBeenCalled();
    });
  });

  describe('getAvailableSlots', () => {
    it('returns slots array', async () => {
      const slots = ['09:00', '10:00'];
      vi.spyOn(doctorService, 'getAvailableSlots').mockResolvedValue(slots);
      const result = await doctorStore.getAvailableSlots('doc-1', '2025-06-01');
      expect(result).toEqual(slots);
    });

    it('sets error on failure', async () => {
      vi.spyOn(doctorService, 'getAvailableSlots').mockRejectedValue(new Error('No slots'));
      try {
        await doctorStore.getAvailableSlots('doc-1', '2025-06-01');
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('No slots');
    });
  });

  describe('telemedicine', () => {
    it('startTelemedicine sets currentSession', async () => {
      const sess = mockTelemedicineSession();
      vi.spyOn(doctorService, 'startTelemedicine').mockResolvedValue(sess);
      const result = await doctorStore.startTelemedicine({ appointmentId: 'a1' });
      expect(result).toEqual(sess);
      let state = getCurrentState();
      expect(state.currentSession).toEqual(sess);
    });

    it('endTelemedicine clears currentSession', async () => {
      const sess = mockTelemedicineSession();
      vi.spyOn(doctorService, 'startTelemedicine').mockResolvedValue(sess);
      await doctorStore.startTelemedicine({ appointmentId: 'a1' });
      let state = getCurrentState();
      expect(state.currentSession).toBeTruthy();
      vi.spyOn(doctorService, 'endTelemedicine').mockResolvedValue({ ...sess, status: 'completed' as const });
      await doctorStore.endTelemedicine(sess.id);
      state = getCurrentState();
      expect(state.currentSession).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(doctorService, 'getDoctors').mockRejectedValue(new Error('test'));
      try { await doctorStore.loadDoctors(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      doctorStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const docs = [mockDoctor()];
      vi.spyOn(doctorService, 'getDoctors').mockResolvedValue({ doctors: docs, total: docs.length });
      await doctorStore.loadDoctors();
      doctorStore.setCurrentUser('user-1');
      let state = getCurrentState();
      expect(state.doctors.length).toBeGreaterThan(0);
      expect(state.currentUserId).toBe('user-1');
      doctorStore.reset();
      state = getCurrentState();
      expect(state.doctors).toEqual([]);
      expect(state.currentUserId).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({ page: 1, pageSize: 10, total: 0, totalPages: 0 });
    });
  });
});
