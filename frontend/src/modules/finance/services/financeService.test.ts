import { describe, it, expect, vi, beforeEach } from 'vitest';
import { FinanceService } from './financeService';

global.fetch = vi.fn();

describe('FinanceService', () => {
  let service: FinanceService;

  beforeEach(() => {
    service = new FinanceService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getAccounts', () => {
    it('should fetch accounts', async () => {
      const accounts = [{ id: 'a1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(accounts));
      const result = await service.getAccounts();
      expect(result).toEqual(accounts);
      expect(fetch).toHaveBeenCalledWith('/api/finance/accounts');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getAccounts()).rejects.toThrow('Failed to fetch accounts');
    });
  });

  describe('getTransactions', () => {
    it('should fetch transactions for account', async () => {
      const txs = [{ id: 'tx1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(txs));
      const result = await service.getTransactions('acc1');
      expect(result).toEqual(txs);
      expect(fetch).toHaveBeenCalledWith('/api/finance/accounts/acc1/transactions');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getTransactions('acc1')).rejects.toThrow('Failed to fetch transactions');
    });
  });
});
