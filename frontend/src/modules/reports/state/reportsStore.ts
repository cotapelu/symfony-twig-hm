import { writable, derived } from 'svelte/store';
import type { ReportsState } from '../contracts/reportsTypes';
import { reportsService } from '../services/reportsService';

const initialState: ReportsState = { reports: [], loading: false, error: null };

function createReportsStore() {
  const { subscribe, set, update } = writable<ReportsState>(initialState);
  return {
    subscribe,
    async loadReports() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const reports = await reportsService.getReports();
        update(s => ({ ...s, reports, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const reportsStore = createReportsStore();
export const reports = derived(reportsStore, $s => $s.reports);
export const reportsLoading = derived(reportsStore, $s => $s.loading);
