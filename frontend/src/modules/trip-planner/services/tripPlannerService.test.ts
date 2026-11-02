import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

global.fetch = vi.fn();

import { TripPlannerService, setRetryWithBackoff, resetRetryWithBackoff } from './tripPlannerService';

// Suppress console logs
vi.spyOn(console, 'info').mockImplementation(() => {});
vi.spyOn(console, 'warn').mockImplementation(() => {});
vi.spyOn(console, 'error').mockImplementation(() => {});

describe('TripPlannerService', () => {
  let service: TripPlannerService;

  beforeEach(() => {
    // Mock the retry function to execute immediately without delays
    setRetryWithBackoff(vi.fn(async (operation: () => Promise<any>) => {
      return operation();
    }));

    vi.clearAllMocks();
    service = new TripPlannerService();
  });

  afterEach(() => {
    resetRetryWithBackoff();
    vi.restoreAllMocks();
  });

  describe('getTrips', () => {
    it('should fetch trips with default pagination', async () => {
      const result = { items: [{ id: 't1', destination: 'Paris' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getTrips();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/trip-planner?page=1&limit=20');
    });

    it('should fetch with custom pagination', async () => {
      const result = { items: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getTrips(2, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/trip-planner?page=2&limit=50');
    });

    it('should throw on invalid pagination', async () => {
      await expect(service.getTrips(0, 10)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getTrips(1, 0)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getTrips(1, 150)).rejects.toThrow('Invalid pagination parameters');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getTrips()).rejects.toThrow('Failed to fetch trips');
    });
  });

  describe('getTrip', () => {
    it('should fetch trip by id', async () => {
      const trip = { id: 't1', destination: 'Tokyo' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(trip) });

      const result = await service.getTrip('t1');

      expect(result).toEqual(trip);
      expect(global.fetch).toHaveBeenCalledWith('/api/trip-planner/t1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getTrip('t1')).rejects.toThrow('Failed to fetch trip');
    });
  });

  describe('createTrip', () => {
    const trip = { destination: 'London', startDate: '2024-06-01', endDate: '2024-06-07', activities: ['sightseeing'] };

    it('should create trip successfully', async () => {
      const created = { id: 't1', ...trip };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(created) });

      const result = await service.createTrip(trip);

      expect(result).toEqual(created);
      expect(global.fetch).toHaveBeenCalledWith('/api/trip-planner', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"destination":"London"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createTrip(trip)).rejects.toThrow('Failed to create trip');
    });
  });

  describe('updateTrip', () => {
    const updates = { destination: 'Berlin' };
    const updated = { id: 't1', destination: 'Berlin', startDate: '2024-06-01', endDate: '2024-06-07', activities: [] };

    it('should update trip successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(updated) });

      const result = await service.updateTrip('t1', updates);

      expect(result).toEqual(updated);
      expect(global.fetch).toHaveBeenCalledWith('/api/trip-planner/t1', expect.objectContaining({ method: 'PATCH' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.updateTrip('t1', updates)).rejects.toThrow('Failed to update trip');
    });
  });

  describe('deleteTrip', () => {
    it('should delete trip successfully', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.deleteTrip('t1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/trip-planner/t1', expect.objectContaining({ method: 'DELETE' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.deleteTrip('t1')).rejects.toThrow('Failed to delete trip');
    });
  });
});