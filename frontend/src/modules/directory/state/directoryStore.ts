import { writable, derived } from 'svelte/store';
import type { DirectoryState } from '../contracts/directoryTypes';
import { directoryService } from '../services/directoryService';

const initialState: DirectoryState = { entries: [], loading: false, error: null };

function createDirectoryStore() {
  const { subscribe, set, update } = writable<DirectoryState>(initialState);
  return {
    subscribe,
    async loadEntries() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const entries = await directoryService.getEntries();
        update(s => ({ ...s, entries, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const directoryStore = createDirectoryStore();
export const directoryEntries = derived(directoryStore, $s => $s.entries);
export const directoryLoading = derived(directoryStore, $s => $s.loading);
