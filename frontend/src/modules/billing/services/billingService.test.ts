import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { BillingService } from './billingService';
import { __testing__ } from './billingService';

// Suppress console logs
vi.spyOn(console, 'info').mockImplementation(() => {});
vi.spyOn(console, 'warn').mockImplementation(() => {});
vi.spyOn(console, 'error').mockImplementation(() => {});

describe('BillingService', () => {
  let service: BillingService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new BillingService();
  });

  describe('getInvoices', () => {
    it('should fetch invoices', async () => {
      const invoices = [{ id: 'inv1', amount: 100 }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(invoices) });

      const result = await service.getInvoices();

      expect(result).toEqual(invoices);
      expect(global.fetch).toHaveBeenCalledWith('/api/billing/invoices');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getInvoices()).rejects.toThrow('Failed to fetch invoices');
    });
  });

  describe('getInvoice', () => {
    it('should fetch invoice by id', async () => {
      const invoice = { id: 'inv1', amount: 100 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(invoice) });

      const result = await service.getInvoice('inv1');

      expect(result).toEqual(invoice);
      expect(global.fetch).toHaveBeenCalledWith('/api/billing/invoices/inv1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getInvoice('inv1')).rejects.toThrow('Failed to fetch invoice');
    });
  });

  describe('createPayment', () => {
    const request = { amount: 100, method: 'card' };

    it('should create payment successfully', async () => {
      const payment = { id: 'pay1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(payment) });

      const result = await service.createPayment(request);

      expect(result).toEqual(payment);
      expect(global.fetch).toHaveBeenCalledWith('/api/billing/payments', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"amount":100')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.createPayment(request)).rejects.toThrow('Failed to create payment');
    });
  });

  describe('getPaymentMethods', () => {
    it('should fetch payment methods', async () => {
      const methods = [{ id: 'pm1', type: 'card' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(methods) });

      const result = await service.getPaymentMethods();

      expect(result).toEqual(methods);
      expect(global.fetch).toHaveBeenCalledWith('/api/billing/payment-methods');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getPaymentMethods()).rejects.toThrow('Failed to fetch payment methods');
    });
  });

  describe('addPaymentMethod', () => {
    const request = { type: 'card', details: '****1234' };

    it('should add payment method successfully', async () => {
      const method = { id: 'pm1', ...request };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(method) });

      const result = await service.addPaymentMethod(request);

      expect(result).toEqual(method);
      expect(global.fetch).toHaveBeenCalledWith('/api/billing/payment-methods', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"type":"card"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.addPaymentMethod(request)).rejects.toThrow('Failed to add payment method');
    });
  });

  describe('deletePaymentMethod', () => {
    it('should delete payment method', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(undefined) });

      await expect(service.deletePaymentMethod('pm1')).resolves.toBeUndefined();
      expect(global.fetch).toHaveBeenCalledWith('/api/billing/payment-methods/pm1', expect.objectContaining({ method: 'DELETE' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.deletePaymentMethod('pm1')).rejects.toThrow('Failed to delete payment method');
    });
  });

  describe('setDefaultPaymentMethod', () => {
    it('should set default payment method', async () => {
      const method = { id: 'pm1', default: true };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(method) });

      const result = await service.setDefaultPaymentMethod('pm1');

      expect(result).toEqual(method);
      expect(global.fetch).toHaveBeenCalledWith('/api/billing/payment-methods/pm1/default', expect.objectContaining({ method: 'PUT' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.setDefaultPaymentMethod('pm1')).rejects.toThrow('Failed to set default payment method');
    });
  });

  describe('getBillingHistory', () => {
    it('should fetch billing history', async () => {
      const history = [{ id: 'h1', description: 'Payment' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(history) });

      const result = await service.getBillingHistory();

      expect(result).toEqual(history);
      expect(global.fetch).toHaveBeenCalledWith('/api/billing/history');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getBillingHistory()).rejects.toThrow('Failed to fetch billing history');
    });
  });

  describe('getPayment', () => {
    it('should fetch payment by id', async () => {
      const payment = { id: 'pay1', amount: 100 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(payment) });

      const result = await service.getPayment('pay1');

      expect(result).toEqual(payment);
      expect(global.fetch).toHaveBeenCalledWith('/api/billing/payments/pay1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getPayment('pay1')).rejects.toThrow('Failed to fetch payment');
    });
  });
});
