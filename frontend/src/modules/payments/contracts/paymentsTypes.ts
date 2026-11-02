export interface PaymentMethod {
  id: string;
  type: 'card' | 'bank' | 'wallet';
  cardType?: 'visa' | 'mastercard' | 'amex' | 'discover';
  last4?: string;
  expiry?: string;
  name?: string;
  isDefault: boolean;
  createdAt: string;
}

export interface Transaction {
  id: string;
  amount: number;
  currency: string;
  description: string;
  status: 'pending' | 'completed' | 'failed' | 'refunded';
  paymentMethodId: string;
  date: string;
  icon?: string;
  metadata?: Record<string, any>;
}

export interface PaymentRequest {
  amount: number;
  currency: string;
  paymentMethodId?: string;
  description?: string;
  metadata?: Record<string, any>;
}

export interface PaymentResponse {
  id: string;
  status: string;
  amount: number;
  currency: string;
  timestamp: string;
}

export interface PaymentsState {
  paymentMethods: PaymentMethod[];
  transactions: Transaction[];
  loading: boolean;
  error: string | null;
  currentPayment: PaymentResponse | null;
}
