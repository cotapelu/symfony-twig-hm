import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  billsStore,
  bills,
  contracts,
  payments,
  billsLoading,
  billsError,
  activeContracts
} from './billsStore';
import { BillsService } from '../services/billsService';
import type { Bill, Contract, BillPayment } from '../contracts/billsTypes';

function getCurrentState() {
  let current: any;
  const unsub = billsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockBill = (overrides: Partial<Bill> = {}): Bill => ({
  id: `bill-${Math.random().toString(36).substr(2, 5)}`,
  number: 'BILL001',
  amount: 100,
  currency: 'USD',
  status: 'pending',
  dueDate: new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000),
  createdAt: now,
  ...overrides
});

const mockContract = (overrides: Partial<Contract> = {}): Contract => ({
  id: `contract-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Contract Name',
  startDate: now,
  endDate: new Date(now.getTime() + 365 * 24 * 60 * 60 * 1000),
  isActive: true,
  ...overrides
});

const mockPayment = (overrides: Partial<BillPayment> = {}): BillPayment => ({
  id: `pay-${Math.random().toString(36).substr(2, 5)}`,
  billId: 'bill-1',
  amount: 100,
  currency: 'USD',
  paidAt: now,
  method: 'card',
  ...overrides
});

describe('BillsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    billsStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.bills).toEqual([]);
      expect(state.contracts).toEqual([]);
      expect(state.payments).toEqual([]);
      expect(state.selectedBill).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let b: Bill[] = [], c: Contract[] = [], p: BillPayment[] = [], l = false, e: string | null = null;
      const unsubB = bills.subscribe(s => { b = s; });
      const unsubC = contracts.subscribe(s => { c = s; });
      const unsubP = payments.subscribe(s => { p = s; });
      const unsubL = billsLoading.subscribe(s => { l = s; });
      const unsubE = billsError.subscribe(s => { e = s; });
      expect(b).toEqual([]);
      expect(c).toEqual([]);
      expect(p).toEqual([]);
      expect(l).toBe(false);
      expect(e).toBeNull();
      unsubB(); unsubC(); unsubP(); unsubL(); unsubE();
    });
  });

  describe('loadBills', () => {
    it('loads bills', async () => {
      const bs = [mockBill(), mockBill()];
      vi.spyOn(BillsService.prototype, 'getBills').mockResolvedValue(bs);
      await billsStore.loadBills();
      const state = getCurrentState();
      expect(state.bills).toEqual(bs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(BillsService.prototype, 'getBills').mockReturnValue(p as any);
      const loadPromise = billsStore.loadBills();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockBill()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates bills derived on success', async () => {
      const bs = [mockBill()];
      vi.spyOn(BillsService.prototype, 'getBills').mockResolvedValue(bs);
      await billsStore.loadBills();
      let b: Bill[] = [];
      const unsub = bills.subscribe(s => { b = s; });
      expect(b).toEqual(bs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(BillsService.prototype, 'getBills').mockResolvedValue([]);
      await billsStore.loadBills();
      expect(getCurrentState().bills).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(BillsService.prototype, 'getBills').mockRejectedValue(new Error('Bills failed'));
      await billsStore.loadBills();
      const state = getCurrentState();
      expect(state.error).toBe('Bills failed');
      expect(state.loading).toBe(false);
      expect(state.bills).toEqual([]);
    });
  });

  describe('loadContracts', () => {
    it('loads contracts', async () => {
      const cs = [mockContract(), mockContract()];
      vi.spyOn(BillsService.prototype, 'getContracts').mockResolvedValue(cs);
      await billsStore.loadContracts();
      const state = getCurrentState();
      expect(state.contracts).toEqual(cs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(BillsService.prototype, 'getContracts').mockReturnValue(p as any);
      const loadPromise = billsStore.loadContracts();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockContract()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates contracts derived on success', async () => {
      const cs = [mockContract()];
      vi.spyOn(BillsService.prototype, 'getContracts').mockResolvedValue(cs);
      await billsStore.loadContracts();
      let c: Contract[] = [];
      const unsub = contracts.subscribe(s => { c = s; });
      expect(c).toEqual(cs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(BillsService.prototype, 'getContracts').mockResolvedValue([]);
      await billsStore.loadContracts();
      expect(getCurrentState().contracts).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(BillsService.prototype, 'getContracts').mockRejectedValue(new Error('Contracts failed'));
      await billsStore.loadContracts();
      const state = getCurrentState();
      expect(state.error).toBe('Contracts failed');
      expect(state.loading).toBe(false);
      expect(state.contracts).toEqual([]);
    });
  });

  describe('loadPaymentHistory', () => {
    it('loads payments', async () => {
      const ps = [mockPayment(), mockPayment()];
      vi.spyOn(BillsService.prototype, 'getPaymentHistory').mockResolvedValue(ps);
      await billsStore.loadPaymentHistory();
      const state = getCurrentState();
      expect(state.payments).toEqual(ps);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(BillsService.prototype, 'getPaymentHistory').mockReturnValue(p as any);
      const loadPromise = billsStore.loadPaymentHistory();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockPayment()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates payments derived on success', async () => {
      const ps = [mockPayment()];
      vi.spyOn(BillsService.prototype, 'getPaymentHistory').mockResolvedValue(ps);
      await billsStore.loadPaymentHistory();
      let p: BillPayment[] = [];
      const unsub = payments.subscribe(s => { p = s; });
      expect(p).toEqual(ps);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(BillsService.prototype, 'getPaymentHistory').mockResolvedValue([]);
      await billsStore.loadPaymentHistory();
      expect(getCurrentState().payments).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(BillsService.prototype, 'getPaymentHistory').mockRejectedValue(new Error('Payments failed'));
      await billsStore.loadPaymentHistory();
      const state = getCurrentState();
      expect(state.error).toBe('Payments failed');
      expect(state.loading).toBe(false);
      expect(state.payments).toEqual([]);
    });
  });

  describe('payBill', () => {
    it('adds payment and updates bill status', async () => {
      const bill = mockBill({ id: 'b1', status: 'pending' });
      const payment = mockPayment({ id: 'p1', billId: 'b1' });
      // Prepopulate bills via loadBills
      vi.spyOn(BillsService.prototype, 'getBills').mockResolvedValue([bill]);
      await billsStore.loadBills();
      // Pay
      vi.spyOn(BillsService.prototype, 'payBill').mockResolvedValue(payment);
      const result = await billsStore.payBill('b1', 'card');
      expect(result).toEqual(payment);
      const state = getCurrentState();
      expect(state.payments).toContainEqual(payment);
      const updatedBill = state.bills.find(b => b.id === 'b1');
      expect(updatedBill?.status).toBe('paid');
      expect(updatedBill?.paidAt).toBe(payment.paidAt);
    });

    it('propagates error', async () => {
      vi.spyOn(BillsService.prototype, 'payBill').mockRejectedValue(new Error('Payment failed'));
      try {
        await billsStore.payBill('b1', 'card');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Payment failed');
    });
  });

  describe('selectBill', () => {
    it('selects and clears selection', () => {
      const bill = mockBill();
      billsStore.selectBill(bill);
      let state = getCurrentState();
      expect(state.selectedBill).toEqual(bill);
      billsStore.selectBill(null);
      state = getCurrentState();
      expect(state.selectedBill).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(BillsService.prototype, 'getBills').mockRejectedValue(new Error('err'));
      await billsStore.loadBills();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      billsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(BillsService.prototype, 'getBills').mockResolvedValue([mockBill()]);
      await billsStore.loadBills();
      let state = getCurrentState();
      expect(state.bills.length).toBeGreaterThan(0);
      billsStore.reset();
      state = getCurrentState();
      expect(state.bills).toEqual([]);
      expect(state.contracts).toEqual([]);
      expect(state.payments).toEqual([]);
      expect(state.selectedBill).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('activeContracts derived', () => {
    it('filters active contracts', async () => {
      const active = mockContract({ id: 'c1', isActive: true });
      const inactive = mockContract({ id: 'c2', isActive: false });
      vi.spyOn(BillsService.prototype, 'getContracts').mockResolvedValue([active, inactive]);
      await billsStore.loadContracts();
      let act: Contract[] = [];
      const unsub = activeContracts.subscribe(s => { act = s; });
      expect(act).toEqual([active]);
      unsub();
    });
  });
});
