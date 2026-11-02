import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  rideStore,
  rides,
  currentRide,
  rideLoading,
  rideError,
  activeRide
} from './rideStore';
import { rideService } from '../services/rideService';
import type { RideRequest, CreateRideRequest } from '../contracts/rideTypes';

function getCurrentState() {
  let current: any;
  const unsub = rideStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date().toISOString();

const mockRide = (overrides: Partial<RideRequest> = {}): RideRequest => ({
  id: `ride-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  pickupLocation: { address: '123 Main', coordinates: { lat: 10, lng: 20 } },
  dropoffLocation: { address: '456 Elm', coordinates: { lat: 30, lng: 40 } },
  status: 'pending' as const,
  estimatedPrice: 25,
  currency: 'USD',
  requestedAt: now,
  ...overrides
});

const mockCreateRequest = (overrides: Partial<CreateRideRequest> = {}): CreateRideRequest => ({
  pickupLocation: { address: '123 Main', coordinates: { lat: 10, lng: 20 } },
  dropoffLocation: { address: '456 Elm', coordinates: { lat: 30, lng: 40 } },
  ...overrides
});

describe('RideStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    rideStore.reset();
  });

  describe('initial state', () => {
    it('has empty rides and null currentRide', () => {
      const state = getCurrentState();
      expect(state.rides).toEqual([]);
      expect(state.currentRide).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let rideList: RideRequest[] = [], curr: RideRequest | null = null, load = false, err: string | null = null;
      const unsubR = rides.subscribe(s => { rideList = s; });
      const unsubC = currentRide.subscribe(s => { curr = s; });
      const unsubL = rideLoading.subscribe(s => { load = s; });
      const unsubE = rideError.subscribe(s => { err = s; });
      expect(rideList).toEqual([]);
      expect(curr).toBeNull();
      expect(load).toBe(false);
      expect(err).toBeNull();
      unsubR(); unsubC(); unsubL(); unsubE();
    });

    it('derived activeRide returns first active ride or null', () => {
      let active: RideRequest | null = null;
      const unsub = activeRide.subscribe(s => { active = s; });
      // no active rides yet -> undefined
      expect(active).toBeUndefined();
      unsub();
    });
  });

  describe('loadRides', () => {
    it('loads all rides', async () => {
      const rideList = [mockRide(), mockRide()];
      vi.spyOn(rideService, 'getRides').mockResolvedValue(rideList);
      await rideStore.loadRides();
      const state = getCurrentState();
      expect(state.rides).toEqual(rideList);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(rideService, 'getRides').mockReturnValue(p as any);
      const loadPromise = rideStore.loadRides();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockRide()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates rides derived on success', async () => {
      const rideList = [mockRide()];
      vi.spyOn(rideService, 'getRides').mockResolvedValue(rideList);
      await rideStore.loadRides();
      let r: RideRequest[] = [];
      const unsub = rides.subscribe(s => { r = s; });
      expect(r).toEqual(rideList);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(rideService, 'getRides').mockResolvedValue([]);
      await rideStore.loadRides();
      expect(getCurrentState().rides).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(rideService, 'getRides').mockRejectedValue(new Error('Service down'));
      await rideStore.loadRides();
      const state = getCurrentState();
      expect(state.error).toBe('Service down');
      expect(state.loading).toBe(false);
      expect(state.rides).toEqual([]);
    });
  });

  describe('loadRide', () => {
    it('loads ride and sets as currentRide', async () => {
      const ride = mockRide({ id: 'r1' });
      vi.spyOn(rideService, 'getRide').mockResolvedValue(ride);
      await rideStore.loadRide('r1');
      const state = getCurrentState();
      expect(state.currentRide).toEqual(ride);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(rideService, 'getRide').mockReturnValue(p as any);
      const loadPromise = rideStore.loadRide('r1');
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise(mockRide({ id: 'r1' }));
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates currentRide derived on success', async () => {
      const ride = mockRide({ id: 'r1' });
      vi.spyOn(rideService, 'getRide').mockResolvedValue(ride);
      await rideStore.loadRide('r1');
      let cr: RideRequest | null = null;
      const unsub = currentRide.subscribe(s => { cr = s; });
      expect(cr).toEqual(ride);
      unsub();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(rideService, 'getRide').mockRejectedValue(new Error('Not found'));
      try {
        await rideStore.loadRide('missing');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
      expect(state.currentRide).toBeNull();
    });
  });

  describe('createRide', () => {
    it('creates ride and adds to list and currentRide', async () => {
      const newRide = mockRide({ id: 'new-r' });
      vi.spyOn(rideService, 'createRide').mockResolvedValue(newRide);
      const result = await rideStore.createRide(mockCreateRequest());
      expect(result).toEqual(newRide);
      let state = getCurrentState();
      expect(state.rides[0]).toEqual(newRide);
      expect(state.currentRide).toEqual(newRide);
    });

    it('propagates errors', async () => {
      vi.spyOn(rideService, 'createRide').mockRejectedValue(new Error('Validation'));
      try {
        await rideStore.createRide(mockCreateRequest());
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Validation');
    });
  });

  describe('cancelRide', () => {
    it('cancels ride and updates list and currentRide', async () => {
      const original = mockRide({ id: 'r1', status: 'accepted' });
      const cancelled = mockRide({ id: 'r1', status: 'cancelled' });
      // Seed a ride
      vi.spyOn(rideService, 'getRides').mockResolvedValue([original]);
      await rideStore.loadRides();
      // Set currentRide
      vi.spyOn(rideService, 'getRide').mockResolvedValue(original);
      await rideStore.loadRide('r1');
      // Mock cancel
      vi.spyOn(rideService, 'cancelRide').mockResolvedValue(cancelled);
      await rideStore.cancelRide('r1');
      let state = getCurrentState();
      expect(state.rides.find(r => r.id === 'r1')?.status).toBe('cancelled');
      expect(state.currentRide?.status).toBe('cancelled');
    });

    it('handles error', async () => {
      vi.spyOn(rideService, 'cancelRide').mockRejectedValue(new Error('Cancel failed'));
      try {
        await rideStore.cancelRide('r1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Cancel failed');
    });
  });

  describe('rateRide', () => {
    it('rates ride and returns updated ride', async () => {
      const original = mockRide({ id: 'r1' });
      const rated = mockRide({ id: 'r1' }); // same but maybe altered
      vi.spyOn(rideService, 'getRides').mockResolvedValue([original]);
      await rideStore.loadRides();
      vi.spyOn(rideService, 'getRide').mockResolvedValue(original);
      await rideStore.loadRide('r1');
      vi.spyOn(rideService, 'rateRide').mockResolvedValue(rated);
      const result = await rideStore.rateRide({ rideId: 'r1', rating: 5 });
      expect(result).toEqual(rated);
      let state = getCurrentState();
      // ride list updated (if id matches)
      expect(state.rides.find(r => r.id === 'r1')).toEqual(rated);
      expect(state.currentRide).toEqual(rated);
    });

    it('handles error', async () => {
      vi.spyOn(rideService, 'rateRide').mockRejectedValue(new Error('Rating failed'));
      try {
        await rideStore.rateRide({ rideId: 'r1', rating: 3 });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Rating failed');
    });
  });

  describe('clearCurrentRide', () => {
    it('clears currentRide', async () => {
      const ride = mockRide();
      vi.spyOn(rideService, 'getRide').mockResolvedValue(ride);
      await rideStore.loadRide('r1');
      let state = getCurrentState();
      expect(state.currentRide).toBeTruthy();
      rideStore.clearCurrentRide();
      state = getCurrentState();
      expect(state.currentRide).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(rideService, 'getRides').mockRejectedValue(new Error('test'));
      try { await rideStore.loadRides(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      rideStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const rides = [mockRide()];
      vi.spyOn(rideService, 'getRides').mockResolvedValue(rides);
      await rideStore.loadRides();
      let state = getCurrentState();
      expect(state.rides.length).toBeGreaterThan(0);
      rideStore.reset();
      state = getCurrentState();
      expect(state.rides).toEqual([]);
      expect(state.currentRide).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('activeRide derived', () => {
    it('returns first active ride or undefined', async () => {
      const active = mockRide({ id: 'a1', status: 'arriving' });
      const completed = mockRide({ id: 'c1', status: 'completed' });
      vi.spyOn(rideService, 'getRides').mockResolvedValue([active, completed]);
      await rideStore.loadRides();
      let act: RideRequest | null = null;
      const unsub = activeRide.subscribe(s => { act = s; });
      expect(act?.id).toBe('a1');
      expect(act).not.toBeNull();
      unsub();
    });

    it('returns undefined if no active rides', async () => {
      const completed = mockRide({ id: 'c1', status: 'completed' });
      vi.spyOn(rideService, 'getRides').mockResolvedValue([completed]);
      await rideStore.loadRides();
      let act: RideRequest | undefined = undefined;
      const unsub = activeRide.subscribe(s => { act = s; });
      expect(act).toBeUndefined();
      unsub();
    });
  });
});
