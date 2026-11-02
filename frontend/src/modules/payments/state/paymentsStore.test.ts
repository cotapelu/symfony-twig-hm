import { describe, it, expect, vi, beforeEach } from 'vitest';
import { paymentsStore } from './paymentsStore';
import { PaymentsService } from '../services/paymentsService';
import type { PaymentMethod, Transaction, PaymentRequest, PaymentResponse } from '../contracts/paymentsTypes';

function getCurrentState() {
  let current: any;
  const unsub = paymentsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date().toISOString();

const mockPaymentMethod = (overrides: Partial<PaymentMethod> = {}): PaymentMethod => ({
  id: `meth-${Math.random().toString(36).substr(2, 5)}`,
  type: 'card',
  cardType: 'visa',
  last4: '4242',
  expiry: '12/25',
  name: 'John Card',
  isDefault: true,
  createdAt: now,
  ...overrides
});

const mockTransaction = (overrides: Partial<Transaction> = {}): Transaction => ({
  id: `trx-${Math.random().toString(36).substr(2, 5)}`,
  amount: 100,
  currency: 'USD',
  description: 'Test',
  status: 'completed',
  paymentMethodId: 'meth-1',
  date: now,
  ...overrides
});

const mockPaymentResponse = (overrides: Partial<PaymentResponse> = {}): PaymentResponse => ({
  id: `pay-${Math.random().toString(36).substr(2, 5)}`,
  status: 'success',
  amount: 100,
  currency: 'USD',
  timestamp: now,
  ...overrides
});

describe('PaymentsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    paymentsStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and null currentPayment', () => {
      const state = getCurrentState();
      expect(state.paymentMethods).toEqual([]);
      expect(state.transactions).toEqual([]);
      expect(state.currentPayment).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('initialize', () => {
    it('loads payment methods and transactions in parallel', async () => {
      const methods = [mockPaymentMethod(), mockPaymentMethod()];
      const trans = [mockTransaction(), mockTransaction()];
      vi.spyOn(PaymentsService.prototype, 'getPaymentMethods').mockResolvedValue(methods);
      vi.spyOn(PaymentsService.prototype, 'getTransactions').mockResolvedValue(trans);
      await paymentsStore.initialize();
      const state = getCurrentState();
      expect(state.paymentMethods).toEqual(methods);
      expect(state.transactions).toEqual(trans);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(PaymentsService.prototype, 'getPaymentMethods').mockRejectedValue(new Error('Methods fail'));
      // getTransactions also fails due to Promise.all rejection
      await paymentsStore.initialize();
      const state = getCurrentState();
      expect(state.error).toBe('Methods fail');
      expect(state.paymentMethods).toEqual([]);
      expect(state.transactions).toEqual([]);
    });
  });

  describe('loadPaymentMethods', () => {
    it('loads payment methods', async () => {
      const methods = [mockPaymentMethod()];
      vi.spyOn(PaymentsService.prototype, 'getPaymentMethods').mockResolvedValue(methods);
      await paymentsStore.loadPaymentMethods();
      const state = getCurrentState();
      expect(state.paymentMethods).toEqual(methods);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(PaymentsService.prototype, 'getPaymentMethods').mockRejectedValue(new Error('Methods fail'));
      await paymentsStore.loadPaymentMethods();
      const state = getCurrentState();
      expect(state.error).toBe('Methods fail');
      expect(state.paymentMethods).toEqual([]);
    });
  });

  describe('addPaymentMethod', () => {
    it('adds payment method to list', async () => {
      const newMethod = mockPaymentMethod({ id: 'new-meth' });
      vi.spyOn(PaymentsService.prototype, 'addPaymentMethod').mockResolvedValue(newMethod);
      const result = await paymentsStore.addPaymentMethod({ type: 'card' });
      expect(result).toEqual(newMethod);
      let state = getCurrentState();
      expect(state.paymentMethods[0]).toEqual(newMethod);
    });

    it('propagates errors', async () => {
      vi.spyOn(PaymentsService.prototype, 'addPaymentMethod').mockRejectedValue(new Error('Add failed'));
      try {
        await paymentsStore.addPaymentMethod({ type: 'card' });
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Add failed');
    });
  });

  describe('removePaymentMethod', () => {
    it('removes payment method by id', async () => {
      const meth1 = mockPaymentMethod({ id: 'm1' });
      const meth2 = mockPaymentMethod({ id: 'm2' });
      vi.spyOn(PaymentsService.prototype, 'getPaymentMethods').mockResolvedValue([meth1, meth2]);
      await paymentsStore.loadPaymentMethods();
      vi.spyOn(PaymentsService.prototype, 'removePaymentMethod').mockResolvedValue(undefined);
      await paymentsStore.removePaymentMethod('m1');
      let state = getCurrentState();
      expect(state.paymentMethods.find(m => m.id === 'm1')).toBeUndefined();
      expect(state.paymentMethods.length).toBe(1);
    });

    it('handles error', async () => {
      const meth = mockPaymentMethod();
      vi.spyOn(PaymentsService.prototype, 'getPaymentMethods').mockResolvedValue([meth]);
      await paymentsStore.loadPaymentMethods();
      vi.spyOn(PaymentsService.prototype, 'removePaymentMethod').mockRejectedValue(new Error('Remove failed'));
      try {
        await paymentsStore.removePaymentMethod('m1');
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Remove failed');
    });
  });

  describe('loadTransactions', () => {
    it('loads transactions', async () => {
      const trans = [mockTransaction(), mockTransaction()];
      vi.spyOn(PaymentsService.prototype, 'getTransactions').mockResolvedValue(trans);
      await paymentsStore.loadTransactions();
      const state = getCurrentState();
      expect(state.transactions).toEqual(trans);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(PaymentsService.prototype, 'getTransactions').mockRejectedValue(new Error('Trans fail'));
      await paymentsStore.loadTransactions();
      const state = getCurrentState();
      expect(state.error).toBe('Trans fail');
      expect(state.transactions).toEqual([]);
    });
  });

  describe('processPayment', () => {
    it('processes payment, sets currentPayment, and prepends transaction', async () => {
      const response = mockPaymentResponse({ id: 'pay-123', status: 'success' });
      vi.spyOn(PaymentsService.prototype, 'processPayment').mockResolvedValue(response);
      const request: PaymentRequest = { amount: 50, currency: 'USD', paymentMethodId: 'meth-1' };
      const result = await paymentsStore.processPayment(request);
      expect(result).toEqual(response);
      let state = getCurrentState();
      expect(state.currentPayment).toEqual(response);
      expect(state.transactions.length).toBe(1);
      expect(state.transactions[0].amount).toBe(100);
      expect(state.transactions[0].status).toBe('completed');
    });

    it('handles payment failure', async () => {
      vi.spyOn(PaymentsService.prototype, 'processPayment').mockRejectedValue(new Error('Card declined'));
      try {
        await paymentsStore.processPayment({ amount: 10, currency: 'USD' });
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Card declined');
      expect(state.currentPayment).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(PaymentsService.prototype, 'getPaymentMethods').mockRejectedValue(new Error('test'));
      try { await paymentsStore.loadPaymentMethods(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      paymentsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const methods = [mockPaymentMethod()];
      const trans = [mockTransaction()];
      vi.spyOn(PaymentsService.prototype, 'getPaymentMethods').mockResolvedValue(methods);
      vi.spyOn(PaymentsService.prototype, 'getTransactions').mockResolvedValue(trans);
      await paymentsStore.initialize();
      let state = getCurrentState();
      expect(state.paymentMethods.length).toBeGreaterThan(0);
      expect(state.transactions.length).toBeGreaterThan(0);
      paymentsStore.reset();
      state = getCurrentState();
      expect(state.paymentMethods).toEqual([]);
      expect(state.transactions).toEqual([]);
      expect(state.currentPayment).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
