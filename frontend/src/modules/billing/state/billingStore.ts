import { writable, derived, get } from 'svelte/store';
import type {
  Invoice,
  PaymentMethod,
  Payment,
  BillingHistory,
  BillingState
} from '../contracts/billingTypes';
import { billingService } from '../services/billingService';

const initialState: BillingState = {
  invoices: [],
  paymentMethods: [],
  payments: [],
  history: [],
  loading: false,
  error: null
};

function createBillingStore() {
  const { subscribe, set, update } = writable<BillingState>(initialState);

  return {
    subscribe,

    async loadInvoices() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const invoices = await billingService.getInvoices();
        update(s => ({ ...s, invoices, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadPaymentMethods() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const paymentMethods = await billingService.getPaymentMethods();
        update(s => ({ ...s, paymentMethods, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadBillingHistory() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const history = await billingService.getBillingHistory();
        update(s => ({ ...s, history, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async createPayment(invoiceId: string, paymentMethodId: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const payment = await billingService.createPayment({ invoiceId, paymentMethodId });
        update(s => ({ ...s, payments: [...s.payments, payment], loading: false }));
        return payment;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async addPaymentMethod(type: 'card' | 'bank' | 'wallet', token: string, isDefault?: boolean) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const method = await billingService.addPaymentMethod({ type, token, isDefault });
        update(s => ({ ...s, paymentMethods: [...s.paymentMethods, method], loading: false }));
        return method;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async deletePaymentMethod(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        await billingService.deletePaymentMethod(id);
        update(s => ({
          ...s,
          paymentMethods: s.paymentMethods.filter(m => m.id !== id),
          loading: false
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async setDefaultPaymentMethod(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const method = await billingService.setDefaultPaymentMethod(id);
        update(s => ({
          ...s,
          paymentMethods: s.paymentMethods.map(m => ({ ...m, isDefault: m.id === id })),
          loading: false
        }));
        return method;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const billingStore = createBillingStore();

export const invoices = derived(billingStore, $s => $s.invoices);
export const paymentMethods = derived(billingStore, $s => $s.paymentMethods);
export const defaultPaymentMethod = derived(billingStore, $s =>
  $s.paymentMethods.find(m => m.isDefault)
);
export const billingHistory = derived(billingStore, $s => $s.history);
export const billingLoading = derived(billingStore, $s => $s.loading);
export const billingError = derived(billingStore, $s => $s.error);
