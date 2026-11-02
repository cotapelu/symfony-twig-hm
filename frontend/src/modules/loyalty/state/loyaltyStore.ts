import { writable, derived } from 'svelte/store';
import type { LoyaltyProgram, LoyaltyReward, LoyaltyTransaction, LoyaltyState } from '../contracts/loyaltyTypes';
import { loyaltyService } from '../services/loyaltyService';

const initialState: LoyaltyState = {
  program: null,
  rewards: [],
  transactions: [],
  loading: false,
  error: null
};

function createLoyaltyStore() {
  const { subscribe, set, update } = writable<LoyaltyState>(initialState);

  return {
    subscribe,

    async loadProgram() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const program = await loyaltyService.getProgram();
        update(s => ({ ...s, program, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadRewards() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const rewards = await loyaltyService.getRewards();
        update(s => ({ ...s, rewards, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadTransactions() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const transactions = await loyaltyService.getTransactions();
        update(s => ({ ...s, transactions, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async redeemReward(rewardId: string, quantity?: number) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const result = await loyaltyService.redeemReward({ rewardId, quantity });
        update(s => ({
          ...s,
          program: s.program ? { ...s.program, points: s.program.points - result.transaction.points, totalRedeemed: s.program.totalRedeemed + result.transaction.points } : null,
          transactions: [result.transaction, ...s.transactions],
          loading: false
        }));
        return result;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const loyaltyStore = createLoyaltyStore();

export const program = derived(loyaltyStore, $s => $s.program);
export const rewards = derived(loyaltyStore, $s => $s.rewards);
export const transactions = derived(loyaltyStore, $s => $s.transactions);
export const loyaltyLoading = derived(loyaltyStore, $s => $s.loading);
export const loyaltyError = derived(loyaltyStore, $s => $s.error);
export const availableRewards = derived(loyaltyStore, $s => $s.rewards.filter(r => r.isAvailable));
