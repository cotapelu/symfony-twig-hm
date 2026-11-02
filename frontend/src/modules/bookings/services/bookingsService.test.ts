import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { BookingsService } from './bookingsService';
import { __testing__ } from './bookingsService';

describe('BookingsService', () => {
  let service: BookingsService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new BookingsService();
  });

  describe('getBookings', () => {
    it('should fetch all bookings', async () => {
      const bookings = [{ id: 'b1', serviceId: 's1' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(bookings) });

      const result = await service.getBookings();

      expect(result).toEqual(bookings);
      expect(global.fetch).toHaveBeenCalledWith('/api/bookings');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getBookings()).rejects.toThrow('Failed to fetch bookings');
    });
  });

  describe('getBooking', () => {
    it('should fetch booking by id', async () => {
      const booking = { id: 'b1', serviceId: 's1' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(booking) });

      const result = await service.getBooking('b1');

      expect(result).toEqual(booking);
      expect(global.fetch).toHaveBeenCalledWith('/api/bookings/b1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getBooking('b1')).rejects.toThrow('Failed to fetch booking');
    });
  });

  describe('createBooking', () => {
    const request = { serviceId: 's1', scheduledDate: '2024-01-01T10:00:00Z', location: '123 St' };

    it('should create booking successfully', async () => {
      const booking = { id: 'b1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(booking) });

      const result = await service.createBooking(request);

      expect(result).toEqual(booking);
      expect(global.fetch).toHaveBeenCalledWith('/api/bookings', expect.objectContaining({
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createBooking(request)).rejects.toThrow('Failed to create booking');
    });
  });

  describe('cancelBooking', () => {
    it('should cancel booking', async () => {
      const booking = { id: 'b1', status: 'cancelled' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(booking) });

      const result = await service.cancelBooking('b1');

      expect(result).toEqual(booking);
      expect(global.fetch).toHaveBeenCalledWith('/api/bookings/b1/cancel', expect.objectContaining({ method: 'PUT' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.cancelBooking('b1')).rejects.toThrow('Failed to cancel booking');
    });
  });

  describe('getAvailableSlots', () => {
    it('should fetch available slots', async () => {
      const slots = [{ startTime: '2024-01-01T10:00:00Z', endTime: '2024-01-01T11:00:00Z' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(slots) });

      const result = await service.getAvailableSlots('s1', '2024-01-01');

      expect(result).toEqual(slots);
      const url = (global.fetch as any).mock.calls[0][0];
      expect(url).toContain('/api/bookings/slots');
      expect(url).toContain('serviceId=s1');
      expect(url).toContain('date=2024-01-01');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getAvailableSlots('s1', '2024-01-01')).rejects.toThrow('Failed to fetch slots');
    });
  });
});
