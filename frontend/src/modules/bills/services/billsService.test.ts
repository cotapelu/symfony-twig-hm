import { describe, it, expect, vi, beforeEach } from 'vitest';
import { BillsService } from './billsService';

global.fetch = vi.fn();

describe('BillsService', () => {
  let service: BillsService;

  beforeEach(() => {
    service = new BillsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getBills', () => {
    it('should fetch bills', async () => {
      const bills = [{ id: 'b1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(bills));
      const result = await service.getBills();
      expect(result).toEqual(bills);
      expect(fetch).toHaveBeenCalledWith('/api/bills/bills');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getBills()).rejects.toThrow('Failed to fetch bills');
    });
  });

  describe('getBill', () => {
    it('should fetch bill by id', async () => {
      const bill = { id: 'b1' };
      (global.fetch as any).mockResolvedValue(mockResponse(bill));
      const result = await service.getBill('b1');
      expect(result).toEqual(bill);
      expect(fetch).toHaveBeenCalledWith('/api/bills/bills/b1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getBill('b1')).rejects.toThrow('Failed to fetch bill');
    });
  });

  describe('getContracts', () => {
    it('should fetch contracts', async () => {
      const contracts = [{ id: 'c1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(contracts));
      const result = await service.getContracts();
      expect(result).toEqual(contracts);
      expect(fetch).toHaveBeenCalledWith('/api/bills/contracts');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getContracts()).rejects.toThrow('Failed to fetch contracts');
    });
  });

  describe('getContract', () => {
    it('should fetch contract by id', async () => {
      const contract = { id: 'c1' };
      (global.fetch as any).mockResolvedValue(mockResponse(contract));
      const result = await service.getContract('c1');
      expect(result).toEqual(contract);
      expect(fetch).toHaveBeenCalledWith('/api/bills/contracts/c1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getContract('c1')).rejects.toThrow('Failed to fetch contract');
    });
  });

  describe('payBill', () => {
    it('should pay a bill', async () => {
      const payment = { id: 'p1' };
      (global.fetch as any).mockResolvedValue(mockResponse(payment));
      const request = { billId: 'b1', amount: 100 };
      const result = await service.payBill(request);
      expect(result).toEqual(payment);
      expect(fetch).toHaveBeenCalledWith('/api/bills/payments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.payBill({ billId: 'b1', amount: 100 })).rejects.toThrow('Failed to pay bill');
    });
  });

  describe('getPaymentHistory', () => {
    it('should fetch payment history', async () => {
      const history = [{ id: 'ph1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(history));
      const result = await service.getPaymentHistory();
      expect(result).toEqual(history);
      expect(fetch).toHaveBeenCalledWith('/api/bills/payments');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getPaymentHistory()).rejects.toThrow('Failed to fetch payment history');
    });
  });
});
