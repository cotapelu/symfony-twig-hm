import { describe, it, expect, vi, beforeEach } from 'vitest';
import { healthService } from './health';
import { getApiUrl } from '../config/api';

vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

const mockFetch = vi.fn();
global.fetch = mockFetch;

const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('HealthService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getDoctors', () => {
    it('fetches all doctors', async () => {
      const doctors = [{ id: 'd1', name: 'Dr. Who', specialty: 'General', rating: 5, experience: 10, hospital: 'General', availability: [] }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ doctors }) });

      const result = await healthService.getDoctors();

      expect(getApiUrl).toHaveBeenCalledWith('telemedicine', '/api/doctors');
      expect(result).toEqual(doctors);
    });

    it('filters by specialty', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await healthService.getDoctors('Cardiology');
      const url = getApiUrl.mock.calls[0][1];
      expect(url).toContain('specialty=Cardiology');
    });

    it('returns empty array if no doctors', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await healthService.getDoctors();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(healthService.getDoctors()).rejects.toThrow('Failed to fetch doctors');
    });
  });

  describe('getDoctor', () => {
    it('fetches a specific doctor', async () => {
      const doctor = { id: 'd1', name: 'Dr. A', specialty: 'Cardiology', rating: 4, experience: 5, hospital: 'City', availability: [] };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => doctor });

      const result = await healthService.getDoctor('d1');

      expect(getApiUrl).toHaveBeenCalledWith('telemedicine', '/api/doctors/d1');
      expect(result).toEqual(doctor);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(healthService.getDoctor('d1')).rejects.toThrow('Failed to fetch doctor');
    });
  });

  describe('bookAppointment', () => {
    it('books an appointment', async () => {
      const appointment = { id: 'a1', doctorId: 'd1', doctorName: 'Dr. A', patientId: 'p1', date: '2024-01-01', time: '10:00', status: 'confirmed' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => appointment });

      const result = await healthService.bookAppointment('d1', '2024-01-01', '10:00', 'Checkup');

      expect(getApiUrl).toHaveBeenCalledWith('telemedicine', '/api/appointments');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ doctorId: 'd1', date: '2024-01-01', time: '10:00', symptoms: 'Checkup' }),
      });
      expect(result).toEqual(appointment);
    });

    it('books without symptoms', async () => {
      const appointment = { id: 'a1', doctorId: 'd1', doctorName: 'Dr.', patientId: 'p1', date: '2024-01-01', time: '10:00', status: 'pending' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => appointment });

      await healthService.bookAppointment('d1', '2024-01-01', '10:00');

      const body = mockFetch.mock.calls[0][1].body;
      expect(body).toBe(JSON.stringify({ doctorId: 'd1', date: '2024-01-01', time: '10:00' }));
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(healthService.bookAppointment('d1', '2024-01-01', '10:00')).rejects.toThrow('Failed to book appointment');
    });
  });

  describe('getMyAppointments', () => {
    it('fetches user appointments', async () => {
      const appointments = [{ id: 'a1', status: 'confirmed' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ appointments }) });

      const result = await healthService.getMyAppointments();

      expect(getApiUrl).toHaveBeenCalledWith('telemedicine', '/api/appointments/my');
      expect(result).toEqual(appointments);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await healthService.getMyAppointments();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(healthService.getMyAppointments()).rejects.toThrow('Failed to fetch appointments');
    });
  });

  describe('cancelAppointment', () => {
    it('cancels an appointment', async () => {
      const appointment = { id: 'a1', status: 'cancelled' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => appointment });

      const result = await healthService.cancelAppointment('a1');

      expect(getApiUrl).toHaveBeenCalledWith('telemedicine', '/api/appointments/a1/cancel');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
      expect(result).toEqual(appointment);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(healthService.cancelAppointment('a1')).rejects.toThrow('Failed to cancel appointment');
    });
  });

  describe('getMedicalRecords', () => {
    it('fetches medical records', async () => {
      const records = [{ id: 'r1', patientId: 'p1', doctorId: 'd1', diagnosis: 'Flu', prescription: 'Rest', date: '2024-01-01' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ records }) });

      const result = await healthService.getMedicalRecords();

      expect(getApiUrl).toHaveBeenCalledWith('telemedicine', '/api/medical-records');
      expect(result).toEqual(records);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await healthService.getMedicalRecords();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(healthService.getMedicalRecords()).rejects.toThrow('Failed to fetch medical records');
    });
  });
});
