import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  tripPlannerStore,
  tripPlans,
  tripPlannerLoading,
  tripPlannerPagination
} from './tripPlannerStore';
import { tripPlannerService } from '../services/tripPlannerService';
import type { TripPlan, PaginatedResponse } from '../contracts/tripPlannerTypes';

function getCurrentState() {
  let current: any;
  const unsub = tripPlannerStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockTrip = (overrides: Partial<TripPlan> = {}): TripPlan => ({
  id: `trip-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  name: 'Vacation',
  destinations: ['Paris', 'Rome'],
  startDate: '2025-06-01',
  endDate: '2025-06-15',
  activities: ['sightseeing', 'dining'],
  status: 'planning',
  ...overrides
});

const mockResponse = (items: TripPlan[], page: number, limit: number, total: number): PaginatedResponse<TripPlan> => ({
  items,
  total,
  page,
  limit,
  totalPages: Math.ceil(total / limit)
});

describe('TripPlannerStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    tripPlannerStore.reset();
  });

  describe('initial state', () => {
    it('has empty trips, null currentTrip, default pagination', () => {
      const state = getCurrentState();
      expect(state.trips).toEqual([]);
      expect(state.currentTrip).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });

    it('derived tripPlans matches state.trips', () => {
      let tripsList: TripPlan[] = [];
      const unsub = tripPlans.subscribe(s => { tripsList = s; });
      expect(tripsList).toEqual([]);
      unsub();
    });

    it('derived tripPlannerLoading matches state.loading', () => {
      let load = false;
      const unsub = tripPlannerLoading.subscribe(s => { load = s; });
      expect(load).toBe(false);
      unsub();
    });

    it('derived tripPlannerPagination matches state.pagination', () => {
      let pag: any = null;
      const unsub = tripPlannerPagination.subscribe(s => { pag = s; });
      expect(pag).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
      unsub();
    });
  });

  describe('loadTrips', () => {
    it('loads trips with default pagination', async () => {
      const trips = [mockTrip(), mockTrip()];
      const resp = mockResponse(trips, 1, 20, trips.length);
      vi.spyOn(tripPlannerService, 'getTrips').mockResolvedValue(resp);
      await tripPlannerStore.loadTrips();
      const state = getCurrentState();
      expect(state.trips).toEqual(trips);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: trips.length, totalPages: 1 });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads with custom page and limit', async () => {
      const trips = [mockTrip()];
      const resp = mockResponse(trips, 3, 10, 50);
      vi.spyOn(tripPlannerService, 'getTrips').mockResolvedValue(resp);
      await tripPlannerStore.loadTrips(3, 10);
      const state = getCurrentState();
      expect(state.pagination.page).toBe(3);
      expect(state.pagination.limit).toBe(10);
      expect(state.pagination.total).toBe(50);
      expect(state.pagination.totalPages).toBe(5);
    });

    it('handles error', async () => {
      vi.spyOn(tripPlannerService, 'getTrips').mockRejectedValue(new Error('Service down'));
      await tripPlannerStore.loadTrips();
      const state = getCurrentState();
      expect(state.error).toBe('Service down');
      expect(state.trips).toEqual([]);
    });
  });

  describe('loadTrip', () => {
    it('loads trip and sets as currentTrip', async () => {
      const trip = mockTrip({ id: 't1' });
      vi.spyOn(tripPlannerService, 'getTrip').mockResolvedValue(trip);
      await tripPlannerStore.loadTrip('t1');
      const state = getCurrentState();
      expect(state.currentTrip).toEqual(trip);
    });

    it('handles error', async () => {
      vi.spyOn(tripPlannerService, 'getTrip').mockRejectedValue(new Error('Not found'));
      try {
        await tripPlannerStore.loadTrip('missing');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Not found');
    });
  });

  describe('createTrip', () => {
    it('creates trip and adds to trips list', async () => {
      const newTrip = mockTrip({ id: 'new-1' });
      vi.spyOn(tripPlannerService, 'createTrip').mockResolvedValue(newTrip);
      const result = await tripPlannerStore.createTrip({ destination: 'Paris', startDate: '2025-06-01', endDate: '2025-06-15', activities: [] });
      expect(result).toEqual(newTrip);
      let state = getCurrentState();
      expect(state.trips[0]).toEqual(newTrip);
    });

    it('propagates errors', async () => {
      vi.spyOn(tripPlannerService, 'createTrip').mockRejectedValue(new Error('Validation'));
      try {
        await tripPlannerStore.createTrip({ destination: 'Paris', startDate: '2025-06-01', endDate: '2025-06-15', activities: [] });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Validation');
    });
  });

  describe('deleteTrip', () => {
    it('removes trip from list', async () => {
      const trip = mockTrip({ id: 't1' });
      vi.spyOn(tripPlannerService, 'getTrips').mockResolvedValue(mockResponse([trip], 1, 20, 1));
      await tripPlannerStore.loadTrips();
      let state = getCurrentState();
      expect(state.trips.some(t => t.id === 't1')).toBe(true);
      vi.spyOn(tripPlannerService, 'deleteTrip').mockResolvedValue(undefined);
      await tripPlannerStore.deleteTrip('t1');
      state = getCurrentState();
      expect(state.trips.find(t => t.id === 't1')).toBeUndefined();
    });

    it('handles error', async () => {
      vi.spyOn(tripPlannerService, 'getTrips').mockResolvedValue(mockResponse([mockTrip()], 1, 20, 1));
      await tripPlannerStore.loadTrips();
      vi.spyOn(tripPlannerService, 'deleteTrip').mockRejectedValue(new Error('Delete failed'));
      try {
        await tripPlannerStore.deleteTrip('t1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Delete failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(tripPlannerService, 'getTrips').mockRejectedValue(new Error('test'));
      try { await tripPlannerStore.loadTrips(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      tripPlannerStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const trips = [mockTrip()];
      const resp = mockResponse(trips, 1, 20, trips.length);
      vi.spyOn(tripPlannerService, 'getTrips').mockResolvedValue(resp);
      await tripPlannerStore.loadTrips();
      vi.spyOn(tripPlannerService, 'getTrip').mockResolvedValue(trips[0]);
      await tripPlannerStore.loadTrip('t1');
      let state = getCurrentState();
      expect(state.trips.length).toBeGreaterThan(0);
      expect(state.currentTrip).toBeTruthy();
      tripPlannerStore.reset();
      state = getCurrentState();
      expect(state.trips).toEqual([]);
      expect(state.currentTrip).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });
  });
});
