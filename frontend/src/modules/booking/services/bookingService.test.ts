import { describe, it, expect, vi, beforeEach } from 'vitest';
import { BookingService } from './bookingService';

global.fetch = vi.fn();

describe('BookingService', () => {
  let service: BookingService;

  beforeEach(() => {
    service = new BookingService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getBookings', () => {
    it('should fetch all bookings', async () => {
      const bookings = [{ id: 'b1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(bookings));
      const result = await service.getBookings();
      expect(result).toEqual(bookings);
      expect(fetch).toHaveBeenCalledWith('/api/bookings');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getBookings()).rejects.toThrow('Failed to fetch bookings');
    });
  });

  describe('getBooking', () => {
    it('should fetch booking by id', async () => {
      const booking = { id: 'b1' };
      (global.fetch as any).mockResolvedValue(mockResponse(booking));
      const result = await service.getBooking('b1');
      expect(result).toEqual(booking);
      expect(fetch).toHaveBeenCalledWith('/api/bookings/b1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getBooking('b1')).rejects.toThrow('Failed to fetch booking');
    });
  });

  describe('createBooking', () => {
    it('should create a booking', async () => {
      const created = { id: 'new' };
      (global.fetch as any).mockResolvedValue(mockResponse(created));
      const request = { resourceId: 'r1', resourceType: 'hotel', startDate: '2025-01-01', endDate: '2025-01-05' };
      const result = await service.createBooking(request);
      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/bookings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createBooking({} as any)).rejects.toThrow('Failed to create booking');
    });
  });

  describe('cancelBooking', () => {
    it('should cancel a booking', async () => {
      const cancelled = { id: 'b1', status: 'cancelled' };
      (global.fetch as any).mockResolvedValue(mockResponse(cancelled));
      const result = await service.cancelBooking({ bookingId: 'b1', reason: 'Change of plans' });
      expect(result).toEqual(cancelled);
      expect(fetch).toHaveBeenCalledWith('/api/bookings/b1/cancel', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reason: 'Change of plans' })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.cancelBooking({ bookingId: 'b1', reason: 'x' })).rejects.toThrow('Failed to cancel booking');
    });
  });

  describe('confirmBooking', () => {
    it('should confirm a booking', async () => {
      const confirmed = { id: 'b1', status: 'confirmed' };
      (global.fetch as any).mockResolvedValue(mockResponse(confirmed));
      const result = await service.confirmBooking('b1');
      expect(result).toEqual(confirmed);
      expect(fetch).toHaveBeenCalledWith('/api/bookings/b1/confirm', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.confirmBooking('b1')).rejects.toThrow('Failed to confirm booking');
    });
  });

  describe('getUpcomingBookings', () => {
    it('should fetch upcoming bookings', async () => {
      const bookings = [{ id: 'b2' }];
      (global.fetch as any).mockResolvedValue(mockResponse(bookings));
      const result = await service.getUpcomingBookings();
      expect(result).toEqual(bookings);
      expect(fetch).toHaveBeenCalledWith('/api/bookings?status=confirmed&upcoming=true');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getUpcomingBookings()).rejects.toThrow('Failed to fetch upcoming bookings');
    });
  });

  describe('getPastBookings', () => {
    it('should fetch past bookings', async () => {
      const bookings = [{ id: 'b3' }];
      (global.fetch as any).mockResolvedValue(mockResponse(bookings));
      const result = await service.getPastBookings();
      expect(result).toEqual(bookings);
      expect(fetch).toHaveBeenCalledWith('/api/bookings?status=completed,past=true');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getPastBookings()).rejects.toThrow('Failed to fetch past bookings');
    });
  });
});
