import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

global.fetch = vi.fn();

import { TtthService, setRetryWithBackoff, resetRetryWithBackoff } from './ttthService';

describe('TtthService', () => {
  let service: TtthService;

  beforeEach(() => {
    // Mock the retry function to execute immediately without delays
    setRetryWithBackoff(vi.fn(async (operation: () => Promise<any>) => {
      return operation();
    }));

    vi.clearAllMocks();
    service = new TtthService();
  });

  afterEach(() => {
    resetRetryWithBackoff();
    vi.restoreAllMocks();
  });

  describe('getAccounts', () => {
    it('should fetch accounts with default pagination', async () => {
      const result = { items: [{ id: 'a1', name: 'Account1' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getAccounts();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/ttth/accounts?page=1&limit=20');
    });

    it('should fetch with custom pagination', async () => {
      const result = { items: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getAccounts(2, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/ttth/accounts?page=2&limit=50');
    });

    it('should throw on invalid page params', async () => {
      await expect(service.getAccounts(0, 10)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getAccounts(1, 0)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getAccounts(1, 150)).rejects.toThrow('Invalid pagination parameters');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getAccounts()).rejects.toThrow('Failed to fetch accounts');
    });
  });

  describe('getAccount', () => {
    it('should fetch account by id', async () => {
      const account = { id: 'a1', name: 'Account1' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(account) });

      const result = await service.getAccount('a1');

      expect(result).toEqual(account);
      expect(global.fetch).toHaveBeenCalledWith('/api/ttth/accounts/a1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getAccount('a1')).rejects.toThrow('Failed to fetch account');
    });
  });

  describe('getTransactions', () => {
    it('should fetch transactions for account', async () => {
      const result = { items: [{ id: 't1', amount: 100 }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getTransactions('a1');

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/ttth/accounts/a1/transactions?page=1&limit=20');
    });

    it('should fetch with custom pagination', async () => {
      const result = { items: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getTransactions('a1', 2, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/ttth/accounts/a1/transactions?page=2&limit=50');
    });

    it('should throw on invalid pagination', async () => {
      await expect(service.getTransactions('a1', 0, 10)).rejects.toThrow('Invalid pagination parameters');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getTransactions('a1')).rejects.toThrow('Failed to fetch transactions');
    });
  });
});