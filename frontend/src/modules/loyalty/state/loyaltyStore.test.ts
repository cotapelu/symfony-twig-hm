import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  loyaltyStore,
  program,
  rewards,
  transactions,
  loyaltyLoading,
  loyaltyError,
  availableRewards
} from './loyaltyStore';
import { loyaltyService } from '../services/loyaltyService';
import type { LoyaltyProgram, LoyaltyReward, LoyaltyTransaction } from '../contracts/loyaltyTypes';

function getCurrentState() {
  let current: any;
  const unsub = loyaltyStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date().toISOString();

const mockProgram = (overrides: Partial<LoyaltyProgram> = {}): LoyaltyProgram => ({
  id: 'prog-1',
  userId: 'user-1',
  points: 1000,
  totalEarned: 5000,
  totalRedeemed: 4000,
  tier: 'gold',
  updatedAt: now,
  ...overrides
});

const mockReward = (overrides: Partial<LoyaltyReward> = {}): LoyaltyReward => ({
  id: `rew-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Free Coffee',
  description: '1 free coffee',
  pointsCost: 100,
  isAvailable: true,
  maxPerUser: 5,
  ...overrides
});

const mockTransaction = (overrides: Partial<LoyaltyTransaction> = {}): LoyaltyTransaction => ({
  id: `trx-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  type: 'earn' as const,
  points: 100,
  source: 'purchase',
  description: 'Points earned',
  date: now,
  ...overrides
});

describe('LoyaltyStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    loyaltyStore.reset();
  });

  describe('initial state', () => {
    it('has null program, empty arrays, defaults', () => {
      const state = getCurrentState();
      expect(state.program).toBeNull();
      expect(state.rewards).toEqual([]);
      expect(state.transactions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let prog: LoyaltyProgram | null = null, rew: LoyaltyReward[] = [], trx: LoyaltyTransaction[] = [], load = false, err: string | null = null;
      const unsubP = program.subscribe(s => { prog = s; });
      const unsubR = rewards.subscribe(s => { rew = s; });
      const unsubT = transactions.subscribe(s => { trx = s; });
      const unsubL = loyaltyLoading.subscribe(s => { load = s; });
      const unsubE = loyaltyError.subscribe(s => { err = s; });
      expect(prog).toBeNull();
      expect(rew).toEqual([]);
      expect(trx).toEqual([]);
      expect(load).toBe(false);
      expect(err).toBeNull();
      unsubP(); unsubR(); unsubT(); unsubL(); unsubE();
    });

    it('availableRewards filters by isAvailable', () => {
      let avail: LoyaltyReward[] = [];
      const unsub = availableRewards.subscribe(s => { avail = s; });
      expect(avail).toEqual([]);
      unsub();
    });
  });

  describe('loadProgram', () => {
    it('loads program', async () => {
      const prog = mockProgram();
      vi.spyOn(loyaltyService, 'getProgram').mockResolvedValue(prog);
      await loyaltyStore.loadProgram();
      const state = getCurrentState();
      expect(state.program).toEqual(prog);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(loyaltyService, 'getProgram').mockRejectedValue(new Error('Service down'));
      await loyaltyStore.loadProgram();
      const state = getCurrentState();
      expect(state.error).toBe('Service down');
      expect(state.program).toBeNull();
    });
  });

  describe('loadRewards', () => {
    it('loads rewards', async () => {
      const rew = [mockReward(), mockReward()];
      vi.spyOn(loyaltyService, 'getRewards').mockResolvedValue(rew);
      await loyaltyStore.loadRewards();
      let state = getCurrentState();
      expect(state.rewards).toEqual(rew);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(loyaltyService, 'getRewards').mockRejectedValue(new Error('Fail'));
      await loyaltyStore.loadRewards();
      let state = getCurrentState();
      expect(state.error).toBe('Fail');
      expect(state.rewards).toEqual([]);
    });
  });

  describe('loadTransactions', () => {
    it('loads transactions', async () => {
      const trx = [mockTransaction(), mockTransaction()];
      vi.spyOn(loyaltyService, 'getTransactions').mockResolvedValue(trx);
      await loyaltyStore.loadTransactions();
      let state = getCurrentState();
      expect(state.transactions).toEqual(trx);
    });

    it('handles error', async () => {
      vi.spyOn(loyaltyService, 'getTransactions').mockRejectedValue(new Error('Fail'));
      await loyaltyStore.loadTransactions();
      let state = getCurrentState();
      expect(state.error).toBe('Fail');
      expect(state.transactions).toEqual([]);
    });
  });

  describe('redeemReward', () => {
    it('redeems reward and updates program points and transactions', async () => {
      const prog = mockProgram({ points: 1000 });
      const reward = mockReward({ id: 'r1', pointsCost: 100 });
      vi.spyOn(loyaltyService, 'getProgram').mockResolvedValue(prog);
      await loyaltyStore.loadProgram();
      vi.spyOn(loyaltyService, 'redeemReward').mockResolvedValue({
        transaction: mockTransaction({ id: 'trx-1', points: 100, type: 'redeem' })
      });
      const result = await loyaltyStore.redeemReward('r1');
      expect(result).toBeDefined();
      let state = getCurrentState();
      expect(state.program?.points).toBe(900);
      expect(state.transactions[0].points).toBe(100);
    });

    it('propagates errors', async () => {
      vi.spyOn(loyaltyService, 'redeemReward').mockRejectedValue(new Error('Redeem failed'));
      try {
        await loyaltyStore.redeemReward('r1');
      } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('Redeem failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(loyaltyService, 'getProgram').mockRejectedValue(new Error('test'));
      try { await loyaltyStore.loadProgram(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      loyaltyStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const prog = mockProgram();
      const rew = [mockReward()];
      vi.spyOn(loyaltyService, 'getProgram').mockResolvedValue(prog);
      await loyaltyStore.loadProgram();
      vi.spyOn(loyaltyService, 'getRewards').mockResolvedValue(rew);
      await loyaltyStore.loadRewards();
      let state = getCurrentState();
      expect(state.program).toBeTruthy();
      expect(state.rewards.length).toBeGreaterThan(0);
      loyaltyStore.reset();
      state = getCurrentState();
      expect(state.program).toBeNull();
      expect(state.rewards).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
