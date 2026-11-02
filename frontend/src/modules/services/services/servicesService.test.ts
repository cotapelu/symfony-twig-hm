import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { ServicesService } from './servicesService';
import { __testing__ } from './servicesService';

describe('ServicesService', () => {
  let service: ServicesService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new ServicesService();
  });

  describe('getServices', () => {
    it('should fetch services', async () => {
      const services = [{ id: 's1', name: 'Cleaning' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(services) });

      const result = await service.getServices();

      expect(result).toEqual(services);
      expect(global.fetch).toHaveBeenCalledWith('/api/services');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getServices()).rejects.toThrow('Failed to fetch services');
    });
  });

  describe('getService', () => {
    it('should fetch service by id', async () => {
      const s = { id: 's1', name: 'Service' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(s) });

      const result = await service.getService('s1');

      expect(result).toEqual(s);
      expect(global.fetch).toHaveBeenCalledWith('/api/services/s1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getService('s1')).rejects.toThrow('Failed to fetch service');
    });
  });

  describe('createBooking', () => {
    const request = { serviceId: 's1', scheduledDate: '2024-01-01T10:00:00Z', address: '123 St' };

    it('should create booking successfully', async () => {
      const booking = { id: 'b1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(booking) });

      const result = await service.createBooking(request);

      expect(result).toEqual(booking);
      expect(global.fetch).toHaveBeenCalledWith('/api/services/bookings', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"serviceId":"s1"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createBooking(request)).rejects.toThrow('Failed to create booking');
    });
  });

  describe('cancelBooking', () => {
    it('should cancel booking', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.cancelBooking('b1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/services/bookings/b1/cancel', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.cancelBooking('b1')).rejects.toThrow('Failed to cancel booking');
    });
  });

  describe('getBookings', () => {
    it('should fetch all bookings', async () => {
      const bookings = [{ id: 'b1', serviceId: 's1' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(bookings) });

      const result = await service.getBookings();

      expect(result).toEqual(bookings);
      expect(global.fetch).toHaveBeenCalledWith('/api/services/bookings');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getBookings()).rejects.toThrow('Failed to fetch bookings');
    });
  });

  describe('rateService', () => {
    const request = { bookingId: 'b1', rating: 5, comment: 'Great' };

    it('should rate service successfully', async () => {
      const booking = { id: 'b1', rating: 5 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(booking) });

      const result = await service.rateService(request);

      expect(result).toEqual(booking);
      expect(global.fetch).toHaveBeenCalledWith('/api/services/bookings/b1/rate', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"rating":5')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.rateService(request)).rejects.toThrow('Failed to rate service');
    });
  });
});
