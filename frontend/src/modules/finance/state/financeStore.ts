import { writable, derived } from 'svelte/store';
import type { FinanceState } from '../contracts/financeTypes';
import { financeService } from '../services/financeService';

const initialState: FinanceState = { accounts: [], transactions: [], loading: false, error: null };

function createFinanceStore() {
  const { subscribe, set, update } = writable<FinanceState>(initialState);
  return {
    subscribe,
    async loadAccounts() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const accounts = await financeService.getAccounts();
        update(s => ({ ...s, accounts, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const financeStore = createFinanceStore();
export const financeAccounts = derived(financeStore, $s => $s.accounts);
export const financeLoading = derived(financeStore, $s => $s.loading);
