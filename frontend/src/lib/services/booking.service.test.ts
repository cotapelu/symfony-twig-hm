import { describe, it, expect, vi, beforeEach } from 'vitest';
import { bookingService } from './booking';
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

describe('BookingService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getMyBookings', () => {
    it('fetches user bookings with pagination', async () => {
      const response = { bookings: [{ id: 'b1', userId: 'u1', totalAmount: 100, status: 'confirmed', createdAt: '' }], total: 1, page: 1, limit: 20 };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => response });

      const result = await bookingService.getMyBookings(1, 20);

      expect(getApiUrl).toHaveBeenCalledWith('coreservices', '/api/bookings/my?page=1&limit=20');
      expect(result).toEqual(response);
    });

    it('uses default pagination', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await bookingService.getMyBookings();
      const calledUrl = getApiUrl.mock.calls[0][1];
      expect(calledUrl).toBe('/api/bookings/my?page=1&limit=20');
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(bookingService.getMyBookings()).rejects.toThrow('Failed to fetch bookings');
    });
  });

  describe('getBookingById', () => {
    it('fetches a specific booking', async () => {
      const booking = { id: 'b1', userId: 'u1', totalAmount: 100, status: 'confirmed', createdAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => booking });

      const result = await bookingService.getBookingById('b1');

      expect(getApiUrl).toHaveBeenCalledWith('coreservices', '/api/bookings/b1');
      expect(result).toEqual(booking);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(bookingService.getBookingById('b1')).rejects.toThrow('Failed to fetch booking');
    });
  });

  describe('createBooking', () => {
    it('creates a new booking', async () => {
      const newBooking = { id: 'b2', userId: 'u1', totalAmount: 200, status: 'pending', createdAt: '' };
      const request = { serviceId: 's1', serviceType: 'hotel' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => newBooking });

      const result = await bookingService.createBooking(request);

      expect(getApiUrl).toHaveBeenCalledWith('coreservices', '/api/bookings');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify(request),
      });
      expect(result).toEqual(newBooking);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(bookingService.createBooking({ serviceId: 's1', serviceType: 'tour' })).rejects.toThrow('Failed to create booking');
    });
  });

  describe('cancelBooking', () => {
    it('cancels a booking', async () => {
      const booking = { id: 'b1', status: 'cancelled' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => booking });

      const result = await bookingService.cancelBooking('b1');

      expect(getApiUrl).toHaveBeenCalledWith('coreservices', '/api/bookings/b1/cancel');
      expect(result).toEqual(booking);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(bookingService.cancelBooking('b1')).rejects.toThrow('Failed to cancel booking');
    });
  });
});
