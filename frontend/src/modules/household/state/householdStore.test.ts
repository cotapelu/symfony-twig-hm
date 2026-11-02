import { describe, it, expect, vi, beforeEach } from 'vitest';
import { householdStore, households, householdLoading } from './householdStore';
import { householdService } from '../services/householdService';
import type { Household } from '../contracts/householdTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = householdStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factory
const mockHousehold = (overrides: Partial<Household> = {}): Household => ({
  id: `hh-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test Household',
  address: '123 Main St',
  members: ['user1'],
  createdAt: new Date().toISOString(),
  ...overrides
});

describe('HouseholdStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    householdStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.households).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let hhs: Household[] = [], loading = false;
      const unsubH = households.subscribe(s => { hhs = s; });
      const unsubL = householdLoading.subscribe(s => { loading = s; });
      expect(hhs).toEqual([]);
      expect(loading).toBe(false);
      unsubH(); unsubL();
    });
  });

  describe('loadHouseholds', () => {
    it('loads households successfully', async () => {
      const hhs = [mockHousehold(), mockHousehold()];
      vi.spyOn(householdService, 'getHouseholds').mockResolvedValue(hhs);

      await householdStore.loadHouseholds();

      expect(householdService.getHouseholds).toHaveBeenCalledTimes(1);
      const state = getState();
      expect(state.households).toEqual(hhs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(householdService, 'getHouseholds').mockReturnValue(p as any);

      const loadPromise = householdStore.loadHouseholds();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockHousehold()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch households');
      vi.spyOn(householdService, 'getHouseholds').mockRejectedValue(error);

      await householdStore.loadHouseholds();

      const state = getState();
      expect(state.error).toBe('Failed to fetch households');
      expect(state.loading).toBe(false);
      expect(state.households).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(householdService, 'getHouseholds').mockRejectedValue(new Error('test error'));
      await householdStore.loadHouseholds().catch(() => {});

      expect(getState().error).toBe('test error');

      householdStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Load some households
      const hhs = [mockHousehold(), mockHousehold()];
      vi.spyOn(householdService, 'getHouseholds').mockResolvedValue(hhs);
      await householdStore.loadHouseholds();
      expect(getState().households).toHaveLength(2);

      // Reset
      householdStore.reset();

      const state = getState();
      expect(state.households).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
