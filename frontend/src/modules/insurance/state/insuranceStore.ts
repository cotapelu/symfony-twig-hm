import { writable, derived, get } from 'svelte/store';
import type { InsurancePolicy, InsuranceClaim, InsuranceState, CreatePolicyRequest, SubmitClaimRequest } from '../contracts/insuranceTypes';
import { insuranceService } from '../services/insuranceService';

const initialState: InsuranceState = { 
  policies: [], 
  claims: [], 
  loading: false, 
  error: null,
  currentUserId: null 
};

function createInsuranceStore() {
  const { subscribe, set, update } = writable<InsuranceState>(initialState);
  return {
    subscribe,
    
    setCurrentUser(userId: string) {
      update(s => ({ ...s, currentUserId: userId }));
    },
    
    async loadPolicies() {
      update(s => ({ ...s, loading: true, error: null }));
      try { 
        const state = get({ subscribe });
        const policies = state.currentUserId 
          ? await insuranceService.getMyPolicies()
          : await insuranceService.getPolicies(); 
        update(s => ({ ...s, policies, loading: false })); 
      }
      catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    
    async loadPolicy(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try { 
        const state = get({ subscribe });
        const policy = await insuranceService.getPolicy(id);
        
        if (state.currentUserId && policy.userId !== state.currentUserId) {
          throw new Error('Unauthorized: You can only access your own policies');
        }
        
        update(s => ({ ...s, policies: [...s.policies.filter(p => p.id !== id), policy], loading: false })); 
        return policy; 
      }
      catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); throw error; }
    },
    
    async createPolicy(request: CreatePolicyRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try { const policy = await insuranceService.createPolicy(request); update(s => ({ ...s, policies: [...s.policies, policy], loading: false })); return policy; }
      catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); throw error; }
    },
    
    async loadClaims() {
      update(s => ({ ...s, loading: true, error: null }));
      try { 
        const state = get({ subscribe });
        const claims = state.currentUserId 
          ? await insuranceService.getMyClaims()
          : await insuranceService.getClaims(); 
        update(s => ({ ...s, claims, loading: false })); 
      }
      catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    
    async submitClaim(request: SubmitClaimRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try { const claim = await insuranceService.submitClaim(request); update(s => ({ ...s, claims: [...s.claims, claim], loading: false })); return claim; }
      catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); throw error; }
    },
    
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const insuranceStore = createInsuranceStore();
export const policies = derived(insuranceStore, $s => $s.policies);
export const claims = derived(insuranceStore, $s => $s.claims);
export const insuranceLoading = derived(insuranceStore, $s => $s.loading);
export const insuranceError = derived(insuranceStore, $s => $s.error);
export const activePolicies = derived(insuranceStore, $s => $s.policies.filter(p => p.status === 'active'));
