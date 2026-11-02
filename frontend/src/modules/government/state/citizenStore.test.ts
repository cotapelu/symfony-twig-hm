import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  citizenStore,
  activeCitizens,
  citizensByGender,
  citizensByWard,
  isLoading,
  isSaving,
  citizenError,
  selectedCitizen,
  citizenStatistics
} from './citizenStore';
import { citizenApi } from '../services/government.service';
import type { Citizen, CitizenFilters, CitizenStatistics, CreateCitizenRequest, UpdateCitizenRequest } from '../contracts/governmentTypes';

function getCurrentState() {
  let current: any;
  const unsub = citizenStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockCitizen = (overrides: Partial<Citizen> = {}): Citizen => ({
  id: `cit-${Math.random().toString(36).substr(2, 5)}`,
  name: 'John Doe',
  email: 'john@example.com',
  phone: '123-456-7890',
  address: '123 Main St',
  ward: 'Ward 1',
  gender: 'male' as const,
  isActive: true,
  createdAt: now,
  updatedAt: now,
  ...overrides
});

describe('CitizenStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    citizenStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.citizens).toEqual([]);
      expect(state.selectedCitizen).toBeNull();
      expect(state.statistics).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.saving).toBe(false);
      expect(state.error).toBeNull();
      expect(state.filters).toEqual({});
      expect(state.page).toBe(1);
      expect(state.perPage).toBe(20);
      expect(state.hasMore).toBe(true);
    });

    it('derived stores (empty)', () => {
      let act: Citizen[] = [], byGender: any = {}, byWard: any = {}, load = false, sav = false, err: string | null = null, sel: Citizen | null = null, stats: CitizenStatistics | null = null;
      const unsubA = activeCitizens.subscribe(s => { act = s; });
      const unsubG = citizensByGender.subscribe(s => { byGender = s; });
      const unsubW = citizensByWard.subscribe(s => { byWard = s; });
      const unsubL = isLoading.subscribe(s => { load = s; });
      const unsubS = isSaving.subscribe(s => { sav = s; });
      const unsubE = citizenError.subscribe(s => { err = s; });
      const unsubSel = selectedCitizen.subscribe(s => { sel = s; });
      const unsubStats = citizenStatistics.subscribe(s => { stats = s; });
      expect(act).toEqual([]);
      expect(byGender).toEqual({ male: [], female: [], other: [] });
      expect(byWard).toEqual({});
      expect(load).toBe(false);
      expect(sav).toBe(false);
      expect(err).toBeNull();
      expect(sel).toBeNull();
      expect(stats).toBeNull();
      unsubA(); unsubG(); unsubW(); unsubL(); unsubS(); unsubE(); unsubSel(); unsubStats();
    });
  });

  describe('loadCitizens', () => {
    it('loads first page', async () => {
      const citizens = [mockCitizen(), mockCitizen()];
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: citizens, total: 2, totalPages: 1 });
      await citizenStore.loadCitizens(); // default reset = false? Actually method signature: loadCitizens(reset = false). If page 1? It uses current state.page, but initially page=1. It will call list with page=1, perPage=20.
      const state = getCurrentState();
      expect(state.citizens).toEqual(citizens);
      expect(state.page).toBe(2);
      expect(state.hasMore).toBe(false);
      expect(state.loading).toBe(false);
    });

    it('loads more pages', async () => {
      const c1 = mockCitizen({ id: 'c1' });
      const c2 = mockCitizen({ id: 'c2' });
      vi.spyOn(citizenApi, 'list').mockResolvedValueOnce({ data: [c1], total: 2, totalPages: 2 })
        .mockResolvedValueOnce({ data: [c2], total: 2, totalPages: 2 });
      // First load (page 1)
      await citizenStore.loadCitizens();
      let state = getCurrentState();
      expect(state.citizens).toHaveLength(1);
      // Second load (page 2)
      await citizenStore.loadCitizens(); // page from state is now 2
      state = getCurrentState();
      expect(state.citizens).toHaveLength(2);
      expect(state.page).toBe(3);
      expect(state.hasMore).toBe(false);
    });

    it('handles error', async () => {
      vi.spyOn(citizenApi, 'list').mockRejectedValue(new Error('List failed'));
      await citizenStore.loadCitizens();
      const state = getCurrentState();
      expect(state.error).toBe('List failed');
      expect(state.loading).toBe(false);
    });
  });

  describe('searchCitizens', () => {
    it('calls list when query empty', async () => {
      const spy = vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [], total: 0, totalPages: 0 });
      await citizenStore.searchCitizens('');
      expect(spy).toHaveBeenCalledWith({}, 1, 20);
    });

    it('searches and replaces citizens', async () => {
      const results = [mockCitizen({ id: 'c1' }), mockCitizen({ id: 'c2' })];
      vi.spyOn(citizenApi, 'search').mockResolvedValue(results);
      await citizenStore.searchCitizens('keyword');
      const state = getCurrentState();
      expect(state.citizens).toEqual(results);
      expect(state.hasMore).toBe(false);
    });

    it('handles search error', async () => {
      vi.spyOn(citizenApi, 'search').mockRejectedValue(new Error('Search failed'));
      await citizenStore.searchCitizens('keyword');
      const state = getCurrentState();
      expect(state.error).toBe('Search failed');
    });
  });

  describe('getCitizen', () => {
    it('loads single citizen and sets selectedCitizen', async () => {
      const citizen = mockCitizen({ id: 'c1' });
      vi.spyOn(citizenApi, 'getById').mockResolvedValue(citizen);
      const result = await citizenStore.getCitizen('c1');
      expect(result).toEqual(citizen);
      const state = getCurrentState();
      expect(state.selectedCitizen).toEqual(citizen);
    });

    it('returns null on error', async () => {
      vi.spyOn(citizenApi, 'getById').mockRejectedValue(new Error('Not found'));
      const result = await citizenStore.getCitizen('c1');
      expect(result).toBeNull();
      const state = getCurrentState();
      expect(state.selectedCitizen).toBeNull();
      expect(state.error).toBe('Not found');
    });
  });

  describe('createCitizen', () => {
    it('creates and prepends to citizens', async () => {
      const newCit = mockCitizen({ id: 'new1', name: 'New Citizen' });
      vi.spyOn(citizenApi, 'create').mockResolvedValue(newCit);
      const result = await citizenStore.createCitizen({ name: 'New Citizen', email: 'new@example.com', phone: '', address: '', ward: '', gender: 'male' } as CreateCitizenRequest);
      expect(result).toEqual(newCit);
      const state = getCurrentState();
      expect(state.citizens[0]).toEqual(newCit);
      expect(state.saving).toBe(false);
    });

    it('propagates error', async () => {
      vi.spyOn(citizenApi, 'create').mockRejectedValue(new Error('Create failed'));
      try {
        await citizenStore.createCitizen({ name: 'New', email: 'new@example.com', phone: '', address: '', ward: '', gender: 'male' } as CreateCitizenRequest);
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Create failed');
      expect(state.saving).toBe(false);
    });
  });

  describe('updateCitizen', () => {
    it('updates citizen in list', async () => {
      const original = mockCitizen({ id: 'c1', name: 'Original' });
      const updated = mockCitizen({ id: 'c1', name: 'Updated' });
      // Prepopulate
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [original], total: 1, totalPages: 1 });
      await citizenStore.loadCitizens();
      // Update
      vi.spyOn(citizenApi, 'update').mockResolvedValue(updated);
      const result = await citizenStore.updateCitizen('c1', { name: 'Updated' } as UpdateCitizenRequest);
      expect(result).toEqual(updated);
      const state = getCurrentState();
      expect(state.citizens[0].name).toBe('Updated');
    });

    it('updates selectedCitizen if it matches', async () => {
      const original = mockCitizen({ id: 'c1', name: 'Original' });
      const updated = mockCitizen({ id: 'c1', name: 'Updated' });
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [original], total: 1, totalPages: 1 });
      await citizenStore.loadCitizens();
      citizenStore.selectCitizen(original);
      vi.spyOn(citizenApi, 'update').mockResolvedValue(updated);
      await citizenStore.updateCitizen('c1', { name: 'Updated' } as UpdateCitizenRequest);
      const state = getCurrentState();
      expect(state.selectedCitizen?.name).toBe('Updated');
    });

    it('handles error', async () => {
      vi.spyOn(citizenApi, 'update').mockRejectedValue(new Error('Update failed'));
      try {
        await citizenStore.updateCitizen('c1', {} as UpdateCitizenRequest);
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Update failed');
    });
  });

  describe('deactivateCitizen', () => {
    it('sets isActive false', async () => {
      const citizen = mockCitizen({ id: 'c1', isActive: true });
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [citizen], total: 1, totalPages: 1 });
      await citizenStore.loadCitizens();
      vi.spyOn(citizenApi, 'deactivate').mockResolvedValue(undefined);
      await citizenStore.deactivateCitizen('c1');
      const state = getCurrentState();
      expect(state.citizens[0].isActive).toBe(false);
    });

    it('handles error', async () => {
      const citizen = mockCitizen({ id: 'c1', isActive: true });
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [citizen], total: 1, totalPages: 1 });
      await citizenStore.loadCitizens();
      vi.spyOn(citizenApi, 'deactivate').mockRejectedValue(new Error('Deactivate failed'));
      try {
        await citizenStore.deactivateCitizen('c1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Deactivate failed');
    });
  });

  describe('activateCitizen', () => {
    it('sets isActive true', async () => {
      const citizen = mockCitizen({ id: 'c1', isActive: false });
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [citizen], total: 1, totalPages: 1 });
      await citizenStore.loadCitizens();
      vi.spyOn(citizenApi, 'activate').mockResolvedValue(undefined);
      await citizenStore.activateCitizen('c1');
      const state = getCurrentState();
      expect(state.citizens[0].isActive).toBe(true);
    });

    it('handles error', async () => {
      const citizen = mockCitizen({ id: 'c1', isActive: false });
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [citizen], total: 1, totalPages: 1 });
      await citizenStore.loadCitizens();
      vi.spyOn(citizenApi, 'activate').mockRejectedValue(new Error('Activate failed'));
      try {
        await citizenStore.activateCitizen('c1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Activate failed');
    });
  });

  describe('loadStatistics', () => {
    it('loads statistics', async () => {
      const stats: CitizenStatistics = { total: 100, byWard: { 'Ward 1': 50, 'Ward 2': 50 }, active: 80, inactive: 20 };
      vi.spyOn(citizenApi, 'getStatistics').mockResolvedValue(stats);
      const result = await citizenStore.loadStatistics();
      expect(result).toEqual(stats);
      const state = getCurrentState();
      expect(state.statistics).toEqual(stats);
    });

    it('returns null on error', async () => {
      vi.spyOn(citizenApi, 'getStatistics').mockRejectedValue(new Error('Stats failed'));
      const result = await citizenStore.loadStatistics();
      expect(result).toBeNull();
      const state = getCurrentState();
      expect(state.statistics).toBeNull();
    });
  });

  describe('filter methods', () => {
    it('setFilters updates filters and resets pagination, then loads', async () => {
      const spy = vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [], total: 0, totalPages: 0 });
      citizenStore.setFilters({ ward: 'Ward 1' });
      const state = getCurrentState();
      expect(state.filters).toEqual({ ward: 'Ward 1' });
      expect(state.page).toBe(1);
      // The setFilters calls loadCitizens(true) which triggers list with reset page=1.
      expect(spy).toHaveBeenCalledWith({ ward: 'Ward 1' }, 1, 20);
    });
  });

  describe('selectCitizen', () => {
    it('selects and clears selection', () => {
      const cit = mockCitizen();
      citizenStore.selectCitizen(cit);
      let state = getCurrentState();
      expect(state.selectedCitizen).toEqual(cit);
      citizenStore.selectCitizen(null);
      state = getCurrentState();
      expect(state.selectedCitizen).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(citizenApi, 'list').mockRejectedValue(new Error('err'));
      await citizenStore.loadCitizens();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      citizenStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [mockCitizen()], total: 1, totalPages: 1 });
      await citizenStore.loadCitizens();
      let state = getCurrentState();
      expect(state.citizens.length).toBeGreaterThan(0);
      citizenStore.reset();
      state = getCurrentState();
      expect(state.citizens).toEqual([]);
      expect(state.selectedCitizen).toBeNull();
      expect(state.statistics).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.saving).toBe(false);
      expect(state.error).toBeNull();
      expect(state.filters).toEqual({});
      expect(state.page).toBe(1);
      expect(state.hasMore).toBe(true);
    });
  });

  describe('derived stores logic', () => {
    it('activeCitizens filters isActive true', async () => {
      const active = mockCitizen({ id: 'c1', isActive: true });
      const inactive = mockCitizen({ id: 'c2', isActive: false });
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [active, inactive], total: 2, totalPages: 1 });
      await citizenStore.loadCitizens();
      let act: Citizen[] = [];
      const unsub = activeCitizens.subscribe(s => { act = s; });
      expect(act).toEqual([active]);
      unsub();
    });

    it('citizensByGender groups correctly', async () => {
      const male = mockCitizen({ id: 'c1', gender: 'male' });
      const female = mockCitizen({ id: 'c2', gender: 'female' });
      const other = mockCitizen({ id: 'c3', gender: 'other' });
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [male, female, other], total: 3, totalPages: 1 });
      await citizenStore.loadCitizens();
      let byGender: any = {};
      const unsub = citizensByGender.subscribe(s => { byGender = s; });
      expect(byGender).toEqual({
        male: [male],
        female: [female],
        other: [other]
      });
      unsub();
    });

    it('citizensByWard groups correctly', async () => {
      const c1 = mockCitizen({ id: 'c1', ward: 'Ward 1' });
      const c2 = mockCitizen({ id: 'c2', ward: 'Ward 2' });
      const c3 = mockCitizen({ id: 'c3', ward: 'Ward 1' });
      vi.spyOn(citizenApi, 'list').mockResolvedValue({ data: [c1, c2, c3], total: 3, totalPages: 1 });
      await citizenStore.loadCitizens();
      let byWard: any = {};
      const unsub = citizensByWard.subscribe(s => { byWard = s; });
      expect(byWard).toEqual({
        'Ward 1': [c1, c3],
        'Ward 2': [c2]
      });
      unsub();
    });
  });
});
