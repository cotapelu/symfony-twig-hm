import { writable, derived } from 'svelte/store';
import type { DiscoverState } from '../contracts/discoverTypes';
import { discoverService } from '../services/discoverService';

const initialState: DiscoverState = { items: [], loading: false, error: null };

function createDiscoverStore() {
  const { subscribe, set, update } = writable<DiscoverState>(initialState);
  return {
    subscribe,
    async loadItems() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const items = await discoverService.getItems();
        update(s => ({ ...s, items, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const discoverStore = createDiscoverStore();
export const discoverItems = derived(discoverStore, $s => $s.items);
export const discoverLoading = derived(discoverStore, $s => $s.loading);
