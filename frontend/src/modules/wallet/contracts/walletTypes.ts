export interface Wallet {
  id: string;
  userId: string;
  balance: number;
  currency: string;
  status: 'active' | 'frozen' | 'suspended';
  createdAt: string;
  updatedAt: string;
}

export interface Transaction {
  id: string;
  walletId: string;
  type: 'deposit' | 'withdraw' | 'transfer_in' | 'transfer_out' | 'payment' | 'refund' | 'fee';
  amount: number;
  currency: string;
  description?: string;
  recipientId?: string;
  senderId?: string;
  status: 'pending' | 'completed' | 'failed';
  date: string;
  metadata?: Record<string, any>;
}

export interface DepositRequest {
  amount: number;
  currency: string;
  paymentMethodId?: string;
  source?: string;
}

export interface WithdrawRequest {
  amount: number;
  currency: string;
  destination?: string;
}

export interface TransferRequest {
  amount: number;
  currency: string;
  recipientId: string;
  note?: string;
}

export interface WalletState {
  wallet: Wallet | null;
  transactions: Transaction[];
  loading: boolean;
  error: string | null;
}
