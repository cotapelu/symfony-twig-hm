import type { FinanceAccount, FinanceTransaction, FinanceState } from '../contracts/financeTypes';

const API_BASE = '/api/finance';

export class FinanceService {
  async getAccounts(): Promise<FinanceAccount[]> {
    const response = await fetch(`${API_BASE}/accounts`);
    if (!response.ok) throw new Error('Failed to fetch accounts');
    return response.json();
  }

  async getTransactions(accountId: string): Promise<FinanceTransaction[]> {
    const response = await fetch(`${API_BASE}/accounts/${accountId}/transactions`);
    if (!response.ok) throw new Error('Failed to fetch transactions');
    return response.json();
  }
}

export const financeService = new FinanceService();
export default financeService;
