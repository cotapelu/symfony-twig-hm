import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flightsService } from './flights';
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

describe('FlightsService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('searchFlights', () => {
    it('searches flights with POST', async () => {
      const flights = [{ id: 'f1', airline: 'VA', flightNumber: 'VA123', origin: 'SGN', originCode: 'SGN', destination: 'HAN', destinationCode: 'HAN', departureTime: '2024-01-01T10:00:00Z', arrivalTime: '2024-01-01T12:00:00Z', duration: '2h', price: 500, availableSeats: 100, class: 'economy' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ flights }) });

      const result = await flightsService.searchFlights({
        origin: 'SGN',
        destination: 'HAN',
        departureDate: '2024-01-01',
        passengers: 1,
      });

      expect(getApiUrl).toHaveBeenCalledWith('flights', '/api/flights/search');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({
          origin: 'SGN',
          destination: 'HAN',
          departureDate: '2024-01-01',
          passengers: 1,
        }),
      });
      expect(result).toEqual(flights);
    });

    it('includes optional returnDate and class', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await flightsService.searchFlights({
        origin: 'SGN',
        destination: 'HAN',
        departureDate: '2024-01-01',
        returnDate: '2024-01-10',
        passengers: 2,
        class: 'business',
      });
      const body = JSON.parse(mockFetch.mock.calls[0][1].body);
      expect(body).toMatchObject({
        origin: 'SGN',
        destination: 'HAN',
        departureDate: '2024-01-01',
        returnDate: '2024-01-10',
        passengers: 2,
        class: 'business',
      });
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(flightsService.searchFlights({
        origin: '',
        destination: '',
        departureDate: '',
        passengers: 1,
      })).rejects.toThrow('Failed to search flights');
    });
  });

  describe('getFlight', () => {
    it('fetches flight details', async () => {
      const flight = { id: 'f1', airline: 'VA', flightNumber: 'VA123', origin: 'SGN', originCode: 'SGN', destination: 'HAN', destinationCode: 'HAN', departureTime: '', arrivalTime: '', duration: '2h', price: 500, availableSeats: 100, class: 'economy' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => flight });

      const result = await flightsService.getFlight('f1');

      expect(getApiUrl).toHaveBeenCalledWith('flights', '/api/flights/f1');
      expect(result).toEqual(flight);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(flightsService.getFlight('f1')).rejects.toThrow('Failed to fetch flight');
    });
  });

  describe('bookFlight', () => {
    it('books a flight', async () => {
      const booking = { id: 'b1', flightId: 'f1', passengerId: 'u1', status: 'confirmed' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => booking });

      const result = await flightsService.bookFlight('f1', 'u1');

      expect(getApiUrl).toHaveBeenCalledWith('flights', '/api/flights/book');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ flightId: 'f1', passengerId: 'u1' }),
      });
      expect(result).toEqual(booking);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(flightsService.bookFlight('f1', 'u1')).rejects.toThrow('Failed to book flight');
    });
  });

  describe('getMyBookings', () => {
    it('fetches user flight bookings', async () => {
      const bookings = [{ id: 'b1', flightId: 'f1', passengerId: 'u1', status: 'confirmed' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ bookings }) });

      const result = await flightsService.getMyBookings();

      expect(getApiUrl).toHaveBeenCalledWith('flights', '/api/flights/bookings/my');
      expect(result).toEqual(bookings);
    });

    it('returns empty array if none', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await flightsService.getMyBookings();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(flightsService.getMyBookings()).rejects.toThrow('Failed to fetch bookings');
    });
  });
});
