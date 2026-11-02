import { describe, it, expect, vi, beforeEach } from 'vitest';
import { QRPaymentService } from './qrpaymentService';

global.fetch = vi.fn();

describe('QRPaymentService', () => {
  let service: QRPaymentService;

  beforeEach(() => {
    service = new QRPaymentService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getPayments', () => {
    it('should fetch all payments', async () => {
      const payments = [{ id: 'p1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(payments));
      const result = await service.getPayments();
      expect(result).toEqual(payments);
      expect(fetch).toHaveBeenCalledWith('/api/qrpayment');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getPayments()).rejects.toThrow('Failed to fetch payments');
    });
  });

  describe('getMyPayments', () => {
    it('should fetch my payments', async () => {
      const payments = [{ id: 'p2' }];
      (global.fetch as any).mockResolvedValue(mockResponse(payments));
      const result = await service.getMyPayments();
      expect(result).toEqual(payments);
      expect(fetch).toHaveBeenCalledWith('/api/qrpayment/my-payments');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getMyPayments()).rejects.toThrow('Failed to fetch my payments');
    });
  });

  describe('getPayment', () => {
    it('should fetch payment by id', async () => {
      const payment = { id: 'p1', userId: 'u1' };
      (global.fetch as any).mockResolvedValue(mockResponse(payment));
      const result = await service.getPayment('p1');
      expect(result).toEqual(payment);
      expect(fetch).toHaveBeenCalledWith('/api/qrpayment/p1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getPayment('p1')).rejects.toThrow('Failed to fetch payment');
    });
  });

  describe('verifyOwnership', () => {
    it('should return true if userId matches', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse({ userId: 'u1' }));
      const result = await service.verifyOwnership('p1', 'u1');
      expect(result).toBe(true);
    });

    it('should return false if userId does not match', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse({ userId: 'u2' }));
      const result = await service.verifyOwnership('p1', 'u1');
      expect(result).toBe(false);
    });

    it('should propagate error if fetch fails', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.verifyOwnership('p1', 'u1')).rejects.toThrow('Failed to fetch payment');
    });
  });

  describe('createPayment', () => {
    it('should create a payment', async () => {
      const created = { id: 'new' };
      (global.fetch as any).mockResolvedValue(mockResponse(created));
      const result = await service.createPayment('merchant123', 100);
      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/qrpayment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ merchantId: 'merchant123', amount: 100 })
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createPayment('m', 100)).rejects.toThrow('Failed to create payment');
    });
  });
});
