import type { Bill, Contract, BillPayment, BillsState, CreateBillRequest, PayBillRequest } from '../contracts/billsTypes';

const API_BASE = '/api/bills';

export class BillsService {
  async getBills(): Promise<Bill[]> {
    const response = await fetch(`${API_BASE}/bills`);
    if (!response.ok) throw new Error('Failed to fetch bills');
    return response.json();
  }

  async getBill(id: string): Promise<Bill> {
    const response = await fetch(`${API_BASE}/bills/${id}`);
    if (!response.ok) throw new Error('Failed to fetch bill');
    return response.json();
  }

  async getContracts(): Promise<Contract[]> {
    const response = await fetch(`${API_BASE}/contracts`);
    if (!response.ok) throw new Error('Failed to fetch contracts');
    return response.json();
  }

  async getContract(id: string): Promise<Contract> {
    const response = await fetch(`${API_BASE}/contracts/${id}`);
    if (!response.ok) throw new Error('Failed to fetch contract');
    return response.json();
  }

  async payBill(request: PayBillRequest): Promise<BillPayment> {
    const response = await fetch(`${API_BASE}/payments`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(request)
    });
    if (!response.ok) throw new Error('Failed to pay bill');
    return response.json();
  }

  async getPaymentHistory(): Promise<BillPayment[]> {
    const response = await fetch(`${API_BASE}/payments`);
    if (!response.ok) throw new Error('Failed to fetch payment history');
    return response.json();
  }
}

export const billsService = new BillsService();
export default billsService;
