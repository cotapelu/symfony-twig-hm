export interface Budget {
  id: string;
  userId: string;
  name: string;
  amount: number;
  currency: string;
  spent: number;
  period: 'weekly' | 'monthly' | 'yearly' | 'custom';
  startDate: string;
  endDate?: string;
  categories: BudgetCategory[];
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface BudgetCategory {
  id: string;
  budgetId: string;
  name: string;
  allocated: number;
  spent: number;
  color?: string;
  icon?: string;
}

export interface BudgetTransaction {
  id: string;
  budgetId: string;
  categoryId?: string;
  amount: number;
  description: string;
  date: string;
  type: 'income' | 'expense';
}

export interface BudgetState {
  budgets: Budget[];
  transactions: BudgetTransaction[];
  loading: boolean;
  error: string | null;
}

export interface CreateBudgetRequest {
  name: string;
  amount: number;
  currency?: string;
  period: Budget['period'];
  startDate: string;
  endDate?: string;
  categories?: Omit<BudgetCategory, 'id' | 'budgetId' | 'spent'>[];
  idempotencyKey?: string;
}

export interface UpdateBudgetRequest {
  name?: string;
  amount?: number;
  isActive?: boolean;
}

export interface AddTransactionRequest {
  budgetId: string;
  categoryId?: string;
  amount: number;
  description: string;
  date: string;
  type: 'income' | 'expense';
}
