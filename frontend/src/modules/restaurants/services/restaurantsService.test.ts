import { describe, it, expect, vi, beforeEach } from 'vitest';
import { RestaurantsService } from './restaurantsService';

// Mock fetch
global.fetch = vi.fn();

describe('RestaurantsService', () => {
  let service: RestaurantsService;

  beforeEach(() => {
    service = new RestaurantsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('search', () => {
    it('should search restaurants with required params', async () => {
      const restaurants = [{ id: 'r1', name: 'Pizza Place' }];
      (global.fetch as any).mockResolvedValue(mockResponse(restaurants));
      const request = { location: 'Hanoi', partySize: 2 };

      const result = await service.search(request);

      expect(result).toEqual(restaurants);
      const [url] = (global.fetch as any).mock.calls[0];
      expect(url).toContain('/api/restaurants?location=Hanoi&partySize=2');
    });

    it('should include optional cuisine, date, time', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse([]));
      const request = { location: 'Hanoi', partySize: 4, cuisine: 'Italian', date: '2024-01-01', time: '19:00' };

      await service.search(request);

      const [url] = (global.fetch as any).mock.calls[0];
      expect(url).toContain('cuisine=Italian');
      expect(url).toContain('date=2024-01-01');
      expect(url).toContain('time=19%3A00'); // colon encoded
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.search({ location: 'Hanoi', partySize: 2 })).rejects.toThrow('Failed to search');
    });
  });

  describe('get', () => {
    it('should fetch restaurant by id', async () => {
      const restaurant = { id: 'r1', name: 'Pizza' };
      (global.fetch as any).mockResolvedValue(mockResponse(restaurant));

      const result = await service.get('r1');

      expect(result).toEqual(restaurant);
      expect(fetch).toHaveBeenCalledWith('/api/restaurants/r1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.get('r1')).rejects.toThrow('Failed to fetch');
    });
  });

  describe('getReservations', () => {
    it('should fetch reservations', async () => {
      const reservations = [{ id: 'res1', restaurantId: 'r1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(reservations));

      const result = await service.getReservations();

      expect(result).toEqual(reservations);
      expect(fetch).toHaveBeenCalledWith('/api/restaurants/reservations');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getReservations()).rejects.toThrow('Failed to fetch');
    });
  });

  describe('reserve', () => {
    it('should create a reservation', async () => {
      const reservation = { id: 'res1', restaurantId: 'r1', time: '19:00' };
      (global.fetch as any).mockResolvedValue(mockResponse(reservation));
      const req = { restaurantId: 'r1', date: '2024-01-01', time: '19:00', partySize: 2 };

      const result = await service.reserve(req);

      expect(result).toEqual(reservation);
      expect(fetch).toHaveBeenCalledWith('/api/restaurants/reservations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(req)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.reserve({ restaurantId: 'r1', date: '2024-01-01', time: '19:00', partySize: 2 })).rejects.toThrow('Failed to reserve');
    });
  });

  describe('cancel', () => {
    it('should cancel a reservation', async () => {
      const result = { id: 'res1', cancelled: true };
      (global.fetch as any).mockResolvedValue(mockResponse(result));

      const res = await service.cancel('res1');

      expect(res).toEqual(result);
      expect(fetch).toHaveBeenCalledWith('/api/restaurants/reservations/res1/cancel', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.cancel('res1')).rejects.toThrow('Failed to cancel');
    });
  });
});
