export interface BillCreatedEvent {
  type: 'bill.created';
  payload: {
    billId: string;
    contractId: string;
    amount: number;
    billingMonth: string;
  };
}

export interface BillPaidEvent {
  type: 'bill.paid';
  payload: {
    billId: string;
    paymentId: string;
    amount: number;
  };
}

export interface BillOverdueEvent {
  type: 'bill.overdue';
  payload: {
    billId: string;
    dueDate: string;
  };
}

export interface ContractCreatedEvent {
  type: 'contract.created';
  payload: {
    contractId: string;
    userId: string;
    serviceType: string;
  };
}

export interface ContractTerminatedEvent {
  type: 'contract.terminated';
  payload: {
    contractId: string;
    userId: string;
  };
}

export type BillsEvent =
  | BillCreatedEvent
  | BillPaidEvent
  | BillOverdueEvent
  | ContractCreatedEvent
  | ContractTerminatedEvent;

export const billsEvents = {
  billCreated: (billId: string, contractId: string, amount: number, billingMonth: string): BillCreatedEvent => ({
    type: 'bill.created',
    payload: { billId, contractId, amount, billingMonth }
  }),

  billPaid: (billId: string, paymentId: string, amount: number): BillPaidEvent => ({
    type: 'bill.paid',
    payload: { billId, paymentId, amount }
  }),

  billOverdue: (billId: string, dueDate: string): BillOverdueEvent => ({
    type: 'bill.overdue',
    payload: { billId, dueDate }
  }),

  contractCreated: (contractId: string, userId: string, serviceType: string): ContractCreatedEvent => ({
    type: 'contract.created',
    payload: { contractId, userId, serviceType }
  }),

  contractTerminated: (contractId: string, userId: string): ContractTerminatedEvent => ({
    type: 'contract.terminated',
    payload: { contractId, userId }
  })
};
