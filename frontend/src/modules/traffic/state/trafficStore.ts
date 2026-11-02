import { writable, derived, get } from 'svelte/store';
import type { TrafficState } from '../contracts/trafficTypes';
import { trafficService } from '../services/trafficService';

const initialState: TrafficState = {
  alerts: [],
  loading: false,
  error: null,
  pagination: { page: 1, limit: 20, total: 0, totalPages: 0 }
};

function createTrafficStore() {
  const { subscribe, set, update } = writable<TrafficState>(initialState);
  return {
    subscribe,
    async loadAlerts(page: number = 1, limit: number = 20) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[TrafficStore] Loading alerts: page=${page}, limit=${limit}`);
        const response = await trafficService.getAlerts(page, limit);
        update(s => ({
          ...s,
          alerts: response.items,
          loading: false,
          pagination: {
            page: response.page,
            limit: response.limit,
            total: response.total,
            totalPages: response.totalPages
          }
        }));
        console.info(`[TrafficStore] Loaded ${response.items.length} alerts, total=${response.total}`);
      } catch (error) {
        console.error('[TrafficStore] Failed to load alerts:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async loadMore() {
      let currentState: TrafficState;
      const unsubscribe = trafficStore.subscribe(s => { currentState = s; });
      unsubscribe();
      if (currentState && currentState.pagination.page < currentState.pagination.totalPages) {
        await this.loadAlerts(currentState.pagination.page + 1, currentState.pagination.limit);
      }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const trafficStore = createTrafficStore();
export const trafficAlerts = derived(trafficStore, $s => $s.alerts);
export const trafficLoading = derived(trafficStore, $s => $s.loading);
export const trafficPagination = derived(trafficStore, $s => $s.pagination);
