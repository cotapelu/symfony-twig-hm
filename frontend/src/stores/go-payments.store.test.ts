import { describe, it, expect, vi, beforeEach } from 'vitest';
import { goPaymentsStore } from './go-all';
import { Go } from '../lib/api';

vi.mock('../lib/api', () => ({
  Go: {
    getWallet: vi.fn(),
    createWallet: vi.fn(),
    getTransactions: vi.fn(),
    processPayment: vi.fn(),
    addFunds: vi.fn(),
    sendPayment: vi.fn(),
    requestPayment: vi.fn(),
  }
}));

describe('GoPaymentsStore', () => {
  let state: any;

  beforeEach(() => {
    vi.clearAllMocks();
    goPaymentsStore.reset();
    goPaymentsStore.subscribe(s => { state = s; });
  });

  describe('loadWallet', () => {
    it('should load wallet', async () => {
      const wallet = { balance: 100 };
      (Go.getWallet as any).mockResolvedValue(wallet);

      const result = await goPaymentsStore.loadWallet('u1');

      expect(result).toEqual(wallet);
      expect(state.wallet).toEqual(wallet);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.getWallet as any).mockRejectedValue(new Error('Load wallet failed'));
      await expect(goPaymentsStore.loadWallet('u1')).rejects.toThrow('Load wallet failed');
      expect(state.error).toBe('Load wallet failed');
    });
  });

  describe('createWallet', () => {
    it('should create wallet', async () => {
      const wallet = { id: 'w1' };
      (Go.createWallet as any).mockResolvedValue(wallet);

      const result = await goPaymentsStore.createWallet('u1');

      expect(result).toEqual(wallet);
      expect(state.wallet).toEqual(wallet);
    });

    it('should handle error', async () => {
      (Go.createWallet as any).mockRejectedValue(new Error('Create failed'));
      await expect(goPaymentsStore.createWallet('u1')).rejects.toThrow('Create failed');
      expect(state.error).toBe('Create failed');
    });
  });

  describe('loadTransactions', () => {
    it('should load transactions', async () => {
      const txs = [{ id: 't1' }];
      (Go.getTransactions as any).mockResolvedValue(txs);

      const result = await goPaymentsStore.loadTransactions('w1');

      expect(result).toEqual(txs);
      expect(state.transactions).toEqual(txs);
      expect(state.loading).toBe(false);
    });

    it('should handle error', async () => {
      (Go.getTransactions as any).mockRejectedValue(new Error('Tx failed'));
      await expect(goPaymentsStore.loadTransactions('w1')).rejects.toThrow('Tx failed');
      expect(state.error).toBe('Tx failed');
    });
  });

  describe('processPayment', () => {
    it('should process payment', async () => {
      const payment = { id: 'p1' };
      (Go.processPayment as any).mockResolvedValue(payment);
      state.transactions = [];

      const result = await goPaymentsStore.processPayment({ amount: 10 });

      expect(result).toEqual(payment);
      expect(state.transactions).toContain(payment);
    });

    it('should handle error', async () => {
      (Go.processPayment as any).mockRejectedValue(new Error('Process failed'));
      await expect(goPaymentsStore.processPayment({})).rejects.toThrow('Process failed');
      expect(state.error).toBe('Process failed');
    });
  });

  describe('addFunds', () => {
    it('should add funds to wallet', async () => {
      const wallet = { balance: 200 };
      (Go.addFunds as any).mockResolvedValue(wallet);
      state.wallet = { balance: 100 };

      const result = await goPaymentsStore.addFunds('w1', 100);

      expect(result).toEqual(wallet);
      expect(state.wallet).toEqual(wallet);
    });

    it('should handle error', async () => {
      (Go.addFunds as any).mockRejectedValue(new Error('AddFunds failed'));
      await expect(goPaymentsStore.addFunds('w1', 50)).rejects.toThrow('AddFunds failed');
      expect(state.error).toBe('AddFunds failed');
    });
  });

  describe('sendPayment', () => {
    it('should send payment and add transaction', async () => {
      const txn = { id: 't2' };
      (Go.sendPayment as any).mockResolvedValue(txn);
      state.transactions = [];

      const result = await goPaymentsStore.sendPayment('w1', 'w2', 50);

      expect(result).toEqual(txn);
      expect(state.transactions).toContain(txn);
    });

    it('should handle error', async () => {
      (Go.sendPayment as any).mockRejectedValue(new Error('Send failed'));
      await expect(goPaymentsStore.sendPayment('w1','w2',50)).rejects.toThrow('Send failed');
      expect(state.error).toBe('Send failed');
    });
  });

  describe('requestPayment', () => {
    it('should request payment and add transaction', async () => {
      const txn = { id: 't3' };
      (Go.requestPayment as any).mockResolvedValue(txn);
      state.transactions = [];

      const result = await goPaymentsStore.requestPayment({ from: 'w1', amount: 25, currency: 'USD' });

      expect(result).toEqual(txn);
      expect(state.transactions).toContain(txn);
    });

    it('should handle error', async () => {
      (Go.requestPayment as any).mockRejectedValue(new Error('Req failed'));
      await expect(goPaymentsStore.requestPayment({ from: 'w1', amount: 10, currency: 'USD' })).rejects.toThrow('Req failed');
      expect(state.error).toBe('Req failed');
    });
  });

  describe('reset', () => {
    it('should clear all state', () => {
      state.wallet = { balance: 100 };
      state.transactions = [{ id: 't1' }];
      state.error = 'err';
      goPaymentsStore.reset();
      expect(state.wallet).toBeNull();
      expect(state.transactions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('clearError', () => {
    it('should clear error after failure', async () => {
      (Go.getWallet as any).mockRejectedValue(new Error('test'));
      try { await goPaymentsStore.loadWallet('u1'); } catch (e) {}
      expect(state.error).toBe('test');
      goPaymentsStore.clearError();
      expect(state.error).toBeNull();
    });
  });
});
