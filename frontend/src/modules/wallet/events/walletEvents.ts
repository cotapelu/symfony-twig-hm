import { createEventDispatcher } from 'svelte';

export interface WalletEvent {
  type: 'balance_updated' | 'transaction_created' | 'deposit_completed' | 'withdraw_completed' | 'transfer_completed' | 'transfer_received';
  data?: any;
  timestamp: number;
}

export function createWalletEvents() {
  const dispatch = createEventDispatcher();

  return {
    dispatchBalanceUpdated(balance: number) {
      dispatch('balance_updated', {
        type: 'balance_updated',
        data: { balance },
        timestamp: Date.now()
      });
    },

    dispatchTransactionCreated(transaction: any) {
      dispatch('transaction_created', {
        type: 'transaction_created',
        data: transaction,
        timestamp: Date.now()
      });
    },

    dispatchDepositCompleted(deposit: any) {
      dispatch('deposit_completed', {
        type: 'deposit_completed',
        data: deposit,
        timestamp: Date.now()
      });
    },

    dispatchWithdrawCompleted(withdrawal: any) {
      dispatch('withdraw_completed', {
        type: 'withdraw_completed',
        data: withdrawal,
        timestamp: Date.now()
      });
    },

    dispatchTransferCompleted(transfer: any) {
      dispatch('transfer_completed', {
        type: 'transfer_completed',
        data: transfer,
        timestamp: Date.now()
      });
    },

    dispatchTransferReceived(transfer: any) {
      dispatch('transfer_received', {
        type: 'transfer_received',
        data: transfer,
        timestamp: Date.now()
      });
    }
  };
}

export const walletEventTypes = {
  BALANCE_UPDATED: 'balance_updated',
  TRANSACTION_CREATED: 'transaction_created',
  DEPOSIT_COMPLETED: 'deposit_completed',
  WITHDRAW_COMPLETED: 'withdraw_completed',
  TRANSFER_COMPLETED: 'transfer_completed',
  TRANSFER_RECEIVED: 'transfer_received'
} as const;
