import { describe, it, expect, vi, beforeEach } from 'vitest';
import { educationGovStore, educationGovServices, educationGovLoading } from './educationGovStore';
import { educationGovServiceModule } from '../services/educationGovService';
import type { EducationGovService } from '../contracts/educationGovTypes';

function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockService = (overrides: Partial<EducationGovService> = {}): EducationGovService => ({
  id: `svc-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test Service',
  category: 'education',
  description: 'A test government education service',
  requirements: ['doc1', 'doc2'],
  ...overrides
});

describe('EducationGovStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    educationGovStore.reset();
  });

  describe('initial state', () => {
    it('has empty services and defaults', () => {
      const state = getCurrentState(educationGovStore);
      expect(state.services).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived educationGovServices matches state.services', () => {
      expect(getCurrentState(educationGovServices)).toEqual([]);
    });

    it('derived educationGovLoading matches state.loading', () => {
      expect(getCurrentState(educationGovLoading)).toBe(false);
    });
  });

  describe('loadServices', () => {
    it('loads services and updates state', async () => {
      const services = [mockService(), mockService()];
      vi.spyOn(educationGovServiceModule, 'getServices').mockResolvedValue(services);
      await educationGovStore.loadServices();
      const state = getCurrentState(educationGovStore);
      expect(state.services).toEqual(services);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(educationGovServiceModule, 'getServices').mockRejectedValue(new Error('Fetch failed'));
      await educationGovStore.loadServices();
      const state = getCurrentState(educationGovStore);
      expect(state.error).toBe('Fetch failed');
      expect(state.services).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(educationGovServiceModule, 'getServices').mockRejectedValue(new Error('test'));
      try { await educationGovStore.loadServices(); } catch {}
      let state = getCurrentState(educationGovStore);
      expect(state.error).toBe('test');
      educationGovStore.clearError();
      state = getCurrentState(educationGovStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const services = [mockService()];
      vi.spyOn(educationGovServiceModule, 'getServices').mockResolvedValue(services);
      await educationGovStore.loadServices();
      let state = getCurrentState(educationGovStore);
      expect(state.services.length).toBeGreaterThan(0);
      educationGovStore.reset();
      state = getCurrentState(educationGovStore);
      expect(state.services).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
