import { describe, it, expect, vi, beforeEach } from 'vitest';
import { WalletService } from './walletService';
import { apiClient } from '../../../appcore/api-client';

// Mock the apiClient module
vi.mock('../../../appcore/api-client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn()
  }
}));

describe('WalletService', () => {
  let service: WalletService;

  beforeEach(() => {
    service = new WalletService();
    vi.clearAllMocks();
  });

  describe('getWallet', () => {
    it('should fetch wallet successfully', async () => {
      const wallet = { id: 'w1', balance: 100 };
      (apiClient.get as any).mockResolvedValue(wallet);

      const result = await service.getWallet();

      expect(result).toEqual(wallet);
      expect(apiClient.get).toHaveBeenCalledWith('/api/wallet');
    });

    it('should propagate errors from apiClient', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('API error'));

      await expect(service.getWallet()).rejects.toThrow('API error');
    });
  });

  describe('checkBalance', () => {
    it('should return balance number', async () => {
      (apiClient.get as any).mockResolvedValue({ balance: 250.5 });

      const result = await service.checkBalance();

      expect(result).toBe(250.5);
      expect(apiClient.get).toHaveBeenCalledWith('/api/wallet/balance');
    });

    it('should throw on apiClient error', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Fail'));

      await expect(service.checkBalance()).rejects.toThrow('Fail');
    });
  });

  describe('getTransactions', () => {
    it('should fetch transactions array', async () => {
      const txns = [{ id: 't1', amount: 50 }, { id: 't2', amount: -20 }];
      (apiClient.get as any).mockResolvedValue(txns);

      const result = await service.getTransactions();

      expect(result).toEqual(txns);
      expect(apiClient.get).toHaveBeenCalledWith('/api/wallet/transactions');
    });

    it('should throw on failure', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Network'));

      await expect(service.getTransactions()).rejects.toThrow('Network');
    });
  });

  describe('deposit', () => {
    it('should deposit and return result', async () => {
      const req = { amount: 100, method: 'card' };
      const resp = { id: 'dep1', status: 'completed' };
      (apiClient.post as any).mockResolvedValue(resp);

      const result = await service.deposit(req);

      expect(result).toEqual(resp);
      expect(apiClient.post).toHaveBeenCalledWith('/api/wallet/deposit', req);
    });

    it('should throw on deposit failure', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Deposit failed'));

      await expect(service.deposit({ amount: 10 })).rejects.toThrow('Deposit failed');
    });
  });

  describe('withdraw', () => {
    it('should withdraw successfully', async () => {
      const req = { amount: 50 };
      const resp = { id: 'wit1', status: 'pending' };
      (apiClient.post as any).mockResolvedValue(resp);

      const result = await service.withdraw(req);

      expect(result).toEqual(resp);
      expect(apiClient.post).toHaveBeenCalledWith('/api/wallet/withdraw', req);
    });

    it('should throw on withdraw failure', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Insufficient funds'));

      await expect(service.withdraw({ amount: 1000 })).rejects.toThrow('Insufficient funds');
    });
  });

  describe('transfer', () => {
    it('should transfer successfully', async () => {
      const req = { to: 'user2', amount: 30 };
      const resp = { id: 'tr1', status: 'completed' };
      (apiClient.post as any).mockResolvedValue(resp);

      const result = await service.transfer(req);

      expect(result).toEqual(resp);
      expect(apiClient.post).toHaveBeenCalledWith('/api/wallet/transfer', req);
    });

    it('should throw on transfer failure', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Recipient not found'));

      await expect(service.transfer({ to: 'unknown', amount: 10 })).rejects.toThrow('Recipient not found');
    });
  });

  describe('getTransactionById', () => {
    it('should fetch transaction by id', async () => {
      const txn = { id: 'txn1', amount: 100 };
      (apiClient.get as any).mockResolvedValue(txn);

      const result = await service.getTransactionById('txn1');

      expect(result).toEqual(txn);
      expect(apiClient.get).toHaveBeenCalledWith('/api/wallet/transactions/txn1');
    });

    it('should throw when transaction not found', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Not found'));

      await expect(service.getTransactionById('invalid')).rejects.toThrow('Not found');
    });
  });
});
