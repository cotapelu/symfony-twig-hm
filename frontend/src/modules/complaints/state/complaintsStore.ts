import { writable, derived } from 'svelte/store';
import type { ComplaintsState } from '../contracts/complaintsTypes';
import { complaintsService } from '../services/complaintsService';

const initialState: ComplaintsState = { complaints: [], loading: false, error: null };

function createComplaintsStore() {
  const { subscribe, set, update } = writable<ComplaintsState>(initialState);
  return {
    subscribe,
    async loadComplaints() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const complaints = await complaintsService.getComplaints();
        update(s => ({ ...s, complaints, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const complaintsStore = createComplaintsStore();
export const complaints = derived(complaintsStore, $s => $s.complaints);
export const complaintsLoading = derived(complaintsStore, $s => $s.loading);
