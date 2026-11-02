import { writable, derived } from 'svelte/store';
import type { SecurityOrderState } from '../contracts/securityOrderTypes';
import { securityOrderService } from '../services/securityOrderService';

const initialState: SecurityOrderState = {
  orders: [],
  loading: false,
  error: null,
  pagination: { page: 1, limit: 20, total: 0, totalPages: 0 }
};

function createSecurityOrderStore() {
  const { subscribe, set, update } = writable<SecurityOrderState>(initialState);
  return {
    subscribe,
    async loadOrders(page: number = 1, limit: number = 20) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[SecurityOrderStore] Loading orders: page=${page}, limit=${limit}`);
        const response = await securityOrderService.getOrders(page, limit);
        update(s => ({
          ...s,
          orders: response.items,
          loading: false,
          pagination: {
            page: response.page,
            limit: response.limit,
            total: response.total,
            totalPages: response.totalPages
          }
        }));
        console.info(`[SecurityOrderStore] Loaded ${response.items.length} orders, total=${response.total}`);
      } catch (error) {
        console.error('[SecurityOrderStore] Failed to load orders:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async loadMore() {
      let currentState: SecurityOrderState;
      const unsubscribe = securityOrderStore.subscribe(s => { currentState = s; });
      unsubscribe();
      if (currentState && currentState.pagination.page < currentState.pagination.totalPages) {
        await this.loadOrders(currentState.pagination.page + 1, currentState.pagination.limit);
      }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const securityOrderStore = createSecurityOrderStore();
export const securityOrders = derived(securityOrderStore, $s => $s.orders);
export const securityOrderLoading = derived(securityOrderStore, $s => $s.loading);
export const securityOrderPagination = derived(securityOrderStore, $s => $s.pagination);
