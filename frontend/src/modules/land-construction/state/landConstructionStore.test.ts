import { describe, it, expect, vi, beforeEach } from 'vitest';
import { landConstructionStore, landConstructionPermits, landConstructionLoading } from './landConstructionStore';
import { landConstructionService } from '../services/landConstructionService';
import type { LandConstructionPermit } from '../contracts/landConstructionTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = landConstructionStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factory
const mockPermit = (overrides: Partial<LandConstructionPermit> = {}): LandConstructionPermit => ({
  id: `permit-${Math.random().toString(36).substr(2, 5)}`,
  address: '123 Main St',
  type: 'residential',
  status: 'approved',
  issuedDate: new Date().toISOString(),
  ...overrides
});

describe('LandConstructionStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    landConstructionStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.permits).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let permits: LandConstructionPermit[] = [], loading = false;
      const unsubP = landConstructionPermits.subscribe(s => { permits = s; });
      const unsubL = landConstructionLoading.subscribe(s => { loading = s; });
      expect(permits).toEqual([]);
      expect(loading).toBe(false);
      unsubP(); unsubL();
    });
  });

  describe('loadPermits', () => {
    it('loads permits successfully', async () => {
      const permits = [mockPermit(), mockPermit()];
      vi.spyOn(landConstructionService, 'getPermits').mockResolvedValue(permits);

      await landConstructionStore.loadPermits();

      expect(landConstructionService.getPermits).toHaveBeenCalledTimes(1);
      const state = getState();
      expect(state.permits).toEqual(permits);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(landConstructionService, 'getPermits').mockReturnValue(p as any);

      const loadPromise = landConstructionStore.loadPermits();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockPermit()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch permits');
      vi.spyOn(landConstructionService, 'getPermits').mockRejectedValue(error);

      await landConstructionStore.loadPermits();

      const state = getState();
      expect(state.error).toBe('Failed to fetch permits');
      expect(state.loading).toBe(false);
      expect(state.permits).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(landConstructionService, 'getPermits').mockRejectedValue(new Error('test error'));
      await landConstructionStore.loadPermits().catch(() => {});

      expect(getState().error).toBe('test error');

      landConstructionStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Load some permits
      const permits = [mockPermit(), mockPermit()];
      vi.spyOn(landConstructionService, 'getPermits').mockResolvedValue(permits);
      await landConstructionStore.loadPermits();
      expect(getState().permits).toHaveLength(2);

      // Reset
      landConstructionStore.reset();

      const state = getState();
      expect(state.permits).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
