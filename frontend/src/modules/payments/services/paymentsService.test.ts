import { describe, it, expect, vi, beforeEach } from 'vitest';
import { PaymentsService } from './paymentsService';

// Mock fetch globally
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('PaymentsService', () => {
  let service: PaymentsService;

  beforeEach(() => {
    service = new PaymentsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => {
    const response: any = { ok };
    response.json = async () => data;
    return response;
  };

  describe('getPaymentMethods', () => {
    it('should fetch payment methods successfully', async () => {
      mockFetch.mockResolvedValue(mockResponse(['card1', 'card2']));

      const result = await service.getPaymentMethods();

      expect(result).toEqual(['card1', 'card2']);
      expect(mockFetch).toHaveBeenCalledWith('/api/payments/methods');
    });

    it('should throw when response not ok', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.getPaymentMethods()).rejects.toThrow('Failed to fetch payment methods');
    });
  });

  describe('addPaymentMethod', () => {
    it('should add payment method successfully', async () => {
      const method = { type: 'card', last4: '1234' };
      const created = { id: 'new-id', ...method };
      mockFetch.mockResolvedValue(mockResponse(created));

      const result = await service.addPaymentMethod(method);

      expect(result).toEqual(created);
      expect(mockFetch).toHaveBeenCalledWith('/api/payments/methods', expect.objectContaining({
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(method)
      }));
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.addPaymentMethod({ type: 'card' })).rejects.toThrow('Failed to add payment method');
    });
  });

  describe('removePaymentMethod', () => {
    it('should remove payment method successfully', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, true));

      await service.removePaymentMethod('method-id');

      expect(mockFetch).toHaveBeenCalledWith('/api/payments/methods/method-id', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.removePaymentMethod('method-id')).rejects.toThrow('Failed to remove payment method');
    });
  });

  describe('getTransactions', () => {
    it('should fetch transactions successfully', async () => {
      const txns = [{ id: 't1', amount: 100 }];
      mockFetch.mockResolvedValue(mockResponse(txns));

      const result = await service.getTransactions();

      expect(result).toEqual(txns);
      expect(mockFetch).toHaveBeenCalledWith('/api/payments/transactions');
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.getTransactions()).rejects.toThrow('Failed to fetch transactions');
    });
  });

  describe('processPayment', () => {
    it('should process payment successfully', async () => {
      const paymentReq = { amount: 100, methodId: 'card' };
      const resp = { transactionId: 'txn', status: 'completed' };
      mockFetch.mockResolvedValue(mockResponse(resp));

      const result = await service.processPayment(paymentReq);

      expect(result).toEqual(resp);
      expect(mockFetch).toHaveBeenCalledWith('/api/payments/process', expect.objectContaining({
        method: 'POST',
        body: JSON.stringify(paymentReq)
      }));
    });

    it('should throw with error message from response body', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        json: async () => ({ message: 'Insufficient funds' })
      });

      await expect(service.processPayment({ amount: 100, methodId: 'card' })).rejects.toThrow('Insufficient funds');
    });

    it('should throw generic Payment failed if no message', async () => {
      mockFetch.mockResolvedValue({
        ok: false,
        json: async () => ({})
      });

      await expect(service.processPayment({ amount: 100, methodId: 'card' })).rejects.toThrow('Payment failed');
    });
  });

  describe('refundPayment', () => {
    it('should refund payment successfully', async () => {
      const refund = { refundId: 'r1' };
      mockFetch.mockResolvedValue(mockResponse(refund));

      const result = await service.refundPayment('txn-1', 50);

      expect(result).toEqual(refund);
      expect(mockFetch).toHaveBeenCalledWith('/api/payments/refund', expect.objectContaining({
        method: 'POST',
        body: JSON.stringify({ transactionId: 'txn-1', amount: 50 })
      }));
    });

    it('should refund full amount when amount omitted', async () => {
      mockFetch.mockResolvedValue(mockResponse({ refundId: 'r1' }));

      await service.refundPayment('txn-1');

      expect(mockFetch).toHaveBeenCalledWith('/api/payments/refund', expect.objectContaining({
        body: JSON.stringify({ transactionId: 'txn-1' }) 
      }));
    });

    it('should throw on refund failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.refundPayment('txn-1')).rejects.toThrow('Refund failed');
    });
  });
});
