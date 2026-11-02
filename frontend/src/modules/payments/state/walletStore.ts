import { writable, derived } from 'svelte/store';

export interface Wallet {
  id: number;
  user_id: string;
  balance: number;
  currency: string;
  status: string;
  is_default: boolean;
  created_at: string;
}

export interface Transaction {
  id: number;
  wallet_id: number;
  type: string;
  amount: number;
  balance_after: number;
  description: string;
  reference_id: string;
  metadata: string;
  status: string;
  created_at: string;
}

export interface PaymentMethod {
  method: string;
  name: string;
  description: string;
  icon: string;
  available: boolean;
  balance?: number;
  currency?: string;
  accounts?: any[];
  message?: string;
}

interface WalletState {
  wallet: Wallet | null;
  transactions: Transaction[];
  paymentMethods: PaymentMethod[];
  loading: boolean;
  error: string | null;
}

function createWalletStore() {
  const { subscribe, set, update } = writable<WalletState>({
    wallet: null,
    transactions: [],
    paymentMethods: [],
    loading: false,
    error: null
  });

  return {
    subscribe,

    loadWallet: async (userId: string): Promise<Wallet> => {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const response = await fetch(`/api/wallet/${userId}`);
        
        if (!response.ok) {
          throw new Error('Failed to load wallet');
        }

        const data = await response.json();
        
        update(state => ({ ...state, wallet: data.wallet, loading: false }));
        
        return data.wallet;
      } catch (error) {
        update(state => ({ 
          ...state, 
          loading: false, 
          error: (error as Error).message 
        }));
        throw error;
      }
    },

    loadTransactions: async (userId: string, limit = 20, offset = 0): Promise<Transaction[]> => {
      try {
        const response = await fetch(`/api/wallet/${userId}/transactions?limit=${limit}&offset=${offset}`);
        
        if (!response.ok) {
          throw new Error('Failed to load transactions');
        }

        const data = await response.json();
        
        update(state => ({ ...state, transactions: data.transactions }));
        
        return data.transactions;
      } catch (error) {
        update(state => ({ error: (error as Error).message }));
        throw error;
      }
    },

    loadPaymentMethods: async (userId: string): Promise<PaymentMethod[]> => {
      try {
        const response = await fetch(`/api/payments/methods/${userId}`);
        
        if (!response.ok) {
          throw new Error('Failed to load payment methods');
        }

        const data = await response.json();
        
        update(state => ({ ...state, paymentMethods: data.methods }));
        
        return data.methods;
      } catch (error) {
        update(state => ({ error: (error as Error).message }));
        throw error;
      }
    },

    topUp: async (userId: string, amount: number, method: string, bankCode?: string): Promise<any> => {
      try {
        const response = await fetch('/api/wallet/topup', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            user_id: userId,
            amount,
            method,
            bank_code: bankCode
          })
        });

        if (!response.ok) {
          throw new Error('Top up failed');
        }

        const data = await response.json();
        
        // Reload wallet after topup
        await createWalletStore().loadWallet(userId);
        
        return data;
      } catch (error) {
        throw error;
      }
    },

    transfer: async (fromUserId: string, toUserId: string, amount: number, description?: string): Promise<any> => {
      try {
        const response = await fetch('/api/wallet/transfer', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            from_user_id: fromUserId,
            to_user_id: toUserId,
            amount,
            description
          })
        });

        if (!response.ok) {
          throw new Error('Transfer failed');
        }

        return await response.json();
      } catch (error) {
        throw error;
      }
    },

    createWallet: async (userId: string, currency = 'VND'): Promise<Wallet> => {
      try {
        const response = await fetch('/api/wallet/create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            user_id: userId,
            currency
          })
        });

        if (!response.ok) {
          throw new Error('Failed to create wallet');
        }

        const data = await response.json();
        update(state => ({ ...state, wallet: data.wallet }));
        
        return data.wallet;
      } catch (error) {
        throw error;
      }
    },

    clearError: () => {
      update(state => ({ ...state, error: null }));
    }
  };
}

export const walletStore = createWalletStore();
export const walletBalance = derived(
  walletStore,
  $store => $store.wallet?.balance || 0
);
export const walletCurrency = derived(
  walletStore,
  $store => $store.wallet?.currency || 'VND'
);
