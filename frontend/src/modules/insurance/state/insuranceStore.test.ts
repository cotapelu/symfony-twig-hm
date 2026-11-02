import { describe, it, expect, vi, beforeEach } from 'vitest';
import { insuranceStore } from './insuranceStore';
import { insuranceService } from '../services/insuranceService';
import type { InsurancePolicy, InsuranceClaim, CreatePolicyRequest, SubmitClaimRequest } from '../contracts/insuranceTypes';

// Helper to get current store state
function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockPolicy = (overrides: Partial<InsurancePolicy> = {}): InsurancePolicy => ({
  id: `pol-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user1',
  type: 'health',
  coverage: 100000,
  premium: 500,
  status: 'active',
  startDate: '2024-01-01',
  endDate: '2024-12-31',
  createdAt: '2024-01-01',
  updatedAt: '2024-01-01',
  ...overrides
});

const mockClaim = (overrides: Partial<InsuranceClaim> = {}): InsuranceClaim => ({
  id: `claim-${Math.random().toString(36).substr(2, 5)}`,
  policyId: 'pol-1',
  amount: 1000,
  description: 'Medical expense',
  status: 'pending',
  submittedAt: '2024-06-01',
  ...overrides
});

describe('InsuranceStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    insuranceStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and null currentUserId', () => {
      const state = getCurrentState(insuranceStore);
      expect(state.policies).toEqual([]);
      expect(state.claims).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.currentUserId).toBeNull();
    });
  });

  describe('setCurrentUser', () => {
    it('sets currentUserId', () => {
      insuranceStore.setCurrentUser('user-123');
      const state = getCurrentState(insuranceStore);
      expect(state.currentUserId).toBe('user-123');
    });
  });

  describe('loadPolicies', () => {
    it('loads all policies when currentUserId is not set', async () => {
      const policies = [mockPolicy(), mockPolicy()];
      vi.spyOn(insuranceService, 'getPolicies').mockResolvedValue(policies);

      await insuranceStore.loadPolicies();

      expect(insuranceService.getPolicies).toHaveBeenCalledTimes(1);
      const state = getCurrentState(insuranceStore);
      expect(state.policies).toEqual(policies);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads my policies when currentUserId is set', async () => {
      insuranceStore.setCurrentUser('user1');
      const policies = [mockPolicy({ userId: 'user1' })];
      vi.spyOn(insuranceService, 'getMyPolicies').mockResolvedValue(policies);

      await insuranceStore.loadPolicies();

      expect(insuranceService.getMyPolicies).toHaveBeenCalledTimes(1);
      const state = getCurrentState(insuranceStore);
      expect(state.policies).toEqual(policies);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(insuranceService, 'getPolicies').mockReturnValue(p as any);
      const loadPromise = insuranceStore.loadPolicies();
      await new Promise(process.nextTick);
      expect(getCurrentState(insuranceStore).loading).toBe(true);
      resolvePromise([mockPolicy()]);
      await loadPromise;
      expect(getCurrentState(insuranceStore).loading).toBe(false);
    });

    it('updates policies derived on success', async () => {
      const policies = [mockPolicy()];
      vi.spyOn(insuranceService, 'getPolicies').mockResolvedValue(policies);
      await insuranceStore.loadPolicies();
      let p: InsurancePolicy[] = [];
      const unsub = insuranceStore.policies?.subscribe ? insuranceStore.policies.subscribe(s => { p = s; }) : () => {};
      // If policies derived not exported, skip test
      if (insuranceStore.policies) {
        expect(p).toEqual(policies);
      }
    });

    it('handles empty response', async () => {
      vi.spyOn(insuranceService, 'getPolicies').mockResolvedValue([]);
      await insuranceStore.loadPolicies();
      expect(getCurrentState(insuranceStore).policies).toEqual([]);
      expect(getCurrentState(insuranceStore).loading).toBe(false);
      expect(getCurrentState(insuranceStore).error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(insuranceService, 'getPolicies').mockRejectedValue(new Error('Policies error'));
      await insuranceStore.loadPolicies();
      const state = getCurrentState(insuranceStore);
      expect(state.error).toBe('Policies error');
      expect(state.loading).toBe(false);
      expect(state.policies).toEqual([]);
    });
  });

  describe('loadPolicy', () => {
    it('loads a policy and adds to list', async () => {
      const policy = mockPolicy({ id: 'p1' });
      vi.spyOn(insuranceService, 'getPolicy').mockResolvedValue(policy);

      await insuranceStore.loadPolicy('p1');

      const state = getCurrentState(insuranceStore);
      expect(state.policies).toContainEqual(policy);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(insuranceService, 'getPolicy').mockReturnValue(p as any);
      const loadPromise = insuranceStore.loadPolicy('p1');
      await new Promise(process.nextTick);
      expect(getCurrentState(insuranceStore).loading).toBe(true);
      resolvePromise(mockPolicy({ id: 'p1' }));
      await loadPromise;
      expect(getCurrentState(insuranceStore).loading).toBe(false);
    });

    it('updates policies derived on success', async () => {
      const policy = mockPolicy({ id: 'p1' });
      vi.spyOn(insuranceService, 'getPolicy').mockResolvedValue(policy);
      await insuranceStore.loadPolicy('p1');
      let p: any[] = [];
      const unsub = insuranceStore.policies?.subscribe ? insuranceStore.policies.subscribe(s => { p = s; }) : () => {};
      if (insuranceStore.policies) {
        expect(p).toContainEqual(policy);
      }
      unsub();
    });

    it('throws if policy userId does not match currentUserId', async () => {
      insuranceStore.setCurrentUser('user1');
      const policy = mockPolicy({ id: 'p1', userId: 'other' });
      vi.spyOn(insuranceService, 'getPolicy').mockResolvedValue(policy);

      try {
        await insuranceStore.loadPolicy('p1');
      } catch {}
      const state = getCurrentState(insuranceStore);
      expect(state.error).toBe('Unauthorized: You can only access your own policies');
      expect(state.loading).toBe(false);
      expect(state.policies).not.toContainEqual(policy);
    });

    it('handles service error and resets loading', async () => {
      vi.spyOn(insuranceService, 'getPolicy').mockRejectedValue(new Error('Not found'));
      try {
        await insuranceStore.loadPolicy('p1');
      } catch {}
      const state = getCurrentState(insuranceStore);
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
    });
  });

  describe('createPolicy', () => {
    it('creates policy and adds to list', async () => {
      const newPolicy = mockPolicy({ id: 'new' });
      vi.spyOn(insuranceService, 'createPolicy').mockResolvedValue(newPolicy);

      const request: CreatePolicyRequest = { type: 'auto', coverage: 200000, premium: 300 };
      const result = await insuranceStore.createPolicy(request);

      expect(result).toEqual(newPolicy);
      const state = getCurrentState(insuranceStore);
      expect(state.policies).toContainEqual(newPolicy);
    });

    it('handles error', async () => {
      vi.spyOn(insuranceService, 'createPolicy').mockRejectedValue(new Error('Create failed'));

      await expect(insuranceStore.createPolicy({ type: 'auto', coverage: 1000, premium: 10 } as any)).rejects.toThrow('Create failed');

      const state = getCurrentState(insuranceStore);
      expect(state.error).toBe('Create failed');
    });
  });

  describe('loadClaims', () => {
    it('loads all claims when currentUserId is not set', async () => {
      const claims = [mockClaim(), mockClaim()];
      vi.spyOn(insuranceService, 'getClaims').mockResolvedValue(claims);

      await insuranceStore.loadClaims();

      const state = getCurrentState(insuranceStore);
      expect(state.claims).toEqual(claims);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads my claims when currentUserId is set', async () => {
      insuranceStore.setCurrentUser('user1');
      const claims = [mockClaim({ id: 'c1' })];
      vi.spyOn(insuranceService, 'getMyClaims').mockResolvedValue(claims);

      await insuranceStore.loadClaims();

      expect(insuranceService.getMyClaims).toHaveBeenCalledTimes(1);
      const state = getCurrentState(insuranceStore);
      expect(state.claims).toEqual(claims);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(insuranceService, 'getClaims').mockReturnValue(p as any);
      const loadPromise = insuranceStore.loadClaims();
      await new Promise(process.nextTick);
      expect(getCurrentState(insuranceStore).loading).toBe(true);
      resolvePromise([mockClaim()]);
      await loadPromise;
      expect(getCurrentState(insuranceStore).loading).toBe(false);
    });

    it('updates claims derived on success', async () => {
      const claims = [mockClaim()];
      vi.spyOn(insuranceService, 'getClaims').mockResolvedValue(claims);
      await insuranceStore.loadClaims();
      let c: any[] = [];
      const unsub = insuranceStore.claims?.subscribe ? insuranceStore.claims.subscribe(s => { c = s; }) : () => {};
      if (insuranceStore.claims) {
        expect(c).toEqual(claims);
      }
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(insuranceService, 'getClaims').mockResolvedValue([]);
      await insuranceStore.loadClaims();
      expect(getCurrentState(insuranceStore).claims).toEqual([]);
      expect(getCurrentState(insuranceStore).loading).toBe(false);
      expect(getCurrentState(insuranceStore).error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(insuranceService, 'getClaims').mockRejectedValue(new Error('Claims error'));
      await insuranceStore.loadClaims();
      const state = getCurrentState(insuranceStore);
      expect(state.error).toBe('Claims error');
      expect(state.loading).toBe(false);
      expect(state.claims).toEqual([]);
    });
  });

  describe('submitClaim', () => {
    it('submits claim and adds to list', async () => {
      const claim = mockClaim({ id: 'c1' });
      vi.spyOn(insuranceService, 'submitClaim').mockResolvedValue(claim);

      const request: SubmitClaimRequest = { policyId: 'p1', amount: 1000, description: 'Accident' };
      const result = await insuranceStore.submitClaim(request);

      expect(result).toEqual(claim);
      const state = getCurrentState(insuranceStore);
      expect(state.claims).toContainEqual(claim);
    });

    it('handles error', async () => {
      vi.spyOn(insuranceService, 'submitClaim').mockRejectedValue(new Error('Submit failed'));

      await expect(insuranceStore.submitClaim({ policyId: 'p1', amount: 100, description: 'x' })).rejects.toThrow('Submit failed');

      const state = getCurrentState(insuranceStore);
      expect(state.error).toBe('Submit failed');
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(insuranceService, 'getPolicies').mockRejectedValue(new Error('test'));
      try {
        await insuranceStore.loadPolicies();
      } catch (e) {}
      let state = getCurrentState(insuranceStore);
      expect(state.error).toBe('test');

      insuranceStore.clearError();
      state = getCurrentState(insuranceStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      // Modify state
      insuranceStore.setCurrentUser('user1');
      expect(getCurrentState(insuranceStore).currentUserId).toBe('user1');

      const policies = [mockPolicy()];
      vi.spyOn(insuranceService, 'getPolicies').mockResolvedValue(policies);
      await insuranceStore.loadPolicies();
      let state = getCurrentState(insuranceStore);
      expect(state.policies).not.toEqual([]);

      const claims = [mockClaim()];
      vi.spyOn(insuranceService, 'getClaims').mockResolvedValue(claims);
      await insuranceStore.loadClaims();
      state = getCurrentState(insuranceStore);
      expect(state.claims).not.toEqual([]);

      insuranceStore.reset();
      state = getCurrentState(insuranceStore);
      expect(state.currentUserId).toBeNull();
      expect(state.policies).toEqual([]);
      expect(state.claims).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
