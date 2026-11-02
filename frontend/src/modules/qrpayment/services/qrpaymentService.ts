import type { QRPayment } from '../contracts/qrpaymentTypes';

const API_BASE = '/api/qrpayment';

export class QRPaymentService {
  async getPayments(): Promise<QRPayment[]> {
    const response = await fetch(`${API_BASE}`);
    if (!response.ok) throw new Error('Failed to fetch payments');
    return response.json();
  }

  async getMyPayments(): Promise<QRPayment[]> {
    const response = await fetch(`${API_BASE}/my-payments`);
    if (!response.ok) throw new Error('Failed to fetch my payments');
    return response.json();
  }

  async getPayment(id: string): Promise<QRPayment> {
    const response = await fetch(`${API_BASE}/${id}`);
    if (!response.ok) throw new Error('Failed to fetch payment');
    return response.json();
  }

  async verifyOwnership(paymentId: string, userId: string): Promise<boolean> {
    const payment = await this.getPayment(paymentId);
    return payment.userId === userId;
  }

  async createPayment(merchantId: string, amount: number): Promise<QRPayment> {
    const response = await fetch(`${API_BASE}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ merchantId, amount })
    });
    if (!response.ok) throw new Error('Failed to create payment');
    return response.json();
  }
}

export const qrpaymentService = new QRPaymentService();
export default qrpaymentService;
