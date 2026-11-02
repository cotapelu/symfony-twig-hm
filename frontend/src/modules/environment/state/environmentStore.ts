import { writable, derived } from 'svelte/store';
import type { EnvironmentState } from '../contracts/environmentTypes';
import { environmentService } from '../services/environmentService';

const initialState: EnvironmentState = { reports: [], loading: false, error: null };

function createEnvironmentStore() {
  const { subscribe, set, update } = writable<EnvironmentState>(initialState);
  return {
    subscribe,
    async loadReports() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const reports = await environmentService.getReports();
        update(s => ({ ...s, reports, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const environmentStore = createEnvironmentStore();
export const environmentReports = derived(environmentStore, $s => $s.reports);
export const environmentLoading = derived(environmentStore, $s => $s.loading);
