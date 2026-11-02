import { describe, it, expect, vi, beforeEach } from 'vitest';
import { FlightsService } from './flightsService';

global.fetch = vi.fn();

describe('FlightsService', () => {
  let service: FlightsService;

  beforeEach(() => {
    service = new FlightsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('searchFlights', () => {
    it('should search flights with required params', async () => {
      const flights = [{ id: 'f1', route: 'HAN-SGN' }];
      (global.fetch as any).mockResolvedValue(mockResponse(flights));
      const req = { origin: 'HAN', destination: 'SGN', departureDate: '2024-01-01', passengers: 1 };

      const result = await service.searchFlights(req);

      expect(result).toEqual(flights);
      const [url] = (global.fetch as any).mock.calls[0];
      expect(url).toContain('/api/flights/search?origin=HAN');
      expect(url).toContain('destination=SGN');
      expect(url).toContain('departureDate=2024-01-01');
      expect(url).toContain('passengers=1');
    });

    it('should include optional returnDate and class', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse([]));
      const req = { origin: 'HAN', destination: 'SGN', departureDate: '2024-01-01', passengers: 2, returnDate: '2024-01-10', class: 'business' };

      await service.searchFlights(req);

      const [url] = (global.fetch as any).mock.calls[0];
      expect(url).toContain('returnDate=2024-01-10');
      expect(url).toContain('class=business');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.searchFlights({ origin: 'HAN', destination: 'SGN', departureDate: '2024-01-01', passengers: 1 })).rejects.toThrow('Failed to search flights');
    });
  });

  describe('getFlight', () => {
    it('should fetch flight by id', async () => {
      const flight = { id: 'f1' };
      (global.fetch as any).mockResolvedValue(mockResponse(flight));
      const result = await service.getFlight('f1');
      expect(result).toEqual(flight);
      expect(fetch).toHaveBeenCalledWith('/api/flights/f1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getFlight('f1')).rejects.toThrow('Failed to fetch flight');
    });
  });

  describe('getBookings', () => {
    it('should fetch bookings', async () => {
      const bookings = [{ id: 'b1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(bookings));
      const result = await service.getBookings();
      expect(result).toEqual(bookings);
      expect(fetch).toHaveBeenCalledWith('/api/flights/bookings');
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
      expect(fetch).toHaveBeenCalledWith('/api/flights/bookings/b1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getBooking('b1')).rejects.toThrow('Failed to fetch booking');
    });
  });

  describe('bookFlight', () => {
    it('should create a booking', async () => {
      const booking = { id: 'b1' };
      (global.fetch as any).mockResolvedValue(mockResponse(booking));
      const req = { flightId: 'f1', passengerName: 'John' };
      const result = await service.bookFlight(req);
      expect(result).toEqual(booking);
      expect(fetch).toHaveBeenCalledWith('/api/flights/bookings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(req)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.bookFlight({ flightId: 'f1', passengerName: 'John' })).rejects.toThrow('Failed to book flight');
    });
  });

  describe('cancelBooking', () => {
    it('should cancel a booking', async () => {
      const result = { id: 'b1', cancelled: true };
      (global.fetch as any).mockResolvedValue(mockResponse(result));
      const res = await service.cancelBooking('b1');
      expect(res).toEqual(result);
      expect(fetch).toHaveBeenCalledWith('/api/flights/bookings/b1/cancel', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.cancelBooking('b1')).rejects.toThrow('Failed to cancel booking');
    });
  });
});
