import { writable, derived } from 'svelte/store';
import type { MyApplicationsState } from '../contracts/myApplicationsTypes';
import { myApplicationsService } from '../services/myApplicationsService';

const initialState: MyApplicationsState = { applications: [], loading: false, error: null };

function createMyApplicationsStore() {
  const { subscribe, set, update } = writable<MyApplicationsState>(initialState);
  return {
    subscribe,
    async loadApplications() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const applications = await myApplicationsService.getApplications();
        update(s => ({ ...s, applications, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const myApplicationsStore = createMyApplicationsStore();
export const myApplications = derived(myApplicationsStore, $s => $s.applications);
export const myApplicationsLoading = derived(myApplicationsStore, $s => $s.loading);
