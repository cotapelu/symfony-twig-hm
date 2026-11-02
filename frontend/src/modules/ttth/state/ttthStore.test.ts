import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ttthStore, ttthAccounts, ttthLoading, ttthPagination } from './ttthStore';
import { ttthService } from '../services/ttthService';
import type { TtthAccount, TtthTransaction, PaginatedResponse } from '../contracts/ttthTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = ttthStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockAccount = (overrides: Partial<TtthAccount> = {}): TtthAccount => ({
  id: `acc-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test Account',
  type: 'checking',
  balance: 1000,
  currency: 'USD',
  createdAt: new Date().toISOString(),
  ...overrides
});

const mockTransaction = (overrides: Partial<TtthTransaction> = {}): TtthTransaction => ({
  id: `tx-${Math.random().toString(36).substr(2, 5)}`,
  accountId: 'acc-1',
  amount: 50,
  currency: 'USD',
  type: 'debit',
  description: 'Purchase',
  timestamp: new Date().toISOString(),
  ...overrides
});

const mockPaginatedResponse = <T>(items: T[], page: number, limit: number, total: number): PaginatedResponse<T> => ({
  items,
  page,
  limit,
  total,
  totalPages: Math.ceil(total / limit)
});

describe('TtthStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    ttthStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.accounts).toEqual([]);
      expect(state.transactions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });

    it('derived stores', () => {
      let accounts: TtthAccount[] = [], loading = false, pagination: any = {};
      const unsubA = ttthAccounts.subscribe(s => { accounts = s; });
      const unsubL = ttthLoading.subscribe(s => { loading = s; });
      const unsubP = ttthPagination.subscribe(s => { pagination = s; });
      expect(accounts).toEqual([]);
      expect(loading).toBe(false);
      expect(pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
      unsubA(); unsubL(); unsubP();
    });
  });

  describe('loadAccounts', () => {
    it('loads accounts successfully with default params', async () => {
      const accounts = [mockAccount(), mockAccount()];
      const response = mockPaginatedResponse(accounts, 1, 20, 2);
      vi.spyOn(ttthService, 'getAccounts').mockResolvedValue(response);

      await ttthStore.loadAccounts();

      expect(ttthService.getAccounts).toHaveBeenCalledWith(1, 20);
      const state = getState();
      expect(state.accounts).toEqual(accounts);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 2, totalPages: 1 });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads with custom page and limit', async () => {
      const accounts = [mockAccount()];
      const response = mockPaginatedResponse(accounts, 2, 10, 1);
      vi.spyOn(ttthService, 'getAccounts').mockResolvedValue(response);

      await ttthStore.loadAccounts(2, 10);

      expect(ttthService.getAccounts).toHaveBeenCalledWith(2, 10);
      const state = getState();
      expect(state.pagination.page).toBe(2);
      expect(state.pagination.limit).toBe(10);
    });

    it('sets loading true during fetch', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(ttthService, 'getAccounts').mockReturnValue(p as any);

      const loadPromise = ttthStore.loadAccounts();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(mockPaginatedResponse([mockAccount()], 1, 20, 1));
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch accounts');
      vi.spyOn(ttthService, 'getAccounts').mockRejectedValue(error);

      await ttthStore.loadAccounts();

      const state = getState();
      expect(state.error).toBe('Failed to fetch accounts');
      expect(state.loading).toBe(false);
      expect(state.accounts).toEqual([]);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 }); // unchanged
    });
  });

  describe('loadTransactions', () => {
    it('loads transactions successfully', async () => {
      const transactions = [mockTransaction(), mockTransaction()];
      const response = mockPaginatedResponse(transactions, 1, 20, 2);
      vi.spyOn(ttthService, 'getTransactions').mockResolvedValue(response);

      await ttthStore.loadTransactions('acc-1');

      expect(ttthService.getTransactions).toHaveBeenCalledWith('acc-1', 1, 20);
      const state = getState();
      expect(state.transactions).toEqual(transactions);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 2, totalPages: 1 });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads with custom pagination', async () => {
      const transactions = [mockTransaction()];
      const response = mockPaginatedResponse(transactions, 2, 10, 1);
      vi.spyOn(ttthService, 'getTransactions').mockResolvedValue(response);

      await ttthStore.loadTransactions('acc-1', 2, 10);

      expect(ttthService.getTransactions).toHaveBeenCalledWith('acc-1', 2, 10);
      const state = getState();
      expect(state.pagination.page).toBe(2);
      expect(state.pagination.limit).toBe(10);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch transactions');
      vi.spyOn(ttthService, 'getTransactions').mockRejectedValue(error);

      await ttthStore.loadTransactions('acc-1');

      const state = getState();
      expect(state.error).toBe('Failed to fetch transactions');
      expect(state.loading).toBe(false);
      expect(state.transactions).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(ttthService, 'getAccounts').mockRejectedValue(new Error('test error'));
      await ttthStore.loadAccounts().catch(() => {});

      expect(getState().error).toBe('test error');

      ttthStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Load some accounts
      const accounts = [mockAccount(), mockAccount()];
      vi.spyOn(ttthService, 'getAccounts').mockResolvedValue(mockPaginatedResponse(accounts, 1, 20, 2));
      await ttthStore.loadAccounts();
      expect(getState().accounts).toHaveLength(2);

      // Load some transactions
      const transactions = [mockTransaction()];
      vi.spyOn(ttthService, 'getTransactions').mockResolvedValue(mockPaginatedResponse(transactions, 1, 20, 1));
      await ttthStore.loadTransactions('acc-1');
      expect(getState().transactions).toHaveLength(1);

      // Reset
      ttthStore.reset();

      const state = getState();
      expect(state.accounts).toEqual([]);
      expect(state.transactions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });
  });
});
