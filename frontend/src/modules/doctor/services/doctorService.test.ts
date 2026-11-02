import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { DoctorService } from './doctorService';
import { __testing__ } from './doctorService';

describe('DoctorService', () => {
  let service: DoctorService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new DoctorService();
  });

  describe('getDoctors', () => {
    it('should fetch all doctors without filters', async () => {
      const result = { doctors: [{ id: 'd1', name: 'Dr. Who' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getDoctors();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/doctors');
    });

    it('should filter by specialty', async () => {
      const result = { doctors: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getDoctors('cardiology');

      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/doctors?specialty=cardiology');
    });

    it('should include pagination params', async () => {
      const result = { doctors: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getDoctors(undefined, 2, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/doctors?page=2&pageSize=50');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getDoctors()).rejects.toThrow('Failed to fetch doctors');
    });
  });

  describe('getDoctor', () => {
    it('should fetch doctor by id', async () => {
      const doctor = { id: 'd1', name: 'Dr. Who' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(doctor) });

      const result = await service.getDoctor('d1');

      expect(result).toEqual(doctor);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/doctors/d1');
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getDoctor('d1')).rejects.toThrow('Failed to fetch doctor');
    });
  });

  describe('searchDoctors', () => {
    it('should search doctors', async () => {
      const doctors = [{ id: 'd1' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(doctors) });

      const result = await service.searchDoctors('test');

      expect(result).toEqual(doctors);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/doctors/search?q=test');
    });

    it('should encode query', async () => {
      const doctors = [];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(doctors) });

      await service.searchDoctors('a b');

      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/doctors/search?q=a%20b');
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.searchDoctors('q')).rejects.toThrow('Failed to search doctors');
    });
  });

  describe('getMyAppointments', () => {
    it('should fetch my appointments', async () => {
      const appointments = [{ id: 'a1' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(appointments) });

      const result = await service.getMyAppointments();

      expect(result).toEqual(appointments);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/my-appointments');
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getMyAppointments()).rejects.toThrow('Failed to fetch my appointments');
    });
  });

  describe('getAppointments', () => {
    it('should fetch all appointments', async () => {
      const appointments = [{ id: 'a1' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(appointments) });

      const result = await service.getAppointments();

      expect(result).toEqual(appointments);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/appointments');
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getAppointments()).rejects.toThrow('Failed to fetch appointments');
    });
  });

  describe('getAppointment', () => {
    it('should fetch appointment by id', async () => {
      const appointment = { id: 'a1' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(appointment) });

      const result = await service.getAppointment('a1');

      expect(result).toEqual(appointment);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/appointments/a1');
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getAppointment('a1')).rejects.toThrow('Failed to fetch appointment');
    });
  });

  describe('bookAppointment', () => {
    const request = { doctorId: 'd1', datetime: '2024-01-01T10:00:00Z', reason: 'Checkup' };

    it('should book appointment', async () => {
      const appointment = { id: 'a1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(appointment) });

      const result = await service.bookAppointment(request);

      expect(result).toEqual(appointment);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/appointments', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"doctorId":"d1"')
      }));
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.bookAppointment(request)).rejects.toThrow('Failed to book appointment');
    });
  });

  describe('cancelAppointment', () => {
    it('should cancel without reason', async () => {
      const appointment = { id: 'a1', status: 'cancelled' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(appointment) });

      const result = await service.cancelAppointment('a1');

      expect(result).toEqual(appointment);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/appointments/a1/cancel', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('{}')
      }));
    });

    it('should cancel with reason', async () => {
      const appointment = { id: 'a1', status: 'cancelled' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(appointment) });

      await service.cancelAppointment('a1', 'Busy');

      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/appointments/a1/cancel', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"reason":"Busy"')
      }));
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.cancelAppointment('a1')).rejects.toThrow('Failed to cancel appointment');
    });
  });

  describe('rescheduleAppointment', () => {
    it('should reschedule appointment', async () => {
      const appointment = { id: 'a1', newDate: '2024-01-02T10:00:00Z' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(appointment) });

      const result = await service.rescheduleAppointment('a1', '2024-01-02T10:00:00Z');

      expect(result).toEqual(appointment);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/appointments/a1/reschedule', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"newDate":"2024-01-02T10:00:00Z"')
      }));
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.rescheduleAppointment('a1', 'newtime')).rejects.toThrow('Failed to reschedule appointment');
    });
  });

  describe('getMyMedicalRecords', () => {
    it('should fetch my medical records', async () => {
      const records = [{ id: 'mr1' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(records) });

      const result = await service.getMyMedicalRecords();

      expect(result).toEqual(records);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/my-medical-records');
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getMyMedicalRecords()).rejects.toThrow('Failed to fetch my medical records');
    });
  });

  describe('getMedicalRecords', () => {
    it('should fetch all medical records', async () => {
      const records = [{ id: 'mr1' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(records) });

      const result = await service.getMedicalRecords();

      expect(result).toEqual(records);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/medical-records');
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getMedicalRecords()).rejects.toThrow('Failed to fetch medical records');
    });
  });

  describe('getAvailableSlots', () => {
    it('should fetch available slots', async () => {
      const slots = ['2024-01-01T10:00:00Z', '2024-01-01T11:00:00Z'];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(slots) });

      const result = await service.getAvailableSlots('d1', '2024-01-01');

      expect(result).toEqual(slots);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/doctors/d1/available-slots?date=2024-01-01');
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getAvailableSlots('d1', '2024-01-01')).rejects.toThrow('Failed to fetch available slots');
    });
  });

  describe('startTelemedicine', () => {
    const request = { appointmentId: 'a1', scheduledTime: '2024-01-01T10:00:00Z' };

    it('should start telemedicine session', async () => {
      const session = { id: 't1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(session) });

      const result = await service.startTelemedicine(request);

      expect(result).toEqual(session);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/telemedicine/start', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"appointmentId":"a1"')
      }));
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.startTelemedicine(request)).rejects.toThrow('Failed to start telemedicine');
    });
  });

  describe('endTelemedicine', () => {
    it('should end telemedicine session', async () => {
      const session = { id: 't1', endTime: 'now' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(session) });

      const result = await service.endTelemedicine('t1');

      expect(result).toEqual(session);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/telemedicine/t1/end', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.endTelemedicine('t1')).rejects.toThrow('Failed to end telemedicine');
    });
  });

  describe('getTelemedicineSession', () => {
    it('should fetch telemedicine session', async () => {
      const session = { id: 't1', appointmentId: 'a1' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(session) });

      const result = await service.getTelemedicineSession('t1');

      expect(result).toEqual(session);
      expect(global.fetch).toHaveBeenCalledWith('/api/doctor/telemedicine/t1');
    });

    it('should throw on error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getTelemedicineSession('t1')).rejects.toThrow('Failed to get telemedicine session');
    });
  });
});
