export interface FinanceAccount {
  id: string;
  userId: string;
  accountNumber: string;
  accountType: string;
  balance: number;
  currency: string;
}

export interface FinanceTransaction {
  id: string;
  accountId: string;
  amount: number;
  type: 'credit' | 'debit';
  description: string;
  createdAt: string;
}

export interface FinanceState {
  accounts: FinanceAccount[];
  transactions: FinanceTransaction[];
  loading: boolean;
  error: string | null;
}
