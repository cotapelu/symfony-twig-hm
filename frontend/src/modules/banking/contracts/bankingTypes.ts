export interface BankAccount {
  id: string;
  userId: string;
  accountNumber: string;
  bankName: string;
  bankCode: string;
  accountType: 'checking' | 'savings' | 'business';
  balance: number;
  currency: string;
  isDefault: boolean;
  status: 'active' | 'inactive' | 'blocked';
  createdAt: string;
}

export interface Transaction {
  id: string;
  accountId: string;
  type: 'credit' | 'debit' | 'transfer' | 'payment' | 'refund';
  amount: number;
  currency: string;
  balance: number;
  description: string;
  reference?: string;
  counterparty?: {
    name: string;
    accountNumber: string;
  };
  status: 'pending' | 'completed' | 'failed' | 'reversed';
  createdAt: string;
  completedAt?: string;
}

export interface Loan {
  id: string;
  userId: string;
  accountId: string;
  type: 'personal' | 'business' | 'home' | 'car' | 'education';
  principal: number;
  interestRate: number;
  termMonths: number;
  monthlyPayment: number;
  remainingBalance: number;
  status: 'pending' | 'approved' | 'active' | 'completed' | 'defaulted' | 'rejected';
  startDate: string;
  endDate?: string;
  nextPaymentDate: string;
  createdAt: string;
}

export interface CreditCard {
  id: string;
  userId: string;
  cardNumber: string;
  cardType: 'visa' | 'mastercard' | 'amex' | 'jcb';
  last4: string;
  expiryMonth: number;
  expiryYear: number;
  creditLimit: number;
  availableCredit: number;
  currentBalance: number;
  status: 'active' | 'blocked' | 'expired';
}

export interface BankingState {
  accounts: BankAccount[];
  transactions: Transaction[];
  loans: Loan[];
  creditCards: CreditCard[];
  loading: boolean;
  error: string | null;
}

export interface CreateTransferRequest {
  fromAccountId: string;
  toAccountNumber: string;
  toBankCode: string;
  amount: number;
  description?: string;
}

export interface CreateLoanRequest {
  accountId: string;
  type: Loan['type'];
  principal: number;
  termMonths: number;
}
