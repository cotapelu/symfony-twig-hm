import { writable, derived } from 'svelte/store';
import type { CompareItem, CompareState } from '../contracts/compareTypes';
import { compareService } from '../services/compareService';

const initialState: CompareState = { currentCompare: [], history: [], loading: false, error: null };

function createCompareStore() {
  const { subscribe, set, update } = writable<CompareState>(initialState);
  return {
    subscribe,
    async loadHistory() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const history = await compareService.getHistory();
        update(s => ({ ...s, history, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    async addItem(item: CompareItem) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const items = await compareService.addItem(item);
        update(s => ({ ...s, currentCompare: items, loading: false }));
      } catch (e) {
        update(s => ({ ...s, error: (e as Error).message, loading: false }));
        throw e;
      }
    },
    async removeItem(itemId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const items = await compareService.removeItem(itemId);
        update(s => ({ ...s, currentCompare: items, loading: false }));
      } catch (e) {
        update(s => ({ ...s, error: (e as Error).message, loading: false }));
        throw e;
      }
    },
    clearCompare() { update(s => ({ ...s, currentCompare: [] })); },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const compareStore = createCompareStore();
export const compareItems = derived(compareStore, $s => $s.currentCompare);
export const compareHistory = derived(compareStore, $s => $s.history);
export const compareLoading = derived(compareStore, $s => $s.loading);
