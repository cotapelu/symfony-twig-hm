import { describe, it, expect, vi, beforeEach } from 'vitest';
import { dealsStore, deals, dealsLoading } from './dealsStore';
import { dealsService } from '../services/dealsService';
import type { Deal } from '../contracts/dealsTypes';

function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockDeal = (overrides: Partial<Deal> = {}): Deal => ({
  id: `deal-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Test Deal',
  description: 'A test deal',
  discount: 10,
  validUntil: new Date().toISOString(),
  ...overrides
});

describe('DealsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    dealsStore.reset();
  });

  describe('initial state', () => {
    it('has empty deals and defaults', () => {
      const state = getCurrentState(dealsStore);
      expect(state.deals).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived deals matches state.deals', () => {
      expect(getCurrentState(deals)).toEqual([]);
    });

    it('derived dealsLoading matches state.loading', () => {
      expect(getCurrentState(dealsLoading)).toBe(false);
    });
  });

  describe('loadDeals', () => {
    it('loads deals and updates state', async () => {
      const dealsList = [mockDeal(), mockDeal()];
      vi.spyOn(dealsService, 'getDeals').mockResolvedValue(dealsList);
      await dealsStore.loadDeals();
      const state = getCurrentState(dealsStore);
      expect(state.deals).toEqual(dealsList);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(dealsService, 'getDeals').mockReturnValue(p as any);
      const loadPromise = dealsStore.loadDeals();
      // Allow microtask to propagate loading update
      await new Promise(process.nextTick);
      expect(getCurrentState(dealsLoading)).toBe(true);
      resolvePromise([mockDeal()]);
      await loadPromise;
      expect(getCurrentState(dealsLoading)).toBe(false);
    });

    it('updates deals derived on success', async () => {
      const dealsList = [mockDeal()];
      vi.spyOn(dealsService, 'getDeals').mockResolvedValue(dealsList);
      await dealsStore.loadDeals();
      expect(getCurrentState(deals)).toEqual(dealsList);
    });

    it('handles empty response', async () => {
      vi.spyOn(dealsService, 'getDeals').mockResolvedValue([]);
      await dealsStore.loadDeals();
      expect(getCurrentState(deals)).toEqual([]);
      expect(getCurrentState(dealsLoading)).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(dealsService, 'getDeals').mockRejectedValue(new Error('Fetch failed'));
      await dealsStore.loadDeals();
      const state = getCurrentState(dealsStore);
      expect(state.error).toBe('Fetch failed');
      expect(state.loading).toBe(false);
      expect(state.deals).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(dealsService, 'getDeals').mockRejectedValue(new Error('test'));
      try { await dealsStore.loadDeals(); } catch {}
      let state = getCurrentState(dealsStore);
      expect(state.error).toBe('test');
      dealsStore.clearError();
      state = getCurrentState(dealsStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const dealsList = [mockDeal()];
      vi.spyOn(dealsService, 'getDeals').mockResolvedValue(dealsList);
      await dealsStore.loadDeals();
      let state = getCurrentState(dealsStore);
      expect(state.deals.length).toBeGreaterThan(0);
      dealsStore.reset();
      state = getCurrentState(dealsStore);
      expect(state.deals).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
