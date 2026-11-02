import type {
  Invoice,
  PaymentMethod,
  Payment,
  BillingHistory,
  CreatePaymentRequest,
  CreatePaymentMethodRequest,
  BillingState
} from '../contracts/billingTypes';

const API_BASE = '/api/billing';

export class BillingService {
  async getInvoices(): Promise<Invoice[]> {
    const response = await fetch(`${API_BASE}/invoices`);
    if (!response.ok) throw new Error('Failed to fetch invoices');
    return response.json();
  }

  async getInvoice(id: string): Promise<Invoice> {
    const response = await fetch(`${API_BASE}/invoices/${id}`);
    if (!response.ok) throw new Error('Failed to fetch invoice');
    return response.json();
  }

  async createPayment(request: CreatePaymentRequest): Promise<Payment> {
    const response = await fetch(`${API_BASE}/payments`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to create payment');
    return response.json();
  }

  async getPaymentMethods(): Promise<PaymentMethod[]> {
    const response = await fetch(`${API_BASE}/payment-methods`);
    if (!response.ok) throw new Error('Failed to fetch payment methods');
    return response.json();
  }

  async addPaymentMethod(request: CreatePaymentMethodRequest): Promise<PaymentMethod> {
    const response = await fetch(`${API_BASE}/payment-methods`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to add payment method');
    return response.json();
  }

  async deletePaymentMethod(id: string): Promise<void> {
    const response = await fetch(`${API_BASE}/payment-methods/${id}`, {
      method: 'DELETE'
    });
    if (!response.ok) throw new Error('Failed to delete payment method');
  }

  async setDefaultPaymentMethod(id: string): Promise<PaymentMethod> {
    const response = await fetch(`${API_BASE}/payment-methods/${id}/default`, {
      method: 'PUT'
    });
    if (!response.ok) throw new Error('Failed to set default payment method');
    return response.json();
  }

  async getBillingHistory(): Promise<BillingHistory[]> {
    const response = await fetch(`${API_BASE}/history`);
    if (!response.ok) throw new Error('Failed to fetch billing history');
    return response.json();
  }

  async getPayment(id: string): Promise<Payment> {
    const response = await fetch(`${API_BASE}/payments/${id}`);
    if (!response.ok) throw new Error('Failed to fetch payment');
    return response.json();
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};

export const billingService = new BillingService();
export default billingService;
