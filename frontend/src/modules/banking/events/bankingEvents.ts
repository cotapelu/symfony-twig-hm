export interface TransferCompletedEvent {
  type: 'banking.transfer.completed';
  payload: {
    transactionId: string;
    fromAccountId: string;
    toAccountNumber: string;
    amount: number;
  };
}

export interface TransferFailedEvent {
  type: 'banking.transfer.failed';
  payload: {
    transactionId: string;
    fromAccountId: string;
    reason: string;
  };
}

export interface LoanApprovedEvent {
  type: 'banking.loan.approved';
  payload: {
    loanId: string;
    userId: string;
    principal: number;
    monthlyPayment: number;
  };
}

export interface LoanDefaultedEvent {
  type: 'banking.loan.defaulted';
  payload: {
    loanId: string;
    userId: string;
    remainingBalance: number;
  };
}

export interface LowBalanceEvent {
  type: 'banking.account.low_balance';
  payload: {
    accountId: string;
    balance: number;
    threshold: number;
  };
}

export type BankingEvent = 
  | TransferCompletedEvent 
  | TransferFailedEvent 
  | LoanApprovedEvent 
  | LoanDefaultedEvent 
  | LowBalanceEvent;

export const bankingEvents = {
  transferCompleted: (transactionId: string, fromAccountId: string, toAccountNumber: string, amount: number): TransferCompletedEvent => ({
    type: 'banking.transfer.completed',
    payload: { transactionId, fromAccountId, toAccountNumber, amount }
  }),

  transferFailed: (transactionId: string, fromAccountId: string, reason: string): TransferFailedEvent => ({
    type: 'banking.transfer.failed',
    payload: { transactionId, fromAccountId, reason }
  }),

  loanApproved: (loanId: string, userId: string, principal: number, monthlyPayment: number): LoanApprovedEvent => ({
    type: 'banking.loan.approved',
    payload: { loanId, userId, principal, monthlyPayment }
  }),

  loanDefaulted: (loanId: string, userId: string, remainingBalance: number): LoanDefaultedEvent => ({
    type: 'banking.loan.defaulted',
    payload: { loanId, userId, remainingBalance }
  }),

  lowBalance: (accountId: string, balance: number, threshold: number): LowBalanceEvent => ({
    type: 'banking.account.low_balance',
    payload: { accountId, balance, threshold }
  })
};
