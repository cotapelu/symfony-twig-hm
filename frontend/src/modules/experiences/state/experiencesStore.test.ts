import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  experiencesStore,
  experiences,
  experiencesLoading
} from './experiencesStore';
import { experiencesService } from '../services/experiencesService';
import type { Experience } from '../contracts/experiencesTypes';

function getCurrentState() {
  let current: any;
  const unsub = experiencesStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockExperience = (overrides: Partial<Experience> = {}): Experience => ({
  id: `exp-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  title: 'Amazing Trip',
  description: 'Great experience',
  category: 'travel',
  images: ['img1.jpg'],
  rating: 4.5,
  createdAt: new Date().toISOString(),
  ...overrides
});

describe('ExperiencesStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    experiencesStore.reset();
  });

  describe('initial state', () => {
    it('has empty experiences and defaults', () => {
      const state = getCurrentState();
      expect(state.experiences).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived experiences matches state.experiences', () => {
      let exps: Experience[] = [];
      const unsub = experiences.subscribe(s => { exps = s; });
      expect(exps).toEqual([]);
      unsub();
    });

    it('derived experiencesLoading matches state.loading', () => {
      let load = false;
      const unsub = experiencesLoading.subscribe(s => { load = s; });
      expect(load).toBe(false);
      unsub();
    });
  });

  describe('loadExperiences', () => {
    it('loads experiences and updates state', async () => {
      const exps = [mockExperience(), mockExperience()];
      vi.spyOn(experiencesService, 'getExperiences').mockResolvedValue(exps);
      await experiencesStore.loadExperiences();
      const state = getCurrentState();
      expect(state.experiences).toEqual(exps);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(experiencesService, 'getExperiences').mockRejectedValue(new Error('Service down'));
      await experiencesStore.loadExperiences();
      const state = getCurrentState();
      expect(state.error).toBe('Service down');
      expect(state.experiences).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(experiencesService, 'getExperiences').mockRejectedValue(new Error('test'));
      try { await experiencesStore.loadExperiences(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      experiencesStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const exps = [mockExperience()];
      vi.spyOn(experiencesService, 'getExperiences').mockResolvedValue(exps);
      await experiencesStore.loadExperiences();
      let state = getCurrentState();
      expect(state.experiences.length).toBeGreaterThan(0);
      experiencesStore.reset();
      state = getCurrentState();
      expect(state.experiences).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
