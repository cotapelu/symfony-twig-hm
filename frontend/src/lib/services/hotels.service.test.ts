import { describe, it, expect, vi, beforeEach } from 'vitest';
import { hotelsService } from './hotels';
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

describe('HotelsService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('searchHotels', () => {
    it('searches hotels with params', async () => {
      const hotels = [{ id: 'h1', name: 'Grand', price: 100, rating: 4, roomTypes: [] }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ hotels }) });

      const result = await hotelsService.searchHotels({
        city: 'Paris',
        checkIn: '2024-01-01',
        checkOut: '2024-01-05',
        guests: 2,
      });

      expect(getApiUrl).toHaveBeenCalledWith('hotels', '/api/hotels/search');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({
          city: 'Paris',
          checkIn: '2024-01-01',
          checkOut: '2024-01-05',
          guests: 2,
        }),
      });
      expect(result).toEqual(hotels);
    });

    it('includes optional filters', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await hotelsService.searchHotels({
        city: 'NY',
        checkIn: '2024-01-01',
        checkOut: '2024-01-02',
        guests: 1,
        minPrice: 50,
        maxPrice: 200,
        rating: 4,
      });
      const body = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(body).toMatchObject({
        city: 'NY',
        checkIn: '2024-01-01',
        checkOut: '2024-01-02',
        guests: 1,
        minPrice: 50,
        maxPrice: 200,
        rating: 4,
      });
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(
        hotelsService.searchHotels({
          city: '',
          checkIn: '',
          checkOut: '',
          guests: 1,
        })
      ).rejects.toThrow('Failed to search hotels');
    });
  });

  describe('getHotel', () => {
    it('fetches hotel details', async () => {
      const hotel = { id: 'h1', name: 'Hotel', price: 100, rating: 4, roomTypes: [] };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => hotel });

      const result = await hotelsService.getHotel('h1');

      expect(getApiUrl).toHaveBeenCalledWith('hotels', '/api/hotels/h1');
      expect(result).toEqual(hotel);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(hotelsService.getHotel('h1')).rejects.toThrow('Failed to fetch hotel');
    });
  });

  describe('bookHotel', () => {
    it('books a room', async () => {
      const booking = { id: 'b1', hotelId: 'h1', roomTypeId: 'r1', checkIn: '2024-01-01', checkOut: '2024-01-05', guests: 2, totalPrice: 500, status: 'confirmed' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => booking });

      const result = await hotelsService.bookHotel('h1', 'r1', '2024-01-01', '2024-01-05', 2);

      expect(getApiUrl).toHaveBeenCalledWith('hotels', '/api/hotels/book');
      expect(result).toEqual(booking);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(hotelsService.bookHotel('h1', 'r1', '2024-01-01', '2024-01-05', 2)).rejects.toThrow('Failed to book hotel');
    });
  });

  describe('getMyBookings', () => {
    it('fetches user bookings', async () => {
      const bookings = [{ id: 'b1', status: 'confirmed' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ bookings }) });

      const result = await hotelsService.getMyBookings();

      expect(getApiUrl).toHaveBeenCalledWith('hotels', '/api/hotels/bookings/my');
      expect(result).toEqual(bookings);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await hotelsService.getMyBookings();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(hotelsService.getMyBookings()).rejects.toThrow('Failed to fetch bookings');
    });
  });

  describe('cancelBooking', () => {
    it('cancels a booking', async () => {
      const booking = { id: 'b1', status: 'cancelled' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => booking });

      const result = await hotelsService.cancelBooking('b1');

      expect(getApiUrl).toHaveBeenCalledWith('hotels', '/api/hotels/bookings/b1/cancel');
      expect(result).toEqual(booking);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(hotelsService.cancelBooking('b1')).rejects.toThrow('Failed to cancel booking');
    });
  });
});
