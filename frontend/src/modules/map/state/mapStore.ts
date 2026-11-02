import { writable, derived } from 'svelte/store';
import type { MapState } from '../contracts/mapTypes';
import { mapService } from '../services/mapService';

const initialState: MapState = { locations: [], loading: false, error: null };

function createMapStore() {
  const { subscribe, set, update } = writable<MapState>(initialState);
  return {
    subscribe,
    async loadLocations() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const locations = await mapService.getLocations();
        update(s => ({ ...s, locations, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const mapStore = createMapStore();
export const mapLocations = derived(mapStore, $s => $s.locations);
export const mapLoading = derived(mapStore, $s => $s.loading);
