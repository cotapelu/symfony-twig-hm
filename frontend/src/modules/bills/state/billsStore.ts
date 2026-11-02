import { writable, derived } from 'svelte/store';
import type { Bill, Contract, BillPayment, BillsState } from '../contracts/billsTypes';
import { billsService } from '../services/billsService';

const initialState: BillsState = {
  bills: [],
  contracts: [],
  payments: [],
  selectedBill: null,
  loading: false,
  error: null
};

function createBillsStore() {
  const { subscribe, set, update } = writable<BillsState>(initialState);

  return {
    subscribe,

    async loadBills() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const bills = await billsService.getBills();
        update(s => ({ ...s, bills, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadContracts() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const contracts = await billsService.getContracts();
        update(s => ({ ...s, contracts, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadPaymentHistory() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const payments = await billsService.getPaymentHistory();
        update(s => ({ ...s, payments, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async payBill(billId: string, paymentMethod: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const payment = await billsService.payBill({ billId, paymentMethod });
        update(s => ({
          ...s,
          payments: [...s.payments, payment],
          bills: s.bills.map(b => b.id === billId ? { ...b, status: 'paid' as const, paidAt: payment.paidAt } : b),
          loading: false
        }));
        return payment;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    selectBill(bill: Bill | null) {
      update(s => ({ ...s, selectedBill: bill }));
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const billsStore = createBillsStore();

export const bills = derived(billsStore, $s => $s.bills);
export const contracts = derived(billsStore, $s => $s.contracts);
export const payments = derived(billsStore, $s => $s.payments);
export const billsLoading = derived(billsStore, $s => $s.loading);
export const billsError = derived(billsStore, $s => $s.error);
export const activeContracts = derived(billsStore, $s => $s.contracts.filter(c => c.isActive));
