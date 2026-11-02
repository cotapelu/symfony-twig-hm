import { describe, it, expect, vi, beforeEach } from 'vitest';
import { HotelsService } from './hotelsService';

// Mock fetch
global.fetch = vi.fn();

describe('HotelsService', () => {
  let service: HotelsService;

  beforeEach(() => {
    service = new HotelsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('searchHotels', () => {
    it('should search hotels with required params', async () => {
      const hotels = [{ id: 'h1', name: 'Grand Hotel' }];
      (global.fetch as any).mockResolvedValue(mockResponse(hotels));
      const request = { location: 'Hanoi', checkIn: '2024-01-01', checkOut: '2024-01-05', guests: 2 };

      const result = await service.searchHotels(request);

      expect(result).toEqual(hotels);
      const [url] = (global.fetch as any).mock.calls[0];
      expect(url).toContain('/api/hotels/search?location=Hanoi');
      expect(url).toContain('checkIn=2024-01-01');
      expect(url).toContain('checkOut=2024-01-05');
      expect(url).toContain('guests=2');
    });

    it('should include optional rooms param', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse([]));
      const request = { location: 'Hanoi', checkIn: '2024-01-01', checkOut: '2024-01-05', guests: 2, rooms: 2 };

      await service.searchHotels(request);

      const [url] = (global.fetch as any).mock.calls[0];
      expect(url).toContain('rooms=2');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.searchHotels({ location: 'Hanoi', checkIn: '2024-01-01', checkOut: '2024-01-05', guests: 1 })).rejects.toThrow('Failed to search hotels');
    });
  });

  describe('getHotel', () => {
    it('should fetch hotel by id', async () => {
      const hotel = { id: 'h1', name: 'Grand' };
      (global.fetch as any).mockResolvedValue(mockResponse(hotel));

      const result = await service.getHotel('h1');

      expect(result).toEqual(hotel);
      expect(fetch).toHaveBeenCalledWith('/api/hotels/h1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getHotel('h1')).rejects.toThrow('Failed to fetch hotel');
    });
  });

  describe('getBookings', () => {
    it('should fetch bookings', async () => {
      const bookings = [{ id: 'b1', hotelId: 'h1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(bookings));

      const result = await service.getBookings();

      expect(result).toEqual(bookings);
      expect(fetch).toHaveBeenCalledWith('/api/hotels/bookings');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getBookings()).rejects.toThrow('Failed to fetch bookings');
    });
  });

  describe('bookHotel', () => {
    it('should create a booking', async () => {
      const booking = { id: 'b1', hotelId: 'h1' };
      (global.fetch as any).mockResolvedValue(mockResponse(booking));
      const req = { hotelId: 'h1', checkIn: '2024-01-01', checkOut: '2024-01-05', guests: 2 };

      const result = await service.bookHotel(req);

      expect(result).toEqual(booking);
      expect(fetch).toHaveBeenCalledWith('/api/hotels/bookings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(req)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.bookHotel({ hotelId: 'h1', checkIn: '2024-01-01', checkOut: '2024-01-05', guests: 2 })).rejects.toThrow('Failed to book hotel');
    });
  });

  describe('cancelBooking', () => {
    it('should cancel a booking', async () => {
      const result = { id: 'b1', cancelled: true };
      (global.fetch as any).mockResolvedValue(mockResponse(result));

      const res = await service.cancelBooking('b1');

      expect(res).toEqual(result);
      expect(fetch).toHaveBeenCalledWith('/api/hotels/bookings/b1/cancel', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.cancelBooking('b1')).rejects.toThrow('Failed to cancel booking');
    });
  });
});
