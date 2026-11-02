import { describe, it, expect, vi, beforeEach } from 'vitest';
import { walletStore } from './walletStore';
import { WalletService } from '../services/walletService';
import type { Wallet, Transaction } from '../contracts/walletTypes';

function getCurrentState() {
  let current: any;
  const unsub = walletStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date().toISOString();

const mockWallet = (overrides: Partial<Wallet> = {}): Wallet => ({
  id: 'wallet-1',
  userId: 'user-1',
  balance: 1000,
  currency: 'USD',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

const mockTransaction = (overrides: Partial<Transaction> = {}): Transaction => ({
  id: `trx-${Math.random().toString(36).substr(2, 5)}`,
  walletId: 'wallet-1',
  type: 'deposit' as const,
  amount: 100,
  currency: 'USD',
  status: 'completed' as const,
  date: now,
  description: 'Test',
  ...overrides
});

describe('WalletStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    walletStore.reset();
  });

  describe('initial state', () => {
    it('has null wallet, empty transactions, defaults', () => {
      const state = getCurrentState();
      expect(state.wallet).toBeNull();
      expect(state.transactions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('loadWallet', () => {
    it('loads wallet and transactions in parallel', async () => {
      const w = mockWallet();
      const trx = [mockTransaction(), mockTransaction()];
      vi.spyOn(WalletService.prototype, 'getWallet').mockResolvedValue(w);
      vi.spyOn(WalletService.prototype, 'getTransactions').mockResolvedValue(trx);
      await walletStore.loadWallet();
      const state = getCurrentState();
      expect(state.wallet).toEqual(w);
      expect(state.transactions).toEqual(trx);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(WalletService.prototype, 'getWallet').mockRejectedValue(new Error('Service down'));
      await walletStore.loadWallet();
      const state = getCurrentState();
      expect(state.error).toBe('Service down');
      expect(state.wallet).toBeNull();
      expect(state.transactions).toEqual([]);
    });
  });

  describe('checkBalance', () => {
    it('returns balance and updates wallet if loaded', async () => {
      const w = mockWallet();
      vi.spyOn(WalletService.prototype, 'getWallet').mockResolvedValue(w);
      await walletStore.loadWallet();
      const newBalance = 1500;
      vi.spyOn(WalletService.prototype, 'checkBalance').mockResolvedValue(newBalance);
      const result = await walletStore.checkBalance();
      expect(result).toBe(newBalance);
      let state = getCurrentState();
      expect(state.wallet?.balance).toBe(newBalance);
    });

    it('handles error and sets error', async () => {
      vi.spyOn(WalletService.prototype, 'checkBalance').mockRejectedValue(new Error('Balance fail'));
      try {
        await walletStore.checkBalance();
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Balance fail');
    });
  });

  describe('deposit', () => {
    it('adds amount to wallet balance and prepends transaction', async () => {
      const w = mockWallet({ balance: 1000 });
      vi.spyOn(WalletService.prototype, 'getWallet').mockResolvedValue(w);
      await walletStore.loadWallet();
      vi.spyOn(WalletService.prototype, 'deposit').mockResolvedValue({ id: 'dep-1', status: 'completed' });
      const depositAmt = 200;
      const result = await walletStore.deposit({ amount: depositAmt, currency: 'USD', source: 'card' });
      expect(result).toEqual({ id: 'dep-1', status: 'completed' });
      let state = getCurrentState();
      expect(state.wallet?.balance).toBe(1200);
      expect(state.transactions[0].type).toBe('deposit');
      expect(state.transactions[0].amount).toBe(depositAmt);
    });

    it('propagates errors', async () => {
      vi.spyOn(WalletService.prototype, 'deposit').mockRejectedValue(new Error('Deposit failed'));
      try {
        await walletStore.deposit({ amount: 100, currency: 'USD', source: 'card' });
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Deposit failed');
    });
  });

  describe('withdraw', () => {
    it('subtracts amount from wallet balance and prepends transaction', async () => {
      const w = mockWallet({ balance: 1000 });
      vi.spyOn(WalletService.prototype, 'getWallet').mockResolvedValue(w);
      await walletStore.loadWallet();
      vi.spyOn(WalletService.prototype, 'withdraw').mockResolvedValue({ id: 'wit-1', status: 'completed' });
      const withdrawAmt = 300;
      const result = await walletStore.withdraw({ amount: withdrawAmt, currency: 'USD', destination: 'bank' });
      expect(result).toEqual({ id: 'wit-1', status: 'completed' });
      let state = getCurrentState();
      expect(state.wallet?.balance).toBe(700);
      expect(state.transactions[0].type).toBe('withdraw');
      expect(state.transactions[0].amount).toBe(withdrawAmt);
    });

    it('propagates errors', async () => {
      vi.spyOn(WalletService.prototype, 'withdraw').mockRejectedValue(new Error('Withdraw failed'));
      try {
        await walletStore.withdraw({ amount: 100, currency: 'USD', destination: 'bank' });
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Withdraw failed');
    });
  });

  describe('transfer', () => {
    it('subtracts amount from wallet balance and prepends transfer_out transaction', async () => {
      const w = mockWallet({ balance: 1000 });
      vi.spyOn(WalletService.prototype, 'getWallet').mockResolvedValue(w);
      await walletStore.loadWallet();
      vi.spyOn(WalletService.prototype, 'transfer').mockResolvedValue({ id: 'trf-1', status: 'completed' });
      const transferAmt = 150;
      const result = await walletStore.transfer({ amount: transferAmt, currency: 'USD', recipientId: 'user-2' });
      expect(result).toEqual({ id: 'trf-1', status: 'completed' });
      let state = getCurrentState();
      expect(state.wallet?.balance).toBe(850);
      expect(state.transactions[0].type).toBe('transfer_out');
      expect(state.transactions[0].amount).toBe(transferAmt);
      expect(state.transactions[0].recipientId).toBe('user-2');
    });

    it('propagates errors', async () => {
      vi.spyOn(WalletService.prototype, 'transfer').mockRejectedValue(new Error('Transfer failed'));
      try {
        await walletStore.transfer({ amount: 100, currency: 'USD', recipientId: 'u2' });
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Transfer failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(WalletService.prototype, 'getWallet').mockRejectedValue(new Error('test'));
      try { await walletStore.loadWallet(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      walletStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const w = mockWallet();
      const trx = [mockTransaction()];
      vi.spyOn(WalletService.prototype, 'getWallet').mockResolvedValue(w);
      vi.spyOn(WalletService.prototype, 'getTransactions').mockResolvedValue(trx);
      await walletStore.loadWallet();
      let state = getCurrentState();
      expect(state.wallet).toBeTruthy();
      expect(state.transactions.length).toBeGreaterThan(0);
      walletStore.reset();
      state = getCurrentState();
      expect(state.wallet).toBeNull();
      expect(state.transactions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
