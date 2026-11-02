import type {
  BankAccount,
  Transaction,
  Loan,
  CreditCard,
  BankingState,
  CreateTransferRequest,
  CreateLoanRequest
} from '../contracts/bankingTypes';

const API_BASE = '/api/banking';

export class BankingService {
  async getAccounts(): Promise<BankAccount[]> {
    const response = await fetch(`${API_BASE}/accounts`);
    if (!response.ok) throw new Error('Failed to fetch accounts');
    return response.json();
  }

  async getAccount(id: string): Promise<BankAccount> {
    const response = await fetch(`${API_BASE}/accounts/${id}`);
    if (!response.ok) throw new Error('Failed to fetch account');
    return response.json();
  }

  async getTransactions(accountId: string): Promise<Transaction[]> {
    const response = await fetch(`${API_BASE}/accounts/${accountId}/transactions`);
    if (!response.ok) throw new Error('Failed to fetch transactions');
    return response.json();
  }

  async createTransfer(request: CreateTransferRequest): Promise<Transaction> {
    const response = await fetch(`${API_BASE}/transfers`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create transfer');
    return response.json();
  }

  async getLoans(): Promise<Loan[]> {
    const response = await fetch(`${API_BASE}/loans`);
    if (!response.ok) throw new Error('Failed to fetch loans');
    return response.json();
  }

  async applyForLoan(request: CreateLoanRequest): Promise<Loan> {
    const response = await fetch(`${API_BASE}/loans`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to apply for loan');
    return response.json();
  }

  async getCreditCards(): Promise<CreditCard[]> {
    const response = await fetch(`${API_BASE}/credit-cards`);
    if (!response.ok) throw new Error('Failed to fetch credit cards');
    return response.json();
  }

  async blockCreditCard(id: string): Promise<CreditCard> {
    const response = await fetch(`${API_BASE}/credit-cards/${id}/block`, {
      method: 'POST'
    });
    if (!response.ok) throw new Error('Failed to block credit card');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const bankingService = new BankingService();
export default bankingService;
