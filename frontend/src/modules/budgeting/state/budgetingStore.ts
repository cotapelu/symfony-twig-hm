import { writable, derived } from 'svelte/store';
import type {
  Budget,
  BudgetTransaction,
  BudgetState,
  CreateBudgetRequest,
  UpdateBudgetRequest,
  AddTransactionRequest
} from '../contracts/budgetingTypes';
import { budgetingService } from '../services/budgetingService';

const initialState: BudgetState = {
  budgets: [],
  transactions: [],
  loading: false,
  error: null
};

function createBudgetingStore() {
  const { subscribe, set, update } = writable<BudgetState>(initialState);

  return {
    subscribe,

    async loadBudgets(page: number = 1, limit: number = 10) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const response = await budgetingService.getBudgets(page, limit);
        update(s => ({ ...s, budgets: response.data, loading: false }));
        // Store pagination info if needed
        return response;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadBudget(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const budget = await budgetingService.getBudget(id);
        update(s => {
          const exists = s.budgets.find(b => b.id === id);
          return {
            ...s,
            budgets: exists ? s.budgets.map(b => b.id === id ? budget : b) : [...s.budgets, budget],
            loading: false
          };
        });
        return budget;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async createBudget(request: CreateBudgetRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const budget = await budgetingService.createBudget(request);
        update(s => ({ ...s, budgets: [...s.budgets, budget], loading: false }));
        return budget;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async updateBudget(id: string, request: UpdateBudgetRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const budget = await budgetingService.updateBudget(id, request);
        update(s => ({
          ...s,
          budgets: s.budgets.map(b => b.id === id ? budget : b),
          loading: false
        }));
        return budget;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async deleteBudget(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        await budgetingService.deleteBudget(id);
        update(s => ({ ...s, budgets: s.budgets.filter(b => b.id !== id), loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadTransactions(budgetId: string, page: number = 1, limit: number = 10) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const response = await budgetingService.getTransactions(budgetId, page, limit);
        update(s => ({ ...s, transactions: response.data, loading: false }));
        // Store pagination info if needed
        return response;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async addTransaction(request: AddTransactionRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const transaction = await budgetingService.addTransaction(request);
        update(s => ({ ...s, transactions: [...s.transactions, transaction], loading: false }));
        return transaction;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async deleteTransaction(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        await budgetingService.deleteTransaction(id);
        update(s => ({ ...s, transactions: s.transactions.filter(t => t.id !== id), loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const budgetingStore = createBudgetingStore();

export const budgets = derived(budgetingStore, $s => $s.budgets);
export const transactions = derived(budgetingStore, $s => $s.transactions);
export const activeBudgets = derived(budgetingStore, $s => $s.budgets.filter(b => b.isActive));
export const budgetingLoading = derived(budgetingStore, $s => $s.loading);
export const budgetingError = derived(budgetingStore, $s => $s.error);

export const totalBudget = derived(budgetingStore, $s =>
  $s.budgets.reduce((sum, b) => sum + b.amount, 0)
);

export const totalSpent = derived(budgetingStore, $s =>
  $s.budgets.reduce((sum, b) => sum + b.spent, 0)
);
