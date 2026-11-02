export interface Invoice {
  id: string;
  userId: string;
  amount: number;
  currency: string;
  status: 'pending' | 'paid' | 'failed' | 'cancelled';
  dueDate: string;
  paidAt?: string;
  items: InvoiceItem[];
  createdAt: string;
  updatedAt: string;
}

export interface InvoiceItem {
  description: string;
  quantity: number;
  unitPrice: number;
  total: number;
}

export interface PaymentMethod {
  id: string;
  userId: string;
  type: 'card' | 'bank' | 'wallet';
  last4?: string;
  brand?: string;
  bankName?: string;
  isDefault: boolean;
  expiryMonth?: number;
  expiryYear?: number;
}

export interface Payment {
  id: string;
  invoiceId: string;
  userId: string;
  amount: number;
  currency: string;
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'refunded';
  paymentMethodId: string;
  transactionId?: string;
  metadata?: Record<string, unknown>;
  createdAt: string;
  completedAt?: string;
}

export interface BillingHistory {
  id: string;
  userId: string;
  type: 'payment' | 'refund' | 'charge';
  amount: number;
  currency: string;
  status: 'success' | 'failed' | 'pending';
  description: string;
  createdAt: string;
}

export interface CreatePaymentRequest {
  invoiceId: string;
  paymentMethodId: string;
}

export interface CreatePaymentMethodRequest {
  type: 'card' | 'bank' | 'wallet';
  token: string;
  isDefault?: boolean;
}

export interface BillingState {
  invoices: Invoice[];
  paymentMethods: PaymentMethod[];
  payments: Payment[];
  history: BillingHistory[];
  loading: boolean;
  error: string | null;
}
