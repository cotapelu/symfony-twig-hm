import { createEventDispatcher } from 'svelte';

export interface PaymentEvent {
  type: 'payment_initiated' | 'payment_success' | 'payment_failed' | 'payment_refunded' | 'method_added' | 'method_removed';
  data?: any;
  timestamp: number;
}

export function createPaymentEvents() {
  const dispatch = createEventDispatcher();

  return {
    dispatchPaymentInitiated(payment: any) {
      dispatch('payment_initiated', {
        type: 'payment_initiated',
        data: payment,
        timestamp: Date.now()
      });
    },

    dispatchPaymentSuccess(result: any) {
      dispatch('payment_success', {
        type: 'payment_success',
        data: result,
        timestamp: Date.now()
      });
    },

    dispatchPaymentFailed(error: any) {
      dispatch('payment_failed', {
        type: 'payment_failed',
        data: error,
        timestamp: Date.now()
      });
    },

    dispatchPaymentRefunded(refund: any) {
      dispatch('payment_refunded', {
        type: 'payment_refunded',
        data: refund,
        timestamp: Date.now()
      });
    },

    dispatchMethodAdded(method: any) {
      dispatch('method_added', {
        type: 'method_added',
        data: method,
        timestamp: Date.now()
      });
    },

    dispatchMethodRemoved(methodId: string) {
      dispatch('method_removed', {
        type: 'method_removed',
        data: { methodId },
        timestamp: Date.now()
      });
    }
  };
}

export const paymentEventTypes = {
  PAYMENT_INITIATED: 'payment_initiated',
  PAYMENT_SUCCESS: 'payment_success',
  PAYMENT_FAILED: 'payment_failed',
  PAYMENT_REFUNDED: 'payment_refunded',
  METHOD_ADDED: 'method_added',
  METHOD_REMOVED: 'method_removed'
} as const;
