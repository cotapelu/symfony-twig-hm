export interface BudgetCreatedEvent {
  type: 'budgeting.budget.created';
  payload: {
    budgetId: string;
    userId: string;
    name: string;
    amount: number;
  };
}

export interface BudgetUpdatedEvent {
  type: 'budgeting.budget.updated';
  payload: {
    budgetId: string;
    userId: string;
    name: string;
  };
}

export interface BudgetDeletedEvent {
  type: 'budgeting.budget.deleted';
  payload: {
    budgetId: string;
    userId: string;
  };
}

export interface TransactionAddedEvent {
  type: 'budgeting.transaction.added';
  payload: {
    transactionId: string;
    budgetId: string;
    categoryId?: string;
    amount: number;
    type: 'income' | 'expense';
  };
}

export interface BudgetExceededEvent {
  type: 'budgeting.exceeded';
  payload: {
    budgetId: string;
    budgetName: string;
    spent: number;
    limit: number;
  };
}

export type BudgetingEvent = 
  | BudgetCreatedEvent 
  | BudgetUpdatedEvent 
  | BudgetDeletedEvent 
  | TransactionAddedEvent 
  | BudgetExceededEvent;

export const budgetingEvents = {
  budgetCreated: (budgetId: string, userId: string, name: string, amount: number): BudgetCreatedEvent => ({
    type: 'budgeting.budget.created',
    payload: { budgetId, userId, name, amount }
  }),

  budgetUpdated: (budgetId: string, userId: string, name: string): BudgetUpdatedEvent => ({
    type: 'budgeting.budget.updated',
    payload: { budgetId, userId, name }
  }),

  budgetDeleted: (budgetId: string, userId: string): BudgetDeletedEvent => ({
    type: 'budgeting.budget.deleted',
    payload: { budgetId, userId }
  }),

  transactionAdded: (transactionId: string, budgetId: string, categoryId: string | undefined, amount: number, type: 'income' | 'expense'): TransactionAddedEvent => ({
    type: 'budgeting.transaction.added',
    payload: { transactionId, budgetId, categoryId, amount, type }
  }),

  budgetExceeded: (budgetId: string, budgetName: string, spent: number, limit: number): BudgetExceededEvent => ({
    type: 'budgeting.exceeded',
    payload: { budgetId, budgetName, spent, limit }
  })
};
