import { describe, it, expect, vi, beforeEach } from 'vitest';
import { elixirWalletStore } from './elixir-stores';
import { Elixir } from '../lib/api';

vi.mock('../lib/api', () => ({
  Elixir: {
    getBalance: vi.fn(),
    transfer: vi.fn(),
    getTransactionHistory: vi.fn(),
    createTransaction: vi.fn(),
  }
}));

describe('ElixirWalletStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    elixirWalletStore.reset();
    elixirWalletStore.subscribe(s => { state = s; });
  });


  describe('getBalance', () => {
    it('should fetch and store balance', async () => {
      const balance = { balance: 100 };
      (Elixir.getBalance as any).mockResolvedValue(balance);
      state.wallets = {};

      const result = await elixirWalletStore.getBalance('w1');

      expect(result).toEqual(balance);
      expect(state.wallets['w1']).toEqual(balance);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Elixir.getBalance as any).mockRejectedValue(new Error('Balance failed'));
      await expect(elixirWalletStore.getBalance('w1')).rejects.toThrow('Balance failed');
      expect(state.error).toBe('Balance failed');
    });
  });

  describe('transfer', () => {
    it('should record transaction', async () => {
      const transaction = { id: 't1', amount: 50 };
      (Elixir.transfer as any).mockResolvedValue(transaction);
      state.transactions = {};

      const result = await elixirWalletStore.transfer('w1', 'w2', 50);

      expect(result).toEqual(transaction);
      expect(state.transactions['t1']).toEqual(transaction);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Elixir.transfer as any).mockRejectedValue(new Error('Transfer failed'));
      await expect(elixirWalletStore.transfer('w1', 'w2', 50)).rejects.toThrow('Transfer failed');
      expect(state.error).toBe('Transfer failed');
    });
  });

  describe('getTransactionHistory', () => {
    it('should fetch and store history for wallet', async () => {
      const history = [{ id: 't2' }, { id: 't3' }];
      (Elixir.getTransactionHistory as any).mockResolvedValue(history);
      state.transactions = {};

      const result = await elixirWalletStore.getTransactionHistory('w1');

      expect(result).toEqual(history);
      expect(state.transactions['w1']).toEqual(history);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Elixir.getTransactionHistory as any).mockRejectedValue(new Error('History failed'));
      await expect(elixirWalletStore.getTransactionHistory('w1')).rejects.toThrow('History failed');
      expect(state.error).toBe('History failed');
    });
  });

  describe('createTransaction', () => {
    it('should add transaction to store', async () => {
      const transaction = { id: 't4', amount: 25 };
      (Elixir.createTransaction as any).mockResolvedValue(transaction);
      state.transactions = {};

      const result = await elixirWalletStore.createTransaction({ amount: 25 });

      expect(result).toEqual(transaction);
      expect(state.transactions['t4']).toEqual(transaction);
    });

    it('should handle error', async () => {
      (Elixir.createTransaction as any).mockRejectedValue(new Error('Create tx failed'));
      await expect(elixirWalletStore.createTransaction({ amount: 25 })).rejects.toThrow('Create tx failed');
      expect(state.error).toBe('Create tx failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.wallets = { w1: { balance: 100 } };
      state.transactions = { t1: { id: 't1' } };
      state.error = 'err';
      elixirWalletStore.reset();
      expect(state.wallets).toEqual({});
      expect(state.transactions).toEqual({});
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Elixir.getBalance as any).mockRejectedValue(new Error('test'));
      try { await elixirWalletStore.getBalance('w1'); } catch (e) {}
      expect(state.error).toBe('test');
      elixirWalletStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
