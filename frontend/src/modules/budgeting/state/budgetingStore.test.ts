import { describe, it, expect, vi, beforeEach } from 'vitest';
import { budgetingStore, budgets, transactions } from './budgetingStore';
import { budgetingService } from '../services/budgetingService';
import type { Budget, BudgetTransaction, CreateBudgetRequest, UpdateBudgetRequest, AddTransactionRequest } from '../contracts/budgetingTypes';

// Helper to get current store state
function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockBudget = (overrides: Partial<Budget> = {}): Budget => ({
  id: `bud-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user1',
  name: 'Test Budget',
  amount: 1000,
  currency: 'USD',
  spent: 0,
  period: 'monthly',
  startDate: '2024-01-01',
  categories: [],
  isActive: true,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  ...overrides
});

const mockTransaction = (overrides: Partial<BudgetTransaction> = {}): BudgetTransaction => ({
  id: `txn-${Math.random().toString(36).substr(2, 5)}`,
  budgetId: 'bud-1',
  categoryId: undefined,
  amount: 100,
  description: 'Test',
  date: '2024-01-01',
  type: 'expense',
  ...overrides
});

const mockPaginatedBudgetsResponse = (data: Budget[], total?: number) => ({
  data,
  total: total ?? data.length,
  page: 1,
  limit: 10
});

const mockPaginatedTransactionsResponse = (data: BudgetTransaction[], total?: number) => ({
  data,
  total: total ?? data.length,
  page: 1,
  limit: 10
});

describe('BudgetingStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    budgetingStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and null loading/error', () => {
      const state = getCurrentState(budgetingStore);
      expect(state.budgets).toEqual([]);
      expect(state.transactions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('loadBudgets', () => {
    it('loads budgets successfully', async () => {
      const budgets = [mockBudget(), mockBudget()];
      vi.spyOn(budgetingService, 'getBudgets').mockResolvedValue(mockPaginatedBudgetsResponse(budgets));

      await budgetingStore.loadBudgets();

      const state = getCurrentState(budgetingStore);
      expect(state.budgets).toEqual(budgets);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(budgetingService, 'getBudgets').mockReturnValue(p as any);
      const loadPromise = budgetingStore.loadBudgets();
      await new Promise(process.nextTick);
      expect(getCurrentState(budgetingStore).loading).toBe(true);
      resolvePromise(mockPaginatedBudgetsResponse([mockBudget()]));
      await loadPromise;
      expect(getCurrentState(budgetingStore).loading).toBe(false);
    });

    it('loads empty paginated response', async () => {
      vi.spyOn(budgetingService, 'getBudgets').mockResolvedValue(mockPaginatedBudgetsResponse([]));
      await budgetingStore.loadBudgets();
      const state = getCurrentState(budgetingStore);
      expect(state.budgets).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(budgetingService, 'getBudgets').mockRejectedValue(new Error('Budget API down'));
      try {
        await budgetingStore.loadBudgets();
      } catch {}
      const state = getCurrentState(budgetingStore);
      expect(state.error).toBe('Budget API down');
      expect(state.loading).toBe(false);
      expect(state.budgets).toEqual([]);
    });

    it('updates budgets derived on success', async () => {
      const budgetsList = [mockBudget()];
      vi.spyOn(budgetingService, 'getBudgets').mockResolvedValue(mockPaginatedBudgetsResponse(budgetsList));
      await budgetingStore.loadBudgets();
      let b: Budget[] = [];
      const unsub = budgets.subscribe(s => { b = s; });
      expect(b).toEqual(budgetsList);
      unsub();
    });
  });

  describe('loadBudget', () => {
    it('loads a single budget and adds to list if not exists', async () => {
      const budget = mockBudget({ id: 'bud1' });
      vi.spyOn(budgetingService, 'getBudget').mockResolvedValue(budget);

      await budgetingStore.loadBudget('bud1');

      const state = getCurrentState(budgetingStore);
      expect(state.budgets).toContainEqual(budget);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('updates existing budget', async () => {
      const existing = mockBudget({ id: 'bud1', name: 'Old' });
      // Preload existing
      vi.spyOn(budgetingService, 'getBudgets').mockResolvedValue(mockPaginatedBudgetsResponse([existing]));
      await budgetingStore.loadBudgets();

      const updated = mockBudget({ id: 'bud1', name: 'New' });
      vi.spyOn(budgetingService, 'getBudget').mockResolvedValue(updated);

      await budgetingStore.loadBudget('bud1');

      const state = getCurrentState(budgetingStore);
      expect(state.budgets[0].name).toBe('New');
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(budgetingService, 'getBudget').mockReturnValue(p as any);
      const loadPromise = budgetingStore.loadBudget('bud1');
      await new Promise(process.nextTick);
      expect(getCurrentState(budgetingStore).loading).toBe(true);
      resolvePromise(mockBudget({ id: 'bud1' }));
      await loadPromise;
      expect(getCurrentState(budgetingStore).loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(budgetingService, 'getBudget').mockRejectedValue(new Error('Not found'));
      try {
        await budgetingStore.loadBudget('bud1');
      } catch {}
      const state = getCurrentState(budgetingStore);
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
    });

    it('updates budgets derived on success', async () => {
      const budget = mockBudget({ id: 'bud1' });
      vi.spyOn(budgetingService, 'getBudget').mockResolvedValue(budget);
      await budgetingStore.loadBudget('bud1');
      let b: Budget[] = [];
      const unsub = budgets.subscribe(s => { b = s; });
      expect(b).toContainEqual(budget);
      unsub();
    });
  });

  describe('createBudget', () => {
    it('creates budget and adds to list', async () => {
      const newBudget = mockBudget({ id: 'new' });
      vi.spyOn(budgetingService, 'createBudget').mockResolvedValue(newBudget);

      const request: CreateBudgetRequest = {
        name: 'New Budget',
        amount: 500,
        period: 'monthly',
        startDate: '2024-01-01'
      };
      const result = await budgetingStore.createBudget(request);

      expect(result).toEqual(newBudget);
      const state = getCurrentState(budgetingStore);
      expect(state.budgets).toContainEqual(newBudget);
    });

    it('handles error and throws', async () => {
      vi.spyOn(budgetingService, 'createBudget').mockRejectedValue(new Error('Create failed'));

      const request: CreateBudgetRequest = {
        name: 'Fail',
        amount: 0,
        period: 'monthly',
        startDate: '2024-01-01'
      };
      await expect(budgetingStore.createBudget(request)).rejects.toThrow('Create failed');
    });
  });

  describe('updateBudget', () => {
    it('updates budget in list', async () => {
      const existing = mockBudget({ id: 'bud1', name: 'Old' });
      // Preload
      vi.spyOn(budgetingService, 'getBudgets').mockResolvedValue(mockPaginatedBudgetsResponse([existing]));
      await budgetingStore.loadBudgets();

      const updated = mockBudget({ id: 'bud1', name: 'New' });
      vi.spyOn(budgetingService, 'updateBudget').mockResolvedValue(updated);

      const result = await budgetingStore.updateBudget('bud1', { name: 'New' as any });

      expect(result).toEqual(updated);
      const state = getCurrentState(budgetingStore);
      expect(state.budgets[0].name).toBe('New');
    });

    it('handles error', async () => {
      vi.spyOn(budgetingService, 'updateBudget').mockRejectedValue(new Error('Update error'));

      await expect(budgetingStore.updateBudget('bud1', { amount: 100 } as any)).rejects.toThrow('Update error');
    });
  });

  describe('deleteBudget', () => {
    it('removes budget from list', async () => {
      const bud1 = mockBudget({ id: 'bud1' });
      const bud2 = mockBudget({ id: 'bud2' });
      vi.spyOn(budgetingService, 'getBudgets').mockResolvedValue(mockPaginatedBudgetsResponse([bud1, bud2]));
      await budgetingStore.loadBudgets();

      vi.spyOn(budgetingService, 'deleteBudget').mockResolvedValue(undefined);
      await budgetingStore.deleteBudget('bud1');

      const state = getCurrentState(budgetingStore);
      expect(state.budgets).toHaveLength(1);
      expect(state.budgets[0].id).toBe('bud2');
    });

    it('handles error', async () => {
      vi.spyOn(budgetingService, 'deleteBudget').mockRejectedValue(new Error('Delete failed'));

      await expect(budgetingStore.deleteBudget('bud1')).rejects.toThrow('Delete failed');
    });
  });

  describe('loadTransactions', () => {
    it('loads transactions for a budget', async () => {
      const txns = [mockTransaction(), mockTransaction()];
      vi.spyOn(budgetingService, 'getTransactions').mockResolvedValue(mockPaginatedTransactionsResponse(txns));
      await budgetingStore.loadTransactions('bud1');
      const state = getCurrentState(budgetingStore);
      expect(state.transactions).toEqual(txns);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(budgetingService, 'getTransactions').mockReturnValue(p as any);
      const loadPromise = budgetingStore.loadTransactions('bud1');
      await new Promise(process.nextTick);
      expect(getCurrentState(budgetingStore).loading).toBe(true);
      resolvePromise(mockPaginatedTransactionsResponse([mockTransaction()]));
      await loadPromise;
      expect(getCurrentState(budgetingStore).loading).toBe(false);
    });

    it('updates transactions derived on success', async () => {
      const txns = [mockTransaction()];
      vi.spyOn(budgetingService, 'getTransactions').mockResolvedValue(mockPaginatedTransactionsResponse(txns));
      await budgetingStore.loadTransactions('bud1');
      let t: BudgetTransaction[] = [];
      const unsub = transactions.subscribe(s => { t = s; });
      expect(t).toEqual(txns);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(budgetingService, 'getTransactions').mockResolvedValue(mockPaginatedTransactionsResponse([]));
      await budgetingStore.loadTransactions('bud1');
      expect(getCurrentState(budgetingStore).transactions).toEqual([]);
      expect(getCurrentState(budgetingStore).loading).toBe(false);
      expect(getCurrentState(budgetingStore).error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(budgetingService, 'getTransactions').mockRejectedValue(new Error('Txn error'));
      try {
        await budgetingStore.loadTransactions('bud1');
      } catch {}
      const state = getCurrentState(budgetingStore);
      expect(state.error).toBe('Txn error');
      expect(state.loading).toBe(false);
      expect(state.transactions).toEqual([]);
    });
  });

  describe('addTransaction', () => {
    it('adds transaction and updates list', async () => {
      const txn = mockTransaction({ id: 'txn1' });
      vi.spyOn(budgetingService, 'addTransaction').mockResolvedValue(txn);

      const request: AddTransactionRequest = {
        budgetId: 'bud1',
        amount: 100,
        description: 'Test',
        date: '2024-01-01',
        type: 'expense'
      };
      const result = await budgetingStore.addTransaction(request);

      expect(result).toEqual(txn);
      const state = getCurrentState(budgetingStore);
      expect(state.transactions).toContainEqual(txn);
    });

    it('handles error and throws', async () => {
      vi.spyOn(budgetingService, 'addTransaction').mockRejectedValue(new Error('Add failed'));

      const request: AddTransactionRequest = {
        budgetId: 'bud1',
        amount: 100,
        description: 'Test',
        date: '2024-01-01',
        type: 'expense'
      };
      await expect(budgetingStore.addTransaction(request)).rejects.toThrow('Add failed');
    });
  });

  describe('deleteTransaction', () => {
    it('removes transaction from list', async () => {
      const txn1 = mockTransaction({ id: 't1' });
      const txn2 = mockTransaction({ id: 't2' });
      // Preload transactions
      vi.spyOn(budgetingService, 'getTransactions').mockResolvedValue(mockPaginatedTransactionsResponse([txn1, txn2]));
      await budgetingStore.loadTransactions('bud1');

      vi.spyOn(budgetingService, 'deleteTransaction').mockResolvedValue(undefined);
      await budgetingStore.deleteTransaction('t1');

      const state = getCurrentState(budgetingStore);
      expect(state.transactions).toHaveLength(1);
      expect(state.transactions[0].id).toBe('t2');
    });

    it('handles error', async () => {
      vi.spyOn(budgetingService, 'deleteTransaction').mockRejectedValue(new Error('Delete failed'));

      await expect(budgetingStore.deleteTransaction('t1')).rejects.toThrow('Delete failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(budgetingService, 'getBudgets').mockRejectedValue(new Error('test error'));
      try {
        await budgetingStore.loadBudgets();
      } catch (e) {}
      let state = getCurrentState(budgetingStore);
      expect(state.error).toBe('test error');

      budgetingStore.clearError();
      state = getCurrentState(budgetingStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      // Load some data
      const budgets = [mockBudget()];
      vi.spyOn(budgetingService, 'getBudgets').mockResolvedValue(mockPaginatedBudgetsResponse(budgets));
      await budgetingStore.loadBudgets();
      let state = getCurrentState(budgetingStore);
      expect(state.budgets.length).toBeGreaterThan(0);

      // Add a transaction
      const txn = mockTransaction();
      vi.spyOn(budgetingService, 'addTransaction').mockResolvedValue(txn);
      await budgetingStore.addTransaction({ budgetId: 'bud1', amount: 100, description: 'desc', date: '2024-01-01', type: 'expense' });
      state = getCurrentState(budgetingStore);
      expect(state.transactions.length).toBeGreaterThan(0);

      budgetingStore.reset();
      state = getCurrentState(budgetingStore);
      expect(state.budgets).toEqual([]);
      expect(state.transactions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
