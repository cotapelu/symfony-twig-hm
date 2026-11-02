import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  billingStore,
  invoices,
  paymentMethods,
  defaultPaymentMethod,
  billingHistory,
  billingLoading,
  billingError
} from './billingStore';
import { BillingService } from '../services/billingService';
import type { Invoice, PaymentMethod, Payment, BillingHistory as BillingHistoryType } from '../contracts/billingTypes';

function getCurrentState() {
  let current: any;
  const unsub = billingStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockInvoice = (overrides: Partial<Invoice> = {}): Invoice => ({
  id: `inv-${Math.random().toString(36).substr(2, 5)}`,
  number: 'INV001',
  amount: 100,
  currency: 'USD',
  status: 'pending',
  dueDate: new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000),
  createdAt: now,
  ...overrides
});

const mockPaymentMethod = (overrides: Partial<PaymentMethod> = {}): PaymentMethod => ({
  id: `pm-${Math.random().toString(36).substr(2, 5)}`,
  type: 'card',
  lastFour: '4242',
  brand: 'Visa',
  isDefault: false,
  createdAt: now,
  ...overrides
});

const mockPayment = (overrides: Partial<Payment> = {}): Payment => ({
  id: `pay-${Math.random().toString(36).substr(2, 5)}`,
  invoiceId: 'inv-1',
  paymentMethodId: 'pm-1',
  amount: 100,
  currency: 'USD',
  status: 'succeeded',
  createdAt: now,
  ...overrides
});

const mockHistory = (overrides: Partial<BillingHistoryType> = {}): BillingHistoryType => ({
  id: `hist-${Math.random().toString(36).substr(2, 5)}`,
  type: 'payment',
  amount: 100,
  currency: 'USD',
  description: 'Payment for invoice',
  createdAt: now,
  ...overrides
});

