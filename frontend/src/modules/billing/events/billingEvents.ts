export interface InvoiceCreatedEvent {
  type: 'invoice.created';
  payload: {
    invoiceId: string;
    userId: string;
    amount: number;
  };
}

export interface InvoicePaidEvent {
  type: 'invoice.paid';
  payload: {
    invoiceId: string;
    paymentId: string;
    amount: number;
  };
}

export interface PaymentFailedEvent {
  type: 'payment.failed';
  payload: {
    paymentId: string;
    invoiceId: string;
    reason: string;
  };
}

export interface PaymentMethodAddedEvent {
  type: 'payment-method.added';
  payload: {
    paymentMethodId: string;
    userId: string;
    type: 'card' | 'bank' | 'wallet';
  };
}

export interface PaymentMethodRemovedEvent {
  type: 'payment-method.removed';
  payload: {
    paymentMethodId: string;
    userId: string;
  };
}

export type BillingEvent = 
  | InvoiceCreatedEvent 
  | InvoicePaidEvent 
  | PaymentFailedEvent 
  | PaymentMethodAddedEvent 
  | PaymentMethodRemovedEvent;

export const billingEvents = {
  invoiceCreated: (invoiceId: string, userId: string, amount: number): InvoiceCreatedEvent => ({
    type: 'invoice.created',
    payload: { invoiceId, userId, amount }
  }),

  invoicePaid: (invoiceId: string, paymentId: string, amount: number): InvoicePaidEvent => ({
    type: 'invoice.paid',
    payload: { invoiceId, paymentId, amount }
  }),

  paymentFailed: (paymentId: string, invoiceId: string, reason: string): PaymentFailedEvent => ({
    type: 'payment.failed',
    payload: { paymentId, invoiceId, reason }
  }),

  paymentMethodAdded: (paymentMethodId: string, userId: string, type: 'card' | 'bank' | 'wallet'): PaymentMethodAddedEvent => ({
    type: 'payment-method.added',
    payload: { paymentMethodId, userId, type }
  }),

  paymentMethodRemoved: (paymentMethodId: string, userId: string): PaymentMethodRemovedEvent => ({
    type: 'payment-method.removed',
    payload: { paymentMethodId, userId }
  })
};
