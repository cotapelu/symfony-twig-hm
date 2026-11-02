import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  bankingStore,
  accounts,
  defaultAccount,
  transactions,
  loans,
  activeLoans,
  creditCards,
  totalBalance,
  bankingLoading,
  bankingError
} from './bankingStore';
import { BankingService } from '../services/bankingService';
import type { BankAccount, Transaction, Loan, CreditCard } from '../contracts/bankingTypes';

function getCurrentState() {
  let current: any;
  const unsub = bankingStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockAccount = (overrides: Partial<BankAccount> = {}): BankAccount => ({
  id: `acc-${Math.random().toString(36).substr(2, 5)}`,
  number: '1234567890',
  balance: 1000,
  currency: 'USD',
  isDefault: false,
  createdAt: now,
  updatedAt: now,
  ...overrides
});

const mockTransaction = (overrides: Partial<Transaction> = {}): Transaction => ({
  id: `trans-${Math.random().toString(36).substr(2, 5)}`,
  fromAccountId: 'acc-1',
  toAccountNumber: '9876543210',
  toBankCode: 'BANK001',
  amount: 100,
  description: 'Transfer',
  timestamp: now,
  ...overrides
});

const mockLoan = (overrides: Partial<Loan> = {}): Loan => ({
  id: `loan-${Math.random().toString(36).substr(2, 5)}`,
  accountId: 'acc-1',
  amount: 10000,
  remaining: 8000,
  status: 'active',
  startDate: now,
  endDate: new Date(now.getTime() + 365 * 24 * 60 * 60 * 1000),
  ...overrides
});

const mockCreditCard = (overrides: Partial<CreditCard> = {}): CreditCard => ({
  id: `card-${Math.random().toString(36).substr(2, 5)}`,
  number: '4111111111111111',
  limit: 5000,
  used: 1000,
  isDefault: false,
  expiryDate: '12/30',
  ...overrides
});

describe('BankingStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    bankingStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.accounts).toEqual([]);
      expect(state.transactions).toEqual([]);
      expect(state.loans).toEqual([]);
      expect(state.creditCards).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores (empty)', () => {
      let accs: BankAccount[] = [], def: BankAccount | undefined = [], trans: Transaction[] = [], lo: Loan[] = [], act: Loan[] = [], cards: CreditCard[] = [], total = 0, load = false, err: string | null = null;
      const unsubA = accounts.subscribe(s => { accs = s; });
      const unsubD = defaultAccount.subscribe(s => { def = s; });
      const unsubT = transactions.subscribe(s => { trans = s; });
      const unsubL = loans.subscribe(s => { lo = s; });
      const unsubAct = activeLoans.subscribe(s => { act = s; });
      const unsubC = creditCards.subscribe(s => { cards = s; });
      const unsubTot = totalBalance.subscribe(s => { total = s; });
      const unsubLoad = bankingLoading.subscribe(s => { load = s; });
      const unsubErr = bankingError.subscribe(s => { err = s; });
      expect(accs).toEqual([]);
      expect(def).toBeUndefined();
      expect(trans).toEqual([]);
      expect(lo).toEqual([]);
      expect(act).toEqual([]);
      expect(cards).toEqual([]);
      expect(total).toBe(0);
      expect(load).toBe(false);
      expect(err).toBeNull();
      unsubA(); unsubD(); unsubT(); unsubL(); unsubAct(); unsubC(); unsubTot(); unsubLoad(); unsubErr();
    });
  });

  describe('loadAccounts', () => {
    it('loads accounts', async () => {
      const accs = [mockAccount(), mockAccount()];
      vi.spyOn(BankingService.prototype, 'getAccounts').mockResolvedValue(accs);
      await bankingStore.loadAccounts();
      const state = getCurrentState();
      expect(state.accounts).toEqual(accs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(BankingService.prototype, 'getAccounts').mockReturnValue(p as any);
      const loadPromise = bankingStore.loadAccounts();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockAccount()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates accounts derived on success', async () => {
      const accs = [mockAccount()];
      vi.spyOn(BankingService.prototype, 'getAccounts').mockResolvedValue(accs);
      await bankingStore.loadAccounts();
      let a: BankAccount[] = [];
      const unsub = accounts.subscribe(s => { a = s; });
      expect(a).toEqual(accs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(BankingService.prototype, 'getAccounts').mockResolvedValue([]);
      await bankingStore.loadAccounts();
      expect(getCurrentState().accounts).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(BankingService.prototype, 'getAccounts').mockRejectedValue(new Error('Accounts failed'));
      await bankingStore.loadAccounts();
      const state = getCurrentState();
      expect(state.error).toBe('Accounts failed');
      expect(state.loading).toBe(false);
      expect(state.accounts).toEqual([]);
    });
  });

  describe('loadTransactions', () => {
    it('loads transactions', async () => {
      const trans = [mockTransaction(), mockTransaction()];
      vi.spyOn(BankingService.prototype, 'getTransactions').mockResolvedValue(trans);
      await bankingStore.loadTransactions('acc-1');
      const state = getCurrentState();
      expect(state.transactions).toEqual(trans);
      expect(state.loading).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(BankingService.prototype, 'getTransactions').mockRejectedValue(new Error('Transactions failed'));
      await bankingStore.loadTransactions('acc-1');
      const state = getCurrentState();
      expect(state.error).toBe('Transactions failed');
    });
  });

  describe('createTransfer', () => {
    it('creates transaction and prepends to transactions', async () => {
      // Prepopulate accounts if needed
      // Not strictly needed for test, but we need to have transactions to be appended.
      const trans = mockTransaction({ id: 't1', amount: 100 });
      vi.spyOn(BankingService.prototype, 'createTransfer').mockResolvedValue(trans);
      const result = await bankingStore.createTransfer('acc-1', '9876543210', 'BANK001', 100);
      expect(result).toEqual(trans);
      const state = getCurrentState();
      expect(state.transactions).toContainEqual(trans);
    });

    it('propagates error', async () => {
      vi.spyOn(BankingService.prototype, 'createTransfer').mockRejectedValue(new Error('Transfer failed'));
      try {
        await bankingStore.createTransfer('acc-1', '9876543210', 'BANK001', 100);
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Transfer failed');
    });
  });

  describe('loadLoans', () => {
    it('loads loans', async () => {
      const loanList = [mockLoan(), mockLoan()];
      vi.spyOn(BankingService.prototype, 'getLoans').mockResolvedValue(loanList);
      await bankingStore.loadLoans();
      const state = getCurrentState();
      expect(state.loans).toEqual(loanList);
      expect(state.loading).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(BankingService.prototype, 'getLoans').mockRejectedValue(new Error('Loans failed'));
      await bankingStore.loadLoans();
      const state = getCurrentState();
      expect(state.error).toBe('Loans failed');
    });
  });

  describe('loadCreditCards', () => {
    it('loads credit cards', async () => {
      const cards = [mockCreditCard(), mockCreditCard()];
      vi.spyOn(BankingService.prototype, 'getCreditCards').mockResolvedValue(cards);
      await bankingStore.loadCreditCards();
      const state = getCurrentState();
      expect(state.creditCards).toEqual(cards);
      expect(state.loading).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(BankingService.prototype, 'getCreditCards').mockRejectedValue(new Error('Cards failed'));
      await bankingStore.loadCreditCards();
      const state = getCurrentState();
      expect(state.error).toBe('Cards failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(BankingService.prototype, 'getAccounts').mockRejectedValue(new Error('err'));
      await bankingStore.loadAccounts();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      bankingStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(BankingService.prototype, 'getAccounts').mockResolvedValue([mockAccount()]);
      await bankingStore.loadAccounts();
      let state = getCurrentState();
      expect(state.accounts.length).toBeGreaterThan(0);
      bankingStore.reset();
      state = getCurrentState();
      expect(state.accounts).toEqual([]);
      expect(state.transactions).toEqual([]);
      expect(state.loans).toEqual([]);
      expect(state.creditCards).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('derived stores', () => {
    it('defaultAccount returns first default account', async () => {
      const acc1 = mockAccount({ id: 'a1', isDefault: false });
      const acc2 = mockAccount({ id: 'a2', isDefault: true });
      vi.spyOn(BankingService.prototype, 'getAccounts').mockResolvedValue([acc1, acc2]);
      await bankingStore.loadAccounts();
      let def: BankAccount | undefined = undefined;
      const unsub = defaultAccount.subscribe(s => { def = s; });
      expect(def).toEqual(acc2);
      unsub();
    });

    it('activeLoans filters by status active', async () => {
      const loan1 = mockLoan({ id: 'l1', status: 'active' });
      const loan2 = mockLoan({ id: 'l2', status: 'closed' });
      vi.spyOn(BankingService.prototype, 'getLoans').mockResolvedValue([loan1, loan2]);
      await bankingStore.loadLoans();
      let act: Loan[] = [];
      const unsub = activeLoans.subscribe(s => { act = s; });
      expect(act).toEqual([loan1]);
      unsub();
    });

    it('totalBalance sums all account balances', async () => {
      const acc1 = mockAccount({ id: 'a1', balance: 1000 });
      const acc2 = mockAccount({ id: 'a2', balance: 2000 });
      vi.spyOn(BankingService.prototype, 'getAccounts').mockResolvedValue([acc1, acc2]);
      await bankingStore.loadAccounts();
      let total = 0;
      const unsub = totalBalance.subscribe(s => { total = s; });
      expect(total).toBe(3000);
      unsub();
    });
  });
});
