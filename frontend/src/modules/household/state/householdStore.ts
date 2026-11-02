import { writable, derived } from 'svelte/store';
import type { HouseholdState } from '../contracts/householdTypes';
import { householdService } from '../services/householdService';

const initialState: HouseholdState = { households: [], loading: false, error: null };

function createHouseholdStore() {
  const { subscribe, set, update } = writable<HouseholdState>(initialState);
  return {
    subscribe,
    async loadHouseholds() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const households = await householdService.getHouseholds();
        update(s => ({ ...s, households, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const householdStore = createHouseholdStore();
export const households = derived(householdStore, $s => $s.households);
export const householdLoading = derived(householdStore, $s => $s.loading);
