import { writable, derived } from 'svelte/store';
import type { TtthState } from '../contracts/ttthTypes';
import { ttthService } from '../services/ttthService';

const initialState: TtthState = {
  accounts: [],
  transactions: [],
  loading: false,
  error: null,
  pagination: { page: 1, limit: 20, total: 0, totalPages: 0 }
};

function createTtthStore() {
  const { subscribe, set, update } = writable<TtthState>(initialState);
  return {
    subscribe,
    async loadAccounts(page: number = 1, limit: number = 20) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[TtthStore] Loading accounts: page=${page}, limit=${limit}`);
        const response = await ttthService.getAccounts(page, limit);
        update(s => ({
          ...s,
          accounts: response.items,
          loading: false,
          pagination: {
            page: response.page,
            limit: response.limit,
            total: response.total,
            totalPages: response.totalPages
          }
        }));
        console.info(`[TtthStore] Loaded ${response.items.length} accounts, total=${response.total}`);
      } catch (error) {
        console.error('[TtthStore] Failed to load accounts:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    async loadTransactions(accountId: string, page: number = 1, limit: number = 20) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        console.info(`[TtthStore] Loading transactions: accountId=${accountId}`);
        const response = await ttthService.getTransactions(accountId, page, limit);
        update(s => ({
          ...s,
          transactions: response.items,
          loading: false,
          pagination: {
            page: response.page,
            limit: response.limit,
            total: response.total,
            totalPages: response.totalPages
          }
        }));
        console.info(`[TtthStore] Loaded ${response.items.length} transactions`);
      } catch (error) {
        console.error('[TtthStore] Failed to load transactions:', error);
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const ttthStore = createTtthStore();
export const ttthAccounts = derived(ttthStore, $s => $s.accounts);
export const ttthLoading = derived(ttthStore, $s => $s.loading);
export const ttthPagination = derived(ttthStore, $s => $s.pagination);
