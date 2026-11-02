import { writable } from 'svelte/store';
import type { PaymentMethod, Transaction, PaymentRequest, PaymentResponse, PaymentsState } from '../contracts/paymentsTypes';
import { PaymentsService } from '../services/paymentsService';

const initialState: PaymentsState = {
  paymentMethods: [],
  transactions: [],
  loading: false,
  error: null,
  currentPayment: null
};

function createPaymentsStore() {
  const { subscribe, set, update } = writable<PaymentsState>(initialState);
  const service = new PaymentsService();

  return {
    subscribe,

    async initialize() {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const [methods, transactions] = await Promise.all([
          service.getPaymentMethods(),
          service.getTransactions()
        ]);
        update(state => ({ 
          ...state, 
          paymentMethods: methods,
          transactions: transactions,
          loading: false 
        }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to initialize',
          loading: false
        }));
      }
    },

    async loadPaymentMethods() {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const methods = await service.getPaymentMethods();
        update(state => ({ ...state, paymentMethods: methods, loading: false }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load payment methods',
          loading: false
        }));
      }
    },

    async addPaymentMethod(method: Partial<PaymentMethod>) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const newMethod = await service.addPaymentMethod(method);
        update(state => ({
          ...state,
          paymentMethods: [...state.paymentMethods, newMethod],
          loading: false
        }));
        return newMethod;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to add payment method',
          loading: false
        }));
        throw error;
      }
    },

    async removePaymentMethod(id: string) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        await service.removePaymentMethod(id);
        update(state => ({
          ...state,
          paymentMethods: state.paymentMethods.filter(m => m.id !== id),
          loading: false
        }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to remove payment method',
          loading: false
        }));
        throw error;
      }
    },

    async loadTransactions() {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const transactions = await service.getTransactions();
        update(state => ({ ...state, transactions, loading: false }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load transactions',
          loading: false
        }));
      }
    },

    async processPayment(request: PaymentRequest): Promise<PaymentResponse> {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const response = await service.processPayment(request);
        update(state => ({
          ...state,
          currentPayment: response,
          transactions: [{
            id: response.id,
            amount: response.amount,
            currency: response.currency,
            description: request.description || 'Payment',
            status: response.status === 'success' ? 'completed' : 'failed',
            paymentMethodId: request.paymentMethodId || '',
            date: response.timestamp
          }, ...state.transactions],
          loading: false
        }));
        return response;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Payment failed',
          loading: false
        }));
        throw error;
      }
    },

    clearError() {
      update(state => ({ ...state, error: null }));
    },
    reset() {
      set(initialState);
    }
  };
}

export const paymentsStore = createPaymentsStore();
export const { initialize, loadPaymentMethods, addPaymentMethod, removePaymentMethod, loadTransactions, processPayment, clearError, reset } = paymentsStore;
