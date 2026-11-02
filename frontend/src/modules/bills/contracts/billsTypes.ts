export interface Bill {
  id: string;
  userId: string;
  contractId: string;
  billingMonth: string;
  amount: number;
  previousReading: number;
  currentReading: number;
  usage: number;
  status: 'pending' | 'paid' | 'overdue';
  dueDate: string;
  paidAt?: string;
  createdAt: string;
}

export interface Contract {
  id: string;
  userId: string;
  serviceType: 'electricity' | 'water' | 'internet' | 'phone' | 'gas';
  contractNumber: string;
  address: string;
  isActive: boolean;
  startDate: string;
  endDate?: string;
}

export interface BillPayment {
  id: string;
  billId: string;
  userId: string;
  amount: number;
  paymentMethod: string;
  transactionId?: string;
  status: 'pending' | 'completed' | 'failed';
  paidAt?: string;
  createdAt: string;
}

export interface BillsState {
  bills: Bill[];
  contracts: Contract[];
  payments: BillPayment[];
  selectedBill: Bill | null;
  loading: boolean;
  error: string | null;
}

export interface CreateBillRequest {
  contractId: string;
  billingMonth: string;
  previousReading: number;
  currentReading: number;
}

export interface PayBillRequest {
  billId: string;
  paymentMethod: string;
}
