import type { PaymentMethod, Transaction, PaymentRequest, PaymentResponse } from '../contracts/paymentsTypes';

export class PaymentsService {
  private baseUrl = '/api/payments';

  async getPaymentMethods(): Promise<PaymentMethod[]> {
    const response = await fetch(`${this.baseUrl}/methods`);
    if (!response.ok) throw new Error('Failed to fetch payment methods');
    return response.json();
  }

  async addPaymentMethod(method: Partial<PaymentMethod>): Promise<PaymentMethod> {
    const response = await fetch(`${this.baseUrl}/methods`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(method)
    });
    if (!response.ok) throw new Error('Failed to add payment method');
    return response.json();
  }

  async removePaymentMethod(id: string): Promise<void> {
    const response = await fetch(`${this.baseUrl}/methods/${id}`, { method: 'DELETE' });
    if (!response.ok) throw new Error('Failed to remove payment method');
  }

  async getTransactions(): Promise<Transaction[]> {
    const response = await fetch(`${this.baseUrl}/transactions`);
    if (!response.ok) throw new Error('Failed to fetch transactions');
    return response.json();
  }

  async processPayment(request: PaymentRequest): Promise<PaymentResponse> {
    const response = await fetch(`${this.baseUrl}/process`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Payment failed');
    }
    return response.json();
  }

  async refundPayment(transactionId: string, amount?: number): Promise<PaymentResponse> {
    const response = await fetch(`${this.baseUrl}/refund`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ transactionId, amount })
    });
    if (!response.ok) throw new Error('Refund failed');
    return response.json();
  }
}
