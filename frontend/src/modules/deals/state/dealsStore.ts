import { writable, derived } from 'svelte/store';
import type { DealsState } from '../contracts/dealsTypes';
import { dealsService } from '../services/dealsService';

const initialState: DealsState = { deals: [], loading: false, error: null };

function createDealsStore() {
  const { subscribe, set, update } = writable<DealsState>(initialState);
  return {
    subscribe,
    async loadDeals() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const deals = await dealsService.getDeals();
        update(s => ({ ...s, deals, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const dealsStore = createDealsStore();
export const deals = derived(dealsStore, $s => $s.deals);
export const dealsLoading = derived(dealsStore, $s => $s.loading);
