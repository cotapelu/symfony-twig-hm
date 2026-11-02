import { describe, it, expect, vi, beforeEach } from 'vitest';
import { get } from 'svelte/store';
import { walletStore, walletBalance, walletCurrency } from './walletStore';

// Mock fetch
const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

describe('walletStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('initial state', () => {
    it('should have correct initial state', () => {
      const state = get(walletStore);
      expect(state.wallet).toBeNull();
      expect(state.transactions).toEqual([]);
      expect(state.paymentMethods).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('should expose derived stores', () => {
      expect(get(walletBalance)).toBe(0);
      expect(get(walletCurrency)).toBe('VND');
    });
  });

  describe('loadWallet', () => {
    it('should load wallet successfully', async () => {
      const mockWallet = { id: 1, user_id: 'user1', balance: 100000, currency: 'VND' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ wallet: mockWallet }) });

      const result = await walletStore.loadWallet('user1');

      expect(result).toEqual(mockWallet);
      const state = get(walletStore);
      expect(state.wallet).toEqual(mockWallet);
      expect(state.loading).toBe(false);
    });

    it('should handle load wallet error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });

      await expect(walletStore.loadWallet('user1')).rejects.toThrow('Failed to load wallet');
      
      const state = get(walletStore);
      expect(state.error).toBe('Failed to load wallet');
    });

    it('should handle network error', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      await expect(walletStore.loadWallet('user1')).rejects.toThrow('Network error');
    });
  });

  describe('loadTransactions', () => {
    it('should load transactions successfully', async () => {
      const mockTransactions = [{ id: 1, amount: 1000 }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ transactions: mockTransactions }) });

      const result = await walletStore.loadTransactions('user1');

      expect(result).toEqual(mockTransactions);
      const state = get(walletStore);
      expect(state.transactions).toEqual(mockTransactions);
    });

    it('should handle load transactions error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });

      await expect(walletStore.loadTransactions('user1')).rejects.toThrow('Failed to load transactions');
    });
  });

  describe('loadPaymentMethods', () => {
    it('should load payment methods successfully', async () => {
      const mockMethods = [{ method: 'card', name: 'Credit Card' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ methods: mockMethods }) });

      const result = await walletStore.loadPaymentMethods('user1');

      expect(result).toEqual(mockMethods);
      const state = get(walletStore);
      expect(state.paymentMethods).toEqual(mockMethods);
    });

    it('should handle load payment methods error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });

      await expect(walletStore.loadPaymentMethods('user1')).rejects.toThrow('Failed to load payment methods');
    });
  });

  describe('createWallet', () => {
    it('should create wallet successfully', async () => {
      const mockWallet = { id: 1, user_id: 'user1', balance: 0, currency: 'VND' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ wallet: mockWallet }) });

      const result = await walletStore.createWallet('user1');

      expect(result).toEqual(mockWallet);
      const state = get(walletStore);
      expect(state.wallet).toEqual(mockWallet);
    });

    it('should handle create wallet error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });

      await expect(walletStore.createWallet('user1')).rejects.toThrow('Failed to create wallet');
    });
  });

  describe('topUp', () => {
    it('should top up successfully', async () => {
      mockFetch.mockResolvedValue({ ok: true, json: () => Promise.resolve({ success: true }) });

      await walletStore.topUp('user1', 100000, 'card');

      expect(mockFetch).toHaveBeenCalledWith('/api/wallet/topup', expect.objectContaining({
        method: 'POST'
      }));
    });

    it('should handle top up error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });

      await expect(walletStore.topUp('user1', 100000, 'card')).rejects.toThrow('Top up failed');
    });
  });

  describe('transfer', () => {
    it('should transfer successfully', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ success: true }) });

      await walletStore.transfer('user1', 'user2', 50000);

      expect(mockFetch).toHaveBeenCalledWith('/api/wallet/transfer', expect.objectContaining({
        method: 'POST'
      }));
    });

    it('should handle transfer error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });

      await expect(walletStore.transfer('user1', 'user2', 50000)).rejects.toThrow('Transfer failed');
    });
  });

  describe('clearError', () => {
    it('should clear error state', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await walletStore.loadWallet('user1').catch(() => {});
      
      expect(get(walletStore).error).toBeTruthy();
      
      walletStore.clearError();
      expect(get(walletStore).error).toBeNull();
    });
  });

  describe('derived stores', () => {
    it('should derive walletBalance from wallet', async () => {
      const mockWallet = { id: 1, user_id: 'user1', balance: 500000, currency: 'VND' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ wallet: mockWallet }) });

      await walletStore.loadWallet('user1');

      expect(get(walletBalance)).toBe(500000);
    });

    it('should derive walletCurrency from wallet', async () => {
      const mockWallet = { id: 1, user_id: 'user1', balance: 0, currency: 'USD' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({ wallet: mockWallet }) });

      await walletStore.loadWallet('user1');

      expect(get(walletCurrency)).toBe('USD');
    });
  });
});
