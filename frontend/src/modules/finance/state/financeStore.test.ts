import { describe, it, expect, vi, beforeEach } from 'vitest';
import { financeStore, financeAccounts, financeLoading } from './financeStore';
import { FinanceService } from '../services/financeService';
import type { Account } from '../contracts/financeTypes';

function getCurrentState() {
  let current: any;
  const unsub = financeStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockAccount = (overrides: Partial<Account> = {}): Account => ({
  id: `acc-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Account Name',
  type: 'checking',
  balance: 1000,
  currency: 'USD',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

describe('FinanceStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    financeStore.reset();
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.accounts).toEqual([]);
    expect(state.transactions).toEqual([]);
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
  });

  it('derived stores', () => {
    let a: Account[] = [], l = false;
    const unsubA = financeAccounts.subscribe(s => { a = s; });
    const unsubL = financeLoading.subscribe(s => { l = s; });
    expect(a).toEqual([]);
    expect(l).toBe(false);
    unsubA(); unsubL();
  });

  describe('loadAccounts', () => {
    it('loads accounts', async () => {
      const accs = [mockAccount(), mockAccount()];
      vi.spyOn(FinanceService.prototype, 'getAccounts').mockResolvedValue(accs);
      await financeStore.loadAccounts();
      const state = getCurrentState();
      expect(state.accounts).toEqual(accs);
      expect(state.loading).toBe(false);
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(FinanceService.prototype, 'getAccounts').mockReturnValue(p as any);
      const loadPromise = financeStore.loadAccounts();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockAccount()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates financeAccounts derived on success', async () => {
      const accs = [mockAccount()];
      vi.spyOn(FinanceService.prototype, 'getAccounts').mockResolvedValue(accs);
      await financeStore.loadAccounts();
      let a: Account[] = [];
      const unsub = financeAccounts.subscribe(s => { a = s; });
      expect(a).toEqual(accs);
      unsub();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(FinanceService.prototype, 'getAccounts').mockRejectedValue(new Error('Failed to load accounts'));
      await financeStore.loadAccounts();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load accounts');
      expect(state.loading).toBe(false);
      expect(state.accounts).toEqual([]);
    });

    it('handles empty response', async () => {
      vi.spyOn(FinanceService.prototype, 'getAccounts').mockResolvedValue([]);
      await financeStore.loadAccounts();
      expect(getCurrentState().accounts).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(FinanceService.prototype, 'getAccounts').mockRejectedValue(new Error('err'));
      await financeStore.loadAccounts();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      financeStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(FinanceService.prototype, 'getAccounts').mockResolvedValue([mockAccount()]);
      await financeStore.loadAccounts();
      let state = getCurrentState();
      expect(state.accounts.length).toBeGreaterThan(0);
      financeStore.reset();
      state = getCurrentState();
      expect(state.accounts).toEqual([]);
      expect(state.transactions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
