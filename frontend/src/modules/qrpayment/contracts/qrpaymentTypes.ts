export interface QRPayment {
  id: string;
  userId: string;
  amount: number;
  merchantId: string;
  status: 'pending' | 'completed' | 'failed';
  createdAt: string;
}

export interface QRPaymentState {
  payments: QRPayment[];
  loading: boolean;
  error: string | null;
  currentUserId: string | null;
}
