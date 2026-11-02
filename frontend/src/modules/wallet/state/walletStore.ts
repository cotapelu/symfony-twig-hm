import { writable } from 'svelte/store';
import type { Wallet, Transaction, DepositRequest, WithdrawRequest, TransferRequest, WalletState } from '../contracts/walletTypes';
import { WalletService } from '../services/walletService';

const initialState: WalletState = {
  wallet: null,
  transactions: [],
  loading: false,
  error: null
};

function createWalletStore() {
  const { subscribe, set, update } = writable<WalletState>(initialState);
  const service = new WalletService();

  return {
    subscribe,

    async loadWallet() {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const [wallet, transactions] = await Promise.all([
          service.getWallet(),
          service.getTransactions()
        ]);
        update(state => ({
          ...state,
          wallet,
          transactions,
          loading: false
        }));
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to load wallet',
          loading: false
        }));
      }
    },

    async checkBalance() {
      try {
        const balance = await service.checkBalance();
        update(state => ({
          ...state,
          wallet: state.wallet ? { ...state.wallet, balance } : null
        }));
        return balance;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Failed to check balance'
        }));
        throw error;
      }
    },

    async deposit(request: DepositRequest) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const result = await service.deposit(request);
        update(state => ({
          ...state,
          wallet: state.wallet ? { ...state.wallet, balance: state.wallet.balance + request.amount } : null,
          transactions: [{
            id: result.id,
            walletId: state.wallet?.id || '',
            type: 'deposit',
            amount: request.amount,
            currency: request.currency,
            status: 'completed',
            date: new Date().toISOString(),
            description: `Deposit from ${request.source || 'external'}`
          }, ...state.transactions],
          loading: false
        }));
        return result;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Deposit failed',
          loading: false
        }));
        throw error;
      }
    },

    async withdraw(request: WithdrawRequest) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const result = await service.withdraw(request);
        update(state => ({
          ...state,
          wallet: state.wallet ? { ...state.wallet, balance: state.wallet.balance - request.amount } : null,
          transactions: [{
            id: result.id,
            walletId: state.wallet?.id || '',
            type: 'withdraw',
            amount: request.amount,
            currency: request.currency,
            status: 'completed',
            date: new Date().toISOString(),
            description: `Withdrawal to ${request.destination || 'external'}`
          }, ...state.transactions],
          loading: false
        }));
        return result;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Withdrawal failed',
          loading: false
        }));
        throw error;
      }
    },

    async transfer(request: TransferRequest) {
      update(state => ({ ...state, loading: true, error: null }));
      try {
        const result = await service.transfer(request);
        update(state => ({
          ...state,
          wallet: state.wallet ? { ...state.wallet, balance: state.wallet.balance - request.amount } : null,
          transactions: [{
            id: result.id,
            walletId: state.wallet?.id || '',
            type: 'transfer_out',
            amount: request.amount,
            currency: request.currency,
            status: 'completed',
            date: new Date().toISOString(),
            description: request.note || `Transfer to ${request.recipientId}`,
            recipientId: request.recipientId
          }, ...state.transactions],
          loading: false
        }));
        return result;
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Transfer failed',
          loading: false
        }));
        throw error;
      }
    },

    clearError() {
      update(state => ({ ...state, error: null }));
    },
    reset() {
      set(initialState);
    }
  };
}

export const walletStore = createWalletStore();
export const { loadWallet, checkBalance, deposit, withdraw, transfer, clearError, reset } = walletStore;
