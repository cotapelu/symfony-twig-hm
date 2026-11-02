import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mapStore, mapLocations, mapLoading } from './mapStore';
import { mapService } from '../services/mapService';
import type { Location } from '../contracts/mapTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = mapStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factory
const mockLocation = (overrides: Partial<Location> = {}): Location => ({
  latitude: 10.0,
  longitude: 20.0,
  name: 'Test Location',
  type: 'landmark',
  address: '123 Main St',
  ...overrides
});

describe('MapStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mapStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.locations).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let locations: Location[] = [], loading = false;
      const unsubL = mapLocations.subscribe(s => { locations = s; });
      const unsubLoad = mapLoading.subscribe(s => { loading = s; });
      expect(locations).toEqual([]);
      expect(loading).toBe(false);
      unsubL(); unsubLoad();
    });
  });

  describe('loadLocations', () => {
    it('loads locations successfully', async () => {
      const locations = [mockLocation(), mockLocation()];
      vi.spyOn(mapService, 'getLocations').mockResolvedValue(locations);

      await mapStore.loadLocations();

      expect(mapService.getLocations).toHaveBeenCalledTimes(1);
      const state = getState();
      expect(state.locations).toEqual(locations);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(mapService, 'getLocations').mockReturnValue(p as any);

      const loadPromise = mapStore.loadLocations();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockLocation()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch locations');
      vi.spyOn(mapService, 'getLocations').mockRejectedValue(error);

      await mapStore.loadLocations();

      const state = getState();
      expect(state.error).toBe('Failed to fetch locations');
      expect(state.loading).toBe(false);
      expect(state.locations).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(mapService, 'getLocations').mockRejectedValue(new Error('test error'));
      await mapStore.loadLocations().catch(() => {});

      expect(getState().error).toBe('test error');

      mapStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Load some locations
      const locations = [mockLocation(), mockLocation()];
      vi.spyOn(mapService, 'getLocations').mockResolvedValue(locations);
      await mapStore.loadLocations();
      expect(getState().locations).toHaveLength(2);

      // Reset
      mapStore.reset();

      const state = getState();
      expect(state.locations).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