describe('BillingStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    billingStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.invoices).toEqual([]);
      expect(state.paymentMethods).toEqual([]);
      expect(state.payments).toEqual([]);
      expect(state.history).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let invs: Invoice[] = [], pms: PaymentMethod[] = [], def: PaymentMethod | undefined = [], hist: BillingHistoryType[] = [], l = false, e: string | null = null;
      const unsubI = invoices.subscribe(s => { invs = s; });
      const unsubPM = paymentMethods.subscribe(s => { pms = s; });
      const unsubDef = defaultPaymentMethod.subscribe(s => { def = s; });
      const unsubH = billingHistory.subscribe(s => { hist = s; });
      const unsubL = billingLoading.subscribe(s => { l = s; });
      const unsubE = billingError.subscribe(s => { e = s; });
      expect(invs).toEqual([]);
      expect(pms).toEqual([]);
      expect(def).toBeUndefined(); // defaultPaymentMethod returns undefined if none
      expect(hist).toEqual([]);
      expect(l).toBe(false);
      expect(e).toBeNull();
      unsubI(); unsubPM(); unsubDef(); unsubH(); unsubL(); unsubE();
    });
  });

  describe('loadInvoices', () => {
    it('loads invoices', async () => {
      const invs = [mockInvoice(), mockInvoice()];
      vi.spyOn(BillingService.prototype, 'getInvoices').mockResolvedValue(invs);
      await billingStore.loadInvoices();
      const state = getCurrentState();
      expect(state.invoices).toEqual(invs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(BillingService.prototype, 'getInvoices').mockReturnValue(p as any);
      const loadPromise = billingStore.loadInvoices();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockInvoice()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates invoices derived on success', async () => {
      const invs = [mockInvoice()];
      vi.spyOn(BillingService.prototype, 'getInvoices').mockResolvedValue(invs);
      await billingStore.loadInvoices();
      let i: Invoice[] = [];
      const unsub = invoices.subscribe(s => { i = s; });
      expect(i).toEqual(invs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(BillingService.prototype, 'getInvoices').mockResolvedValue([]);
      await billingStore.loadInvoices();
      expect(getCurrentState().invoices).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(BillingService.prototype, 'getInvoices').mockRejectedValue(new Error('Invoices failed'));
      await billingStore.loadInvoices();
      const state = getCurrentState();
      expect(state.error).toBe('Invoices failed');
      expect(state.loading).toBe(false);
      expect(state.invoices).toEqual([]);
    });
  });

  describe('loadPaymentMethods', () => {
    it('loads payment methods', async () => {
      const pms = [mockPaymentMethod(), mockPaymentMethod()];
      vi.spyOn(BillingService.prototype, 'getPaymentMethods').mockResolvedValue(pms);
      await billingStore.loadPaymentMethods();
      const state = getCurrentState();
      expect(state.paymentMethods).toEqual(pms);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(BillingService.prototype, 'getPaymentMethods').mockReturnValue(p as any);
      const loadPromise = billingStore.loadPaymentMethods();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockPaymentMethod()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates paymentMethods derived on success', async () => {
      const pms = [mockPaymentMethod()];
      vi.spyOn(BillingService.prototype, 'getPaymentMethods').mockResolvedValue(pms);
      await billingStore.loadPaymentMethods();
      let p: PaymentMethod[] = [];
      const unsub = paymentMethods.subscribe(s => { p = s; });
      expect(p).toEqual(pms);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(BillingService.prototype, 'getPaymentMethods').mockResolvedValue([]);
      await billingStore.loadPaymentMethods();
      expect(getCurrentState().paymentMethods).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(BillingService.prototype, 'getPaymentMethods').mockRejectedValue(new Error('PM failed'));
      await billingStore.loadPaymentMethods();
      const state = getCurrentState();
      expect(state.error).toBe('PM failed');
      expect(state.loading).toBe(false);
      expect(state.paymentMethods).toEqual([]);
    });
  });

  describe('loadBillingHistory', () => {
    it('loads billing history', async () => {
      const hist = [mockHistory(), mockHistory()];
      vi.spyOn(BillingService.prototype, 'getBillingHistory').mockResolvedValue(hist);
      await billingStore.loadBillingHistory();
      const state = getCurrentState();
      expect(state.history).toEqual(hist);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(BillingService.prototype, 'getBillingHistory').mockReturnValue(p as any);
      const loadPromise = billingStore.loadBillingHistory();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockHistory()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates billingHistory derived on success', async () => {
      const hist = [mockHistory()];
      vi.spyOn(BillingService.prototype, 'getBillingHistory').mockResolvedValue(hist);
      await billingStore.loadBillingHistory();
      let h: BillingHistoryType[] = [];
      const unsub = billingHistory.subscribe(s => { h = s; });
      expect(h).toEqual(hist);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(BillingService.prototype, 'getBillingHistory').mockResolvedValue([]);
      await billingStore.loadBillingHistory();
      expect(getCurrentState().history).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(BillingService.prototype, 'getBillingHistory').mockRejectedValue(new Error('History failed'));
      await billingStore.loadBillingHistory();
      const state = getCurrentState();
      expect(state.error).toBe('History failed');
      expect(state.loading).toBe(false);
      expect(state.history).toEqual([]);
    });
  });

  describe('createPayment', () => {
    it('creates payment and adds to payments', async () => {
      const pay = mockPayment({ id: 'p1' });
      vi.spyOn(BillingService.prototype, 'createPayment').mockResolvedValue(pay);
      const result = await billingStore.createPayment('inv-1', 'pm-1');
      expect(result).toEqual(pay);
      const state = getCurrentState();
      expect(state.payments).toContainEqual(pay);
    });

    it('propagates error', async () => {
      vi.spyOn(BillingService.prototype, 'createPayment').mockRejectedValue(new Error('Payment failed'));
      try {
        await billingStore.createPayment('inv-1', 'pm-1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Payment failed');
    });
  });

  describe('addPaymentMethod', () => {
    it('adds payment method to list', async () => {
      const method = mockPaymentMethod({ id: 'pm-new' });
      vi.spyOn(BillingService.prototype, 'addPaymentMethod').mockResolvedValue(method);
      const result = await billingStore.addPaymentMethod('card', 'token123', true);
      expect(result).toEqual(method);
      const state = getCurrentState();
      expect(state.paymentMethods).toContainEqual(method);
    });

    it('handles error', async () => {
      vi.spyOn(BillingService.prototype, 'addPaymentMethod').mockRejectedValue(new Error('Add failed'));
      try {
        await billingStore.addPaymentMethod('card', 'token');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Add failed');
    });
  });

  describe('deletePaymentMethod', () => {
    it('removes payment method from list', async () => {
      const method = mockPaymentMethod({ id: 'pm1' });
      // Prepopulate via loadPaymentMethods
      vi.spyOn(BillingService.prototype, 'getPaymentMethods').mockResolvedValue([method]);
      await billingStore.loadPaymentMethods();
      // Then delete
      vi.spyOn(BillingService.prototype, 'deletePaymentMethod').mockResolvedValue(undefined);
      await billingStore.deletePaymentMethod('pm1');
      const state = getCurrentState();
      expect(state.paymentMethods).toEqual([]);
    });

    it('handles error', async () => {
      const method = mockPaymentMethod({ id: 'pm1' });
      vi.spyOn(BillingService.prototype, 'getPaymentMethods').mockResolvedValue([method]);
      await billingStore.loadPaymentMethods();
      vi.spyOn(BillingService.prototype, 'deletePaymentMethod').mockRejectedValue(new Error('Delete failed'));
      try {
        await billingStore.deletePaymentMethod('pm1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Delete failed');
      // method should remain
      expect(state.paymentMethods).toContainEqual(method);
    });
  });

  describe('setDefaultPaymentMethod', () => {
    it('sets the chosen method as default and clears others', async () => {
      const pm1 = mockPaymentMethod({ id: 'pm1', isDefault: false });
      const pm2 = mockPaymentMethod({ id: 'pm2', isDefault: false });
      // Prepopulate via loadPaymentMethods
      vi.spyOn(BillingService.prototype, 'getPaymentMethods').mockResolvedValue([pm1, pm2]);
      await billingStore.loadPaymentMethods();
      const updated = mockPaymentMethod({ id: 'pm2', isDefault: true });
      vi.spyOn(BillingService.prototype, 'setDefaultPaymentMethod').mockResolvedValue(updated);
      const result = await billingStore.setDefaultPaymentMethod('pm2');
      expect(result).toEqual(updated);
      const state = getCurrentState();
      const pm2State = state.paymentMethods.find(p => p.id === 'pm2');
      const pm1State = state.paymentMethods.find(p => p.id === 'pm1');
      expect(pm2State?.isDefault).toBe(true);
      expect(pm1State?.isDefault).toBe(false);
    });

    it('handles error', async () => {
      const method = mockPaymentMethod({ id: 'pm1' });
      vi.spyOn(BillingService.prototype, 'getPaymentMethods').mockResolvedValue([method]);
      await billingStore.loadPaymentMethods();
      vi.spyOn(BillingService.prototype, 'setDefaultPaymentMethod').mockRejectedValue(new Error('Set default failed'));
      try {
        await billingStore.setDefaultPaymentMethod('pm1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Set default failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(BillingService.prototype, 'getInvoices').mockRejectedValue(new Error('err'));
      await billingStore.loadInvoices();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      billingStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(BillingService.prototype, 'getInvoices').mockResolvedValue([mockInvoice()]);
      await billingStore.loadInvoices();
      let state = getCurrentState();
      expect(state.invoices.length).toBeGreaterThan(0);
      billingStore.reset();
      state = getCurrentState();
      expect(state.invoices).toEqual([]);
      expect(state.paymentMethods).toEqual([]);
      expect(state.payments).toEqual([]);
      expect(state.history).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
