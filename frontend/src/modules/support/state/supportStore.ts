import { writable, derived } from 'svelte/store';
import type { SupportState } from '../contracts/supportTypes';
import { supportService } from '../services/supportService';

const initialState: SupportState = {
  tickets: [],
  loading: false,
  error: null,
  pagination: { page: 1, limit: 20, total: 0, totalPages: 0 }
};

function createSupportStore() {
  const { subscribe, set, update } = writable<SupportState>(initialState);
  return {
    subscribe,
    async loadTickets(page: number = 1, limit: number = 20) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[SupportStore] Loading tickets: page=${page}, limit=${limit}`);
        const response = await supportService.getTickets(page, limit);
        update(s => ({
          ...s,
          tickets: response.items,
          loading: false,
          pagination: {
            page: response.page,
            limit: response.limit,
            total: response.total,
            totalPages: response.totalPages
          }
        }));
        console.info(`[SupportStore] Loaded ${response.items.length} tickets, total=${response.total}`);
      } catch (error) {
        console.error('[SupportStore] Failed to load tickets:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async loadMore() {
      let currentState: SupportState;
      const unsubscribe = supportStore.subscribe(s => { currentState = s; });
      unsubscribe();
      if (currentState && currentState.pagination.page < currentState.pagination.totalPages) {
        await this.loadTickets(currentState.pagination.page + 1, currentState.pagination.limit);
      }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const supportStore = createSupportStore();
export const supportTickets = derived(supportStore, $s => $s.tickets);
export const supportLoading = derived(supportStore, $s => $s.loading);
export const supportPagination = derived(supportStore, $s => $s.pagination);
