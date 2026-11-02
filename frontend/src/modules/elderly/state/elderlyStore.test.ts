import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  elderlyStore,
  elderlyServices,
  elderlyLoading
} from './elderlyStore';
import { elderlyServiceModule } from '../services/elderlyService';
import type { ElderlyService } from '../contracts/elderlyTypes';

function getCurrentState() {
  let current: any;
  const unsub = elderlyStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockService = (overrides: Partial<ElderlyService> = {}): ElderlyService => ({
  id: `serv-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Home Care',
  category: 'health',
  description: 'In-home assistance',
  eligibility: ['Age >= 65'],
  ...overrides
});

describe('ElderlyStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    elderlyStore.reset();
  });

  describe('initial state', () => {
    it('has empty services and defaults', () => {
      const state = getCurrentState();
      expect(state.services).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived elderlyServices matches state.services', () => {
      let svcs: ElderlyService[] = [];
      const unsub = elderlyServices.subscribe(s => { svcs = s; });
      expect(svcs).toEqual([]);
      unsub();
    });

    it('derived elderlyLoading matches state.loading', () => {
      let load = false;
      const unsub = elderlyLoading.subscribe(s => { load = s; });
      expect(load).toBe(false);
      unsub();
    });
  });

  describe('loadServices', () => {
    it('loads services and updates state', async () => {
      const svcs = [mockService(), mockService()];
      vi.spyOn(elderlyServiceModule, 'getServices').mockResolvedValue(svcs);
      await elderlyStore.loadServices();
      const state = getCurrentState();
      expect(state.services).toEqual(svcs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(elderlyServiceModule, 'getServices').mockReturnValue(p as any);
      const loadPromise = elderlyStore.loadServices();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockService()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates elderlyServices derived on success', async () => {
      const svcs = [mockService()];
      vi.spyOn(elderlyServiceModule, 'getServices').mockResolvedValue(svcs);
      await elderlyStore.loadServices();
      let s: ElderlyService[] = [];
      const unsub = elderlyServices.subscribe(val => { s = val; });
      expect(s).toEqual(svcs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(elderlyServiceModule, 'getServices').mockResolvedValue([]);
      await elderlyStore.loadServices();
      expect(getCurrentState().services).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(elderlyServiceModule, 'getServices').mockRejectedValue(new Error('Service down'));
      await elderlyStore.loadServices();
      const state = getCurrentState();
      expect(state.error).toBe('Service down');
      expect(state.loading).toBe(false);
      expect(state.services).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(elderlyServiceModule, 'getServices').mockRejectedValue(new Error('test'));
      try { await elderlyStore.loadServices(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      elderlyStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const svcs = [mockService()];
      vi.spyOn(elderlyServiceModule, 'getServices').mockResolvedValue(svcs);
      await elderlyStore.loadServices();
      let state = getCurrentState();
      expect(state.services.length).toBeGreaterThan(0);
      elderlyStore.reset();
      state = getCurrentState();
      expect(state.services).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
