import { writable, derived } from 'svelte/store';
import type { BankingState, BankAccount, Transaction, Loan, CreditCard } from '../contracts/bankingTypes';
import { bankingService } from '../services/bankingService';

const initialState: BankingState = {
  accounts: [],
  transactions: [],
  loans: [],
  creditCards: [],
  loading: false,
  error: null
};

function createBankingStore() {
  const { subscribe, set, update } = writable<BankingState>(initialState);

  return {
    subscribe,

    async loadAccounts() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const accounts = await bankingService.getAccounts();
        update(s => ({ ...s, accounts, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadTransactions(accountId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const transactions = await bankingService.getTransactions(accountId);
        update(s => ({ ...s, transactions, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async createTransfer(fromAccountId: string, toAccountNumber: string, toBankCode: string, amount: number, description?: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const transaction = await bankingService.createTransfer({ fromAccountId, toAccountNumber, toBankCode, amount, description });
        update(s => ({ ...s, transactions: [transaction, ...s.transactions], loading: false }));
        return transaction;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadLoans() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const loans = await bankingService.getLoans();
        update(s => ({ ...s, loans, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadCreditCards() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const creditCards = await bankingService.getCreditCards();
        update(s => ({ ...s, creditCards, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
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

export const bankingStore = createBankingStore();

export const accounts = derived(bankingStore, $s => $s.accounts);
export const defaultAccount = derived(bankingStore, $s => $s.accounts.find(a => a.isDefault));
export const transactions = derived(bankingStore, $s => $s.transactions);
export const loans = derived(bankingStore, $s => $s.loans);
export const activeLoans = derived(bankingStore, $s => $s.loans.filter(l => l.status === 'active'));
export const creditCards = derived(bankingStore, $s => $s.creditCards);
export const totalBalance = derived(bankingStore, $s => $s.accounts.reduce((sum, a) => sum + a.balance, 0));
export const bankingLoading = derived(bankingStore, $s => $s.loading);
export const bankingError = derived(bankingStore, $s => $s.error);
