import { writable, derived } from 'svelte/store';
import type { LandConstructionState } from '../contracts/landConstructionTypes';
import { landConstructionService } from '../services/landConstructionService';

const initialState: LandConstructionState = { permits: [], loading: false, error: null };

function createLandConstructionStore() {
  const { subscribe, set, update } = writable<LandConstructionState>(initialState);
  return {
    subscribe,
    async loadPermits() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const permits = await landConstructionService.getPermits();
        update(s => ({ ...s, permits, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const landConstructionStore = createLandConstructionStore();
export const landConstructionPermits = derived(landConstructionStore, $s => $s.permits);
export const landConstructionLoading = derived(landConstructionStore, $s => $s.loading);
