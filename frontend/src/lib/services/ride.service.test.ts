import { describe, it, expect, vi, beforeEach } from 'vitest';
import { rideHailingService } from './ride';
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

describe('RideHailingService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('requestRide', () => {
    it('requests a new ride', async () => {
      const ride = { id: 'r1', driverId: 'd1', driverName: 'Driver', pickupLocation: 'A', dropoffLocation: 'B', status: 'searching', fare: 25, createdAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ride });

      const result = await rideHailingService.requestRide({
        pickupLocation: 'A',
        dropoffLocation: 'B',
        vehicleType: 'car',
      });

      expect(getApiUrl).toHaveBeenCalledWith('ridehailing', '/api/ride/request');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify({ pickupLocation: 'A', dropoffLocation: 'B', vehicleType: 'car' }),
      });
      expect(result).toEqual(ride);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(rideHailingService.requestRide({
        pickupLocation: 'A',
        dropoffLocation: 'B',
        vehicleType: 'car',
      })).rejects.toThrow('Failed to request ride');
    });
  });

  describe('getCurrentRide', () => {
    it('gets the current active ride', async () => {
      const ride = { id: 'r1', status: 'accepted', fare: 30, pickupLocation: '', dropoffLocation: '', driverId: '', driverName: '', createdAt: '' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ride });

      const result = await rideHailingService.getCurrentRide();

      expect(getApiUrl).toHaveBeenCalledWith('ridehailing', '/api/ride/current');
      expect(result).toEqual(ride);
    });

    it('returns null when no current ride', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => null });
      const result = await rideHailingService.getCurrentRide();
      expect(result).toBeNull();
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(rideHailingService.getCurrentRide()).rejects.toThrow('Failed to get current ride');
    });
  });

  describe('getRideHistory', () => {
    it('fetches ride history', async () => {
      const rides = [{ id: 'r1', status: 'completed', fare: 20, createdAt: '' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ rides }) });

      const result = await rideHailingService.getRideHistory();

      expect(getApiUrl).toHaveBeenCalledWith('ridehailing', '/api/ride/history');
      expect(result).toEqual(rides);
    });

    it('returns empty array if no history', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await rideHailingService.getRideHistory();
      expect(result).toEqual([]);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(rideHailingService.getRideHistory()).rejects.toThrow('Failed to get ride history');
    });
  });

  describe('cancelRide', () => {
    it('cancels a ride', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true });

      await rideHailingService.cancelRide('r1');

      expect(getApiUrl).toHaveBeenCalledWith('ridehailing', '/api/ride/r1/cancel');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
      });
    });

    it('does not throw on failure (fire-and-forget)', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      // cancelRide does not throw; it just calls fetch
      await expect(rideHailingService.cancelRide('r1')).resolves.toBeUndefined();
    });
  });
});
