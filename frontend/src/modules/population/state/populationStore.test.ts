import { describe, it, expect, vi, beforeEach } from 'vitest';
import { populationStore, populationRecords, populationLoading } from './populationStore';
import { populationService } from '../services/populationService';
import type { PopulationRecord } from '../contracts/populationTypes';

function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockRecord = (overrides: Partial<PopulationRecord> = {}): PopulationRecord => ({
  id: `pop-${Math.random().toString(36).substr(2, 5)}`,
  area: 'Test Area',
  totalPopulation: 1000,
  male: 500,
  female: 500,
  year: 2024,
  ...overrides
});

describe('PopulationStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    populationStore.reset();
  });

  describe('initial state', () => {
    it('has empty records and defaults', () => {
      const state = getCurrentState(populationStore);
      expect(state.records).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived populationRecords matches state.records', () => {
      const records = getCurrentState(populationRecords);
      expect(records).toEqual([]);
    });

    it('derived populationLoading matches state.loading', () => {
      const loading = getCurrentState(populationLoading);
      expect(loading).toBe(false);
    });
  });

  describe('loadRecords', () => {
    it('loads records and updates state', async () => {
      const records = [mockRecord(), mockRecord()];
      vi.spyOn(populationService, 'getRecords').mockResolvedValue(records);

      await populationStore.loadRecords();

      const state = getCurrentState(populationStore);
      expect(state.records).toEqual(records);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(populationService, 'getRecords').mockReturnValue(p as any);
      const loadPromise = populationStore.loadRecords();
      await new Promise(process.nextTick);
      expect(getCurrentState(populationStore).loading).toBe(true);
      resolvePromise([mockRecord()]);
      await loadPromise;
      expect(getCurrentState(populationStore).loading).toBe(false);
    });

    it('updates populationRecords derived on success', async () => {
      const records = [mockRecord()];
      vi.spyOn(populationService, 'getRecords').mockResolvedValue(records);
      await populationStore.loadRecords();
      let r: PopulationRecord[] = [];
      const unsub = populationRecords.subscribe(s => { r = s; });
      expect(r).toEqual(records);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(populationService, 'getRecords').mockResolvedValue([]);
      await populationStore.loadRecords();
      expect(getCurrentState(populationStore).records).toEqual([]);
      expect(getCurrentState(populationStore).loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(populationService, 'getRecords').mockRejectedValue(new Error('Network error'));
      await populationStore.loadRecords();
      const state = getCurrentState(populationStore);
      expect(state.error).toBe('Network error');
      expect(state.loading).toBe(false);
      expect(state.records).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(populationService, 'getRecords').mockRejectedValue(new Error('test error'));
      try {
        await populationStore.loadRecords();
      } catch {}
      let state = getCurrentState(populationStore);
      expect(state.error).toBe('test error');

      populationStore.clearError();
      state = getCurrentState(populationStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state after operations', async () => {
      const records = [mockRecord()];
      vi.spyOn(populationService, 'getRecords').mockResolvedValue(records);
      await populationStore.loadRecords();
      let state = getCurrentState(populationStore);
      expect(state.records.length).toBeGreaterThan(0);

      populationStore.reset();
      state = getCurrentState(populationStore);
      expect(state.records).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
