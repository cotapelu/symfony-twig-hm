import { writable, derived, get } from 'svelte/store';
import type { QRPayment, QRPaymentState } from '../contracts/qrpaymentTypes';
import { qrpaymentService } from '../services/qrpaymentService';

const initialState: QRPaymentState = { 
  payments: [], 
  loading: false, 
  error: null,
  currentUserId: null 
};

function createQRPaymentStore() {
  const { subscribe, set, update } = writable<QRPaymentState>(initialState);
  return {
    subscribe,
    
    setCurrentUser(userId: string) {
      update(s => ({ ...s, currentUserId: userId }));
    },
    
    async loadPayments() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const state = get({ subscribe });
        const payments = state.currentUserId 
          ? await qrpaymentService.getMyPayments()
          : await qrpaymentService.getPayments();
        update(s => ({ ...s, payments, loading: false }));
      } catch (error) { 
        update(s => ({ ...s, error: (error as Error).message, loading: false })); 
      }
    },
    
    async loadPayment(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const state = get({ subscribe });
        const payment = await qrpaymentService.getPayment(id);
        
        if (state.currentUserId && payment.userId !== state.currentUserId) {
          throw new Error('Unauthorized: You can only access your own payments');
        }
        
        update(s => ({ ...s, payments: [payment], loading: false }));
      } catch (error) { 
        update(s => ({ ...s, error: (error as Error).message, loading: false })); 
      }
    },
    
    async createPayment(merchantId: string, amount: number) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const payment = await qrpaymentService.createPayment(merchantId, amount);
        update(s => ({ ...s, payments: [...s.payments, payment], loading: false }));
        return payment;
      } catch (error) { 
        update(s => ({ ...s, error: (error as Error).message, loading: false })); 
        throw error;
      }
    },
    
    canAccessPayment(payment: QRPayment): boolean {
      const state = get({ subscribe });
      return !state.currentUserId || payment.userId === state.currentUserId;
    },
    
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const qrpaymentStore = createQRPaymentStore();
export const qrpayments = derived(qrpaymentStore, $s => $s.payments);
export const qrpaymentLoading = derived(qrpaymentStore, $s => $s.loading);
