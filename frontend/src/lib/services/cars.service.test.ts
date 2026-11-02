import { describe, it, expect, vi, beforeEach } from 'vitest';
import { carsService } from './cars';
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

describe('CarsService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('searchCars', () => {
    it('searches cars with POST', async () => {
      const cars = [{ id: 'c1', name: 'Model S', brand: 'Tesla', type: 'luxury', seats: 5, transmission: 'auto', fuel: 'Electric', pricePerDay: 150, images: [], location: 'SGN', available: true, features: [] }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ cars }) });

      const result = await carsService.searchCars({
        location: 'SGN',
        pickupDate: '2024-01-01',
        returnDate: '2024-01-05',
      });

      expect(getApiUrl).toHaveBeenCalledWith('cars', '/api/cars/search');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({
          location: 'SGN',
          pickupDate: '2024-01-01',
          returnDate: '2024-01-05',
        }),
      });
      expect(result).toEqual(cars);
    });

    it('includes optional filters', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await carsService.searchCars({
        location: 'NYC',
        pickupDate: '2024-02-01',
        returnDate: '2024-02-03',
        carType: 'suv',
        seats: 7,
        maxPrice: 200,
      });
      const body = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(body).toMatchObject({
        location: 'NYC',
        pickupDate: '2024-02-01',
        returnDate: '2024-02-03',
        carType: 'suv',
        seats: 7,
        maxPrice: 200,
      });
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(carsService.searchCars({
        location: '',
        pickupDate: '',
        returnDate: '',
      })).rejects.toThrow('Failed to search cars');
    });
  });

  describe('getCar', () => {
    it('fetches a car by id', async () => {
      const car = { id: 'c1', name: 'Car', brand: 'Brand', type: 'economy', seats: 4, transmission: 'auto', fuel: 'Gas', pricePerDay: 80, images: [], location: 'LA', available: true, features: [] };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => car });

      const result = await carsService.getCar('c1');

      expect(getApiUrl).toHaveBeenCalledWith('cars', '/api/cars/c1');
      expect(result).toEqual(car);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(carsService.getCar('c1')).rejects.toThrow('Failed to fetch car');
    });
  });

  describe('bookCar', () => {
    it('books a car', async () => {
      const booking = { id: 'b1', carId: 'c1', userId: 'u1', pickupLocation: 'SGN', dropoffLocation: 'HAN', pickupDate: '2024-01-01', dropoffDate: '2024-01-05', totalPrice: 400, status: 'pending' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => booking });

      const result = await carsService.bookCar('c1', {
        pickupLocation: 'SGN',
        dropoffLocation: 'HAN',
        pickupDate: '2024-01-01',
        dropoffDate: '2024-01-05',
      });

      expect(getApiUrl).toHaveBeenCalledWith('cars', '/api/cars/book');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({
          carId: 'c1',
          pickupLocation: 'SGN',
          dropoffLocation: 'HAN',
          pickupDate: '2024-01-01',
          dropoffDate: '2024-01-05',
        }),
      });
      expect(result).toEqual(booking);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(carsService.bookCar('c1', {
        pickupLocation: '',
        dropoffLocation: '',
        pickupDate: '',
        dropoffDate: '',
      })).rejects.toThrow('Failed to book car');
    });
  });

  describe('getMyBookings', () => {
    it('fetches user car bookings', async () => {
      const bookings = [{ id: 'b1', carId: 'c1', userId: 'u1', totalPrice: 100, status: 'confirmed', pickupDate: '', dropoffDate: '', pickupLocation: '', dropoffLocation: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ bookings }) });

      const result = await carsService.getMyBookings();

      expect(getApiUrl).toHaveBeenCalledWith('cars', '/api/cars/bookings/my');
      expect(result).toEqual(bookings);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await carsService.getMyBookings();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(carsService.getMyBookings()).rejects.toThrow('Failed to fetch bookings');
    });
  });

  describe('cancelBooking', () => {
    it('cancels a car booking', async () => {
      const booking = { id: 'b1', status: 'cancelled' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => booking });

      const result = await carsService.cancelBooking('b1');

      expect(getApiUrl).toHaveBeenCalledWith('cars', '/api/cars/bookings/b1/cancel');
      expect(result).toEqual(booking);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(carsService.cancelBooking('b1')).rejects.toThrow('Failed to cancel booking');
    });
  });
});
