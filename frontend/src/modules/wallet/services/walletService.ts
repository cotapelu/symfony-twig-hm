import type { Wallet, Transaction, DepositRequest, WithdrawRequest, TransferRequest } from '../contracts/walletTypes';
import { apiClient } from '../../../appcore/api-client';

export class WalletService {
  private baseUrl = '/api/wallet';

  async getWallet(): Promise<Wallet> {
    return apiClient.get<Wallet>(this.baseUrl);
  }

  async checkBalance(): Promise<number> {
    const data = await apiClient.get<{ balance: number }>(`${this.baseUrl}/balance`);
    return data.balance;
  }

  async getTransactions(): Promise<Transaction[]> {
    return apiClient.get<Transaction[]>(`${this.baseUrl}/transactions`);
  }

  async deposit(request: DepositRequest): Promise<{ id: string; status: string }> {
    return apiClient.post<{ id: string; status: string }>(`${this.baseUrl}/deposit`, request);
  }

  async withdraw(request: WithdrawRequest): Promise<{ id: string; status: string }> {
    return apiClient.post<{ id: string; status: string }>(`${this.baseUrl}/withdraw`, request);
  }

  async transfer(request: TransferRequest): Promise<{ id: string; status: string }> {
    return apiClient.post<{ id: string; status: string }>(`${this.baseUrl}/transfer`, request);
  }

  async getTransactionById(id: string): Promise<Transaction> {
    return apiClient.get<Transaction>(`${this.baseUrl}/transactions/${id}`);
  }
}
