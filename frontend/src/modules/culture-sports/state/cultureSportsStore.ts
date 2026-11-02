import { writable, derived } from 'svelte/store';
import type { CultureSportsState } from '../contracts/cultureSportsTypes';
import { cultureSportsService } from '../services/cultureSportsService';

const initialState: CultureSportsState = { events: [], loading: false, error: null };

function createCultureSportsStore() {
  const { subscribe, set, update } = writable<CultureSportsState>(initialState);
  return {
    subscribe,
    async loadEvents() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const events = await cultureSportsService.getEvents();
        update(s => ({ ...s, events, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const cultureSportsStore = createCultureSportsStore();
export const cultureSportsEvents = derived(cultureSportsStore, $s => $s.events);
export const cultureSportsLoading = derived(cultureSportsStore, $s => $s.loading);
