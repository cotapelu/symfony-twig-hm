import { writable, derived } from 'svelte/store';
import type { PopulationState } from '../contracts/populationTypes';
import { populationService } from '../services/populationService';

const initialState: PopulationState = { records: [], loading: false, error: null };

function createPopulationStore() {
  const { subscribe, set, update } = writable<PopulationState>(initialState);
  return {
    subscribe,
    async loadRecords() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const records = await populationService.getRecords();
        update(s => ({ ...s, records, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const populationStore = createPopulationStore();
export const populationRecords = derived(populationStore, $s => $s.records);
export const populationLoading = derived(populationStore, $s => $s.loading);
