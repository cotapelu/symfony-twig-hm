import { describe, it, expect, vi, beforeEach } from 'vitest';
import { restaurantsService } from './restaurants';
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

describe('RestaurantsService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('searchRestaurants', () => {
    it('searches restaurants with POST', async () => {
      const restaurants = [{ id: 'r1', name: 'Bistro', rating: 4.5, address: '', city: '', cuisine: [], priceRange: 'medium', openingHours: '', features: [], availableTables: 5 }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ restaurants }) });

      const result = await restaurantsService.searchRestaurants({
        city: 'Hanoi',
        guests: 2,
      });

      expect(getApiUrl).toHaveBeenCalledWith('restaurants', '/api/restaurants/search');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ city: 'Hanoi', guests: 2 }),
      });
      expect(result).toEqual(restaurants);
    });

    it('includes optional filters', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await restaurantsService.searchRestaurants({
        city: 'Saigon',
        cuisine: 'Japanese',
        priceRange: 'high',
        date: '2024-01-01',
        time: '19:00',
        guests: 4,
      });
      const body = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(body).toMatchObject({
        city: 'Saigon',
        cuisine: 'Japanese',
        priceRange: 'high',
        date: '2024-01-01',
        time: '19:00',
        guests: 4,
      });
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(restaurantsService.searchRestaurants({ city: '', guests: 1 })).rejects.toThrow('Failed to search restaurants');
    });
  });

  describe('getRestaurant', () => {
    it('fetches restaurant details', async () => {
      const restaurant = { id: 'r1', name: 'Place', rating: 5, address: '', city: '', cuisine: [], priceRange: 'low', openingHours: '', features: [], availableTables: 10 };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => restaurant });

      const result = await restaurantsService.getRestaurant('r1');

      expect(getApiUrl).toHaveBeenCalledWith('restaurants', '/api/restaurants/r1');
      expect(result).toEqual(restaurant);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(restaurantsService.getRestaurant('r1')).rejects.toThrow('Failed to fetch restaurant');
    });
  });

  describe('getAvailableTables', () => {
    it('fetches available tables for a slot', async () => {
      const tables = [{ id: 't1', restaurantId: 'r1', name: 'Table1', capacity: 4, location: 'indoor', available: true }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ tables }) });

      const result = await restaurantsService.getAvailableTables('r1', '2024-01-01', '19:00', 2);

      expect(getApiUrl).toHaveBeenCalledWith('restaurants', '/api/restaurants/r1/tables?date=2024-01-01&time=19:00&guests=2');
      expect(result).toEqual(tables);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(restaurantsService.getAvailableTables('r1', '2024-01-01', '19:00', 2)).rejects.toThrow('Failed to fetch tables');
    });
  });

  describe('getTimeSlots', () => {
    it('fetches available time slots', async () => {
      const slots = [{ id: 's1', time: '19:00', available: true }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ slots }) });

      const result = await restaurantsService.getTimeSlots('r1', '2024-01-01', 2);

      expect(getApiUrl).toHaveBeenCalledWith('restaurants', '/api/restaurants/r1/timeslots?date=2024-01-01&guests=2');
      expect(result).toEqual(slots);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await restaurantsService.getTimeSlots('r1', '2024-01-01', 2);
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(restaurantsService.getTimeSlots('r1', '2024-01-01', 2)).rejects.toThrow('Failed to fetch time slots');
    });
  });

  describe('bookTable', () => {
    it('books a table', async () => {
      const booking = { id: 'b1', restaurantId: 'r1', tableId: 't1', userId: 'u1', date: '2024-01-01', time: '19:00', guests: 2, status: 'pending' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => booking });

      const result = await restaurantsService.bookTable('r1', 't1', '2024-01-01', '19:00', 2);

      expect(getApiUrl).toHaveBeenCalledWith('restaurants', '/api/restaurants/book');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ restaurantId: 'r1', tableId: 't1', date: '2024-01-01', time: '19:00', guests: 2 }),
      });
      expect(result).toEqual(booking);
    });

    it('includes specialRequests', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await restaurantsService.bookTable('r1', 't1', '2024-01-01', '19:00', 2, 'Window seat');
      const body = mockFetch.mock.calls[0][1].body;
      expect(body).toContain('Window seat');
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(restaurantsService.bookTable('r1', 't1', '2024-01-01', '19:00', 2)).rejects.toThrow('Failed to book table');
    });
  });

  describe('getMyBookings', () => {
    it('fetches user restaurant bookings', async () => {
      const bookings = [{ id: 'b1', restaurantId: 'r1', tableId: 't1', userId: 'u1', date: '2024-01-01', time: '19:00', guests: 2, status: 'confirmed' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ bookings }) });

      const result = await restaurantsService.getMyBookings();

      expect(getApiUrl).toHaveBeenCalledWith('restaurants', '/api/restaurants/bookings/my');
      expect(result).toEqual(bookings);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await restaurantsService.getMyBookings();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(restaurantsService.getMyBookings()).rejects.toThrow('Failed to fetch bookings');
    });
  });

  describe('cancelBooking', () => {
    it('cancels a booking', async () => {
      const booking = { id: 'b1', status: 'cancelled' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => booking });

      const result = await restaurantsService.cancelBooking('b1');

      expect(getApiUrl).toHaveBeenCalledWith('restaurants', '/api/restaurants/bookings/b1/cancel');
      expect(result).toEqual(booking);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(restaurantsService.cancelBooking('b1')).rejects.toThrow('Failed to cancel booking');
    });
  });
});
