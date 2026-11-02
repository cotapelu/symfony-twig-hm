import { describe, it, expect, vi, beforeEach } from 'vitest';
import { walletService } from './wallet';
import { getApiUrl } from '../config/api';

// Mock the api config
vi.mock('../config/api', () => ({
  getApiUrl: vi.fn(() => '/'),
}));

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock localStorage
const mockLocalStorage = {
  getItem: vi.fn(() => 'test-token'),
};
Object.defineProperty(global, 'localStorage', { value: mockLocalStorage });

describe('WalletService', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    mockLocalStorage.getItem.mockClear();
    vi.mocked(getApiUrl).mockClear();
  });

  describe('getWallet', () => {
    it('fetches wallet for user', async () => {
      const wallet = { id: 'w1', userId: 'u1', balance: 100, currency: 'USD', status: 'active', isDefault: true };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => wallet });

      const result = await walletService.getWallet('u1');

      expect(getApiUrl).toHaveBeenCalledWith('wallet', '/api/wallet/u1');
      expect(mockFetch).toHaveBeenCalledWith('/', { headers: expect.objectContaining({ Authorization: 'Bearer test-token' }) });
      expect(result).toEqual(wallet);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(walletService.getWallet('u1')).rejects.toThrow('Failed to fetch wallet');
    });
  });

  describe('getBalance', () => {
    it('fetches balance', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ balance: 250 }) });

      const balance = await walletService.getBalance('u1');

      expect(getApiUrl).toHaveBeenCalledWith('wallet', '/api/wallet/u1/balance');
      expect(balance).toBe(250);
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(walletService.getBalance('u1')).rejects.toThrow('Failed to fetch balance');
    });
  });

  describe('topUp', () => {
    it('creates top-up transaction', async () => {
      const tx = { id: 't1', walletId: 'w1', type: 'topup', amount: 50, balanceAfter: 150, description: 'Top up', status: 'completed' };
      const request = { userId: 'u1', amount: 50, method: 'card' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => tx });

      const result = await walletService.topUp(request);

      expect(getApiUrl).toHaveBeenCalledWith('wallet', '/api/wallet/topup');
      expect(mockFetch).toHaveBeenCalledWith('/', {
        method: 'POST',
        headers: expect.objectContaining({ Authorization: 'Bearer test-token' }),
        body: JSON.stringify(request),
      });
      expect(result).toEqual(tx);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(walletService.topUp({ userId: 'u1', amount: 10, method: 'card' })).rejects.toThrow('Failed to top up');
    });
  });

  describe('transfer', () => {
    it('creates transfer transaction', async () => {
      const tx = { id: 't2', walletId: 'w1', type: 'transfer', amount: 30, balanceAfter: 120, description: 'Transfer', status: 'completed' };
      const request = { fromUserId: 'u1', toUserId: 'u2', amount: 30 };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => tx });

      const result = await walletService.transfer(request);

      expect(getApiUrl).toHaveBeenCalledWith('wallet', '/api/wallet/transfer');
      expect(result).toEqual(tx);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(walletService.transfer({ fromUserId: 'u1', toUserId: 'u2', amount: 10 })).rejects.toThrow('Failed to transfer');
    });
  });

  describe('withdraw', () => {
    it('creates withdrawal transaction', async () => {
      const tx = { id: 't3', walletId: 'w1', type: 'withdrawal', amount: 20, balanceAfter: 100, description: 'Withdraw', status: 'pending' };
      const request = { userId: 'u1', amount: 20, bankCode: '001', accountNo: '123456', holderName: 'John Doe' };
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => tx });

      const result = await walletService.withdraw(request);

      expect(getApiUrl).toHaveBeenCalledWith('wallet', '/api/wallet/withdraw');
      expect(result).toEqual(tx);
    });

    it('throws on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(walletService.withdraw({ userId: 'u1', amount: 10, bankCode: '001', accountNo: '123', holderName: 'Jane' })).rejects.toThrow('Failed to withdraw');
    });
  });

  describe('getTransactions', () => {
    it('fetches transactions with pagination', async () => {
      const txList = [{ id: 't1' }, { id: 't2' }];
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({ transactions: txList }) });

      const result = await walletService.getTransactions('u1', 10, 0);

      expect(getApiUrl).toHaveBeenCalledWith('wallet', '/api/wallet/u1/transactions?limit=10&offset=0');
      expect(result).toEqual(txList);
    });

    it('returns empty array if no transactions', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      const result = await walletService.getTransactions('u1');
      expect(result).toEqual([]);
    });

    it('uses default limit/offset when omitted', async () => {
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });
      await walletService.getTransactions('u1');
      const calledUrl = getApiUrl.mock.calls[0][1];
      expect(calledUrl).toBe('/api/wallet/u1/transactions?limit=20&offset=0');
    });

    it('throws on error', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });
      await expect(walletService.getTransactions('u1')).rejects.toThrow('Failed to fetch transactions');
    });
  });
});
