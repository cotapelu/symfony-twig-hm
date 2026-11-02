import { describe, it, expect, vi, beforeEach } from 'vitest';
import { RideService } from './rideService';

global.fetch = vi.fn();

describe('RideService', () => {
  let service: RideService;
  beforeEach(() => { service = new RideService(); vi.clearAllMocks(); });
  const mockResponse = (data: any, ok = true) => ({ ok, json: vi.fn().mockResolvedValue(data) });

  describe('createRide', () => {
    it('should create ride', async () => {
      const ride = { id: 'r1' }; (global.fetch as any).mockResolvedValue(mockResponse(ride));
      const req = { pickup: 'loc1', dropoff: 'loc2' };
      const res = await service.createRide(req);
      expect(res).toEqual(ride);
      expect(fetch).toHaveBeenCalledWith('/api/ride', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(req) });
    });
    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createRide({ pickup: 'a', dropoff: 'b' })).rejects.toThrow('Failed to create ride');
    });
  });

  describe('getRide', () => {
    it('should fetch ride by id', async () => {
      const ride = { id: 'r1' }; (global.fetch as any).mockResolvedValue(mockResponse(ride));
      const res = await service.getRide('r1');
      expect(res).toEqual(ride);
      expect(fetch).toHaveBeenCalledWith('/api/ride/r1');
    });
    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getRide('r1')).rejects.toThrow('Failed to fetch');
    });
  });

  describe('getRides', () => {
    it('should fetch all rides', async () => {
      const rides = [{ id: 'r1' }]; (global.fetch as any).mockResolvedValue(mockResponse(rides));
      const res = await service.getRides();
      expect(res).toEqual(rides);
      expect(fetch).toHaveBeenCalledWith('/api/ride');
    });
    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getRides()).rejects.toThrow('Failed to fetch');
    });
  });

  describe('cancelRide', () => {
    it('should cancel ride', async () => {
      const result = { id: 'r1', cancelled: true }; (global.fetch as any).mockResolvedValue(mockResponse(result));
      const res = await service.cancelRide('r1');
      expect(res).toEqual(result);
      expect(fetch).toHaveBeenCalledWith('/api/ride/r1/cancel', { method: 'POST' });
    });
    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.cancelRide('r1')).rejects.toThrow('Failed to cancel');
    });
  });

  describe('rateRide', () => {
    it('should rate ride', async () => {
      const result = { id: 'r1', rating: 5 }; (global.fetch as any).mockResolvedValue(mockResponse(result));
      const req = { rideId: 'r1', rating: 5, comment: 'Great' };
      const res = await service.rateRide(req);
      expect(res).toEqual(result);
      expect(fetch).toHaveBeenCalledWith('/api/ride/r1/rate', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ rating: 5, comment: 'Great' })
      });
    });
    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.rateRide({ rideId: 'r1', rating: 5 })).rejects.toThrow('Failed to rate');
    });
  });
});
