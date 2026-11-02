import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  volunteerStore,
  volunteerOpportunities,
  volunteerLoading,
  volunteerPagination
} from './volunteerStore';
import { volunteerService } from '../services/volunteerService';
import type { VolunteerOpportunity } from '../contracts/volunteerTypes';

function getCurrentState() {
  let current: any;
  const unsub = volunteerStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockOpportunity = (overrides: Partial<VolunteerOpportunity> = {}): VolunteerOpportunity => ({
  id: `opp-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Community Cleanup',
  description: 'Help clean the park',
  location: 'Central Park',
  date: new Date().toISOString(),
  slots: 10,
  applied: 0,
  category: 'environment',
  ...overrides
});

const mockResponse = (items: VolunteerOpportunity[], page: number, limit: number, total: number) => ({
  items,
  page,
  limit,
  total,
  totalPages: Math.ceil(total / limit)
});

describe('VolunteerStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    volunteerStore.reset();
  });

  describe('initial state', () => {
    it('has empty opportunities and default values', () => {
      const state = getCurrentState();
      expect(state.opportunities).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });

    it('derived volunteerOpportunities matches state.opportunities', () => {
      let opps: VolunteerOpportunity[] = [];
      const unsub = volunteerOpportunities.subscribe(s => { opps = s; });
      expect(opps).toEqual([]);
      unsub();
    });

    it('derived volunteerLoading matches state.loading', () => {
      let load = false;
      const unsub = volunteerLoading.subscribe(s => { load = s; });
      expect(load).toBe(false);
      unsub();
    });

    it('derived volunteerPagination matches state.pagination', () => {
      let pag: any = null;
      const unsub = volunteerPagination.subscribe(s => { pag = s; });
      expect(pag).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
      unsub();
    });
  });

  describe('loadOpportunities', () => {
    it('loads opportunities with default pagination', async () => {
      const items = [mockOpportunity(), mockOpportunity()];
      const resp = mockResponse(items, 1, 20, items.length);
      vi.spyOn(volunteerService, 'getOpportunities').mockResolvedValue(resp);
      await volunteerStore.loadOpportunities();
      const state = getCurrentState();
      expect(state.opportunities).toEqual(items);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: items.length, totalPages: 1 });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('loads with custom page and limit', async () => {
      const items = [mockOpportunity()];
      const resp = mockResponse(items, 2, 10, 50);
      vi.spyOn(volunteerService, 'getOpportunities').mockResolvedValue(resp);
      await volunteerStore.loadOpportunities(2, 10);
      const state = getCurrentState();
      expect(state.pagination.page).toBe(2);
      expect(state.pagination.limit).toBe(10);
      expect(state.pagination.total).toBe(50);
      expect(state.pagination.totalPages).toBe(5);
    });

    it('handles error', async () => {
      vi.spyOn(volunteerService, 'getOpportunities').mockRejectedValue(new Error('Service down'));
      await volunteerStore.loadOpportunities();
      const state = getCurrentState();
      expect(state.error).toBe('Service down');
      expect(state.opportunities).toEqual([]);
    });
  });

  describe('apply', () => {
    it('applies to opportunity and clears loading', async () => {
      vi.spyOn(volunteerService, 'apply').mockResolvedValue(undefined);
      const result = await volunteerStore.apply('opp-1');
      expect(result).toBeUndefined();
      let state = getCurrentState();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('propagates apply error', async () => {
      vi.spyOn(volunteerService, 'apply').mockRejectedValue(new Error('Already applied'));
      try {
        await volunteerStore.apply('opp-1');
      } catch (e: any) {
        expect(e.message).toBe('Already applied');
      }
      let state = getCurrentState();
      expect(state.error).toBe('Already applied');
      expect(state.loading).toBe(false);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(volunteerService, 'getOpportunities').mockRejectedValue(new Error('test'));
      await volunteerStore.loadOpportunities();
      let state = getCurrentState();
      expect(state.error).toBe('test');
      volunteerStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const items = [mockOpportunity()];
      const resp = mockResponse(items, 1, 20, items.length);
      vi.spyOn(volunteerService, 'getOpportunities').mockResolvedValue(resp);
      await volunteerStore.loadOpportunities();
      let state = getCurrentState();
      expect(state.opportunities.length).toBeGreaterThan(0);
      volunteerStore.reset();
      state = getCurrentState();
      expect(state.opportunities).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });
  });
});
