export interface TtthAccount {
  id: string;
  userId: string;
  accountNumber: string;
  balance: number;
  currency: string;
}

export interface TtthTransaction {
  id: string;
  accountId: string;
  amount: number;
  type: 'credit' | 'debit';
  description: string;
  createdAt: string;
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface TtthState {
  accounts: TtthAccount[];
  transactions: TtthTransaction[];
  loading: boolean;
  error: string | null;
  pagination: {
    page: number;
    limit: number;
    total: number;
    totalPages: number;
  };
}
