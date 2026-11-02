import { writable, derived } from 'svelte/store';
import type { ElderlyState } from '../contracts/elderlyTypes';
import { elderlyServiceModule } from '../services/elderlyService';

const initialState: ElderlyState = { services: [], loading: false, error: null };

function createElderlyStore() {
  const { subscribe, set, update } = writable<ElderlyState>(initialState);
  return {
    subscribe,
    async loadServices() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const services = await elderlyServiceModule.getServices();
        update(s => ({ ...s, services, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const elderlyStore = createElderlyStore();
export const elderlyServices = derived(elderlyStore, $s => $s.services);
export const elderlyLoading = derived(elderlyStore, $s => $s.loading);
