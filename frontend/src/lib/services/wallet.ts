// Wallet Service - Connects to Go WalletService
import { getApiUrl } from '../config/api';

export interface Wallet {
  id: string;
  userId: string;
  balance: number;
  currency: string;
  status: string;
  isDefault: boolean;
}

export interface WalletTransaction {
  id: string;
  walletId: string;
  type: string;
  amount: number;
  balanceAfter: number;
  description: string;
  referenceId?: string;
  status: string;
  createdAt: string;
}

export interface TopUpRequest {
  userId: string;
  amount: number;
  method: string;
  bankCode?: string;
}

export interface TransferRequest {
  fromUserId: string;
  toUserId: string;
  amount: number;
  description?: string;
}

export interface WithdrawalRequest {
  userId: string;
  amount: number;
  bankCode: string;
  accountNo: string;
  holderName: string;
}

class WalletService {
  private getHeaders() {
    const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') : null;
    return {
      'Content-Type': 'application/json',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };
  }

  async getWallet(userId: string): Promise<Wallet> {
    const url = getApiUrl('wallet', `/api/wallet/${userId}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch wallet');
    return response.json();
  }

  async getBalance(userId: string): Promise<number> {
    const url = getApiUrl('wallet', `/api/wallet/${userId}/balance`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch balance');
    const data = await response.json();
    return data.balance;
  }

  async topUp(request: TopUpRequest): Promise<WalletTransaction> {
    const url = getApiUrl('wallet', '/api/wallet/topup');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(request),
    });
    if (!response.ok) throw new Error('Failed to top up');
    return response.json();
  }

  async transfer(request: TransferRequest): Promise<WalletTransaction> {
    const url = getApiUrl('wallet', '/api/wallet/transfer');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(request),
    });
    if (!response.ok) throw new Error('Failed to transfer');
    return response.json();
  }

  async withdraw(request: WithdrawalRequest): Promise<WalletTransaction> {
    const url = getApiUrl('wallet', '/api/wallet/withdraw');
    const response = await fetch(url, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(request),
    });
    if (!response.ok) throw new Error('Failed to withdraw');
    return response.json();
  }

  async getTransactions(userId: string, limit = 20, offset = 0): Promise<WalletTransaction[]> {
    const url = getApiUrl('wallet', `/api/wallet/${userId}/transactions?limit=${limit}&offset=${offset}`);
    const response = await fetch(url, { headers: this.getHeaders() });
    if (!response.ok) throw new Error('Failed to fetch transactions');
    const data = await response.json();
    return data.transactions || [];
  }
}

export const walletService = new WalletService();
