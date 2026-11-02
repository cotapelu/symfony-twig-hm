import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { BankingService } from './bankingService';
import { __testing__ } from './bankingService';

describe('BankingService', () => {
  let service: BankingService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new BankingService();
  });

  describe('getAccounts', () => {
    it('should fetch accounts', async () => {
      const accounts = [{ id: 'a1', balance: 1000 }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(accounts) });

      const result = await service.getAccounts();

      expect(result).toEqual(accounts);
      expect(global.fetch).toHaveBeenCalledWith('/api/banking/accounts');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getAccounts()).rejects.toThrow('Failed to fetch accounts');
    });
  });

  describe('getAccount', () => {
    it('should fetch account by id', async () => {
      const account = { id: 'a1', balance: 1000 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(account) });

      const result = await service.getAccount('a1');

      expect(result).toEqual(account);
      expect(global.fetch).toHaveBeenCalledWith('/api/banking/accounts/a1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getAccount('a1')).rejects.toThrow('Failed to fetch account');
    });
  });

  describe('getTransactions', () => {
    it('should fetch transactions for account', async () => {
      const transactions = [{ id: 't1', amount: 100 }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(transactions) });

      const result = await service.getTransactions('a1');

      expect(result).toEqual(transactions);
      expect(global.fetch).toHaveBeenCalledWith('/api/banking/accounts/a1/transactions');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getTransactions('a1')).rejects.toThrow('Failed to fetch transactions');
    });
  });

  describe('createTransfer', () => {
    const request = { from: 'a1', to: 'a2', amount: 100, description: 'Test' };

    it('should create transfer successfully', async () => {
      const transaction = { id: 't1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(transaction) });

      const result = await service.createTransfer(request);

      expect(result).toEqual(transaction);
      expect(global.fetch).toHaveBeenCalledWith('/api/banking/transfers', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"from":"a1"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createTransfer(request)).rejects.toThrow('Failed to create transfer');
    });
  });

  describe('getLoans', () => {
    it('should fetch loans', async () => {
      const loans = [{ id: 'l1', amount: 5000 }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(loans) });

      const result = await service.getLoans();

      expect(result).toEqual(loans);
      expect(global.fetch).toHaveBeenCalledWith('/api/banking/loans');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getLoans()).rejects.toThrow('Failed to fetch loans');
    });
  });

  describe('applyForLoan', () => {
    const request = { amount: 10000, term: 12, purpose: 'Home' };

    it('should apply for loan successfully', async () => {
      const loan = { id: 'l1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(loan) });

      const result = await service.applyForLoan(request);

      expect(result).toEqual(loan);
      expect(global.fetch).toHaveBeenCalledWith('/api/banking/loans', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"amount":10000')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.applyForLoan(request)).rejects.toThrow('Failed to apply for loan');
    });
  });

  describe('getCreditCards', () => {
    it('should fetch credit cards', async () => {
      const cards = [{ id: 'c1', number: '1234' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(cards) });

      const result = await service.getCreditCards();

      expect(result).toEqual(cards);
      expect(global.fetch).toHaveBeenCalledWith('/api/banking/credit-cards');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getCreditCards()).rejects.toThrow('Failed to fetch credit cards');
    });
  });

  describe('blockCreditCard', () => {
    it('should block credit card', async () => {
      const card = { id: 'c1', blocked: true };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(card) });

      const result = await service.blockCreditCard('c1');

      expect(result).toEqual(card);
      expect(global.fetch).toHaveBeenCalledWith('/api/banking/credit-cards/c1/block', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.blockCreditCard('c1')).rejects.toThrow('Failed to block credit card');
    });
  });
});
