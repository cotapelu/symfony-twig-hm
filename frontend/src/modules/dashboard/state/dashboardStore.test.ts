import { describe, it, expect, vi, beforeEach } from 'vitest';
import { dashboardStore, stats, loading, error } from './dashboardStore';
import type { DashboardStats } from './dashboardStore';

function getState() {
  let current: any;
  const unsub = dashboardStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockStats = (overrides: Partial<DashboardStats> = {}): DashboardStats => ({
  totalMessages: 100,
  unreadMessages: 20,
  totalFriends: 50,
  onlineFriends: 10,
  totalGroups: 5,
  activeGroups: 3,
  recentActivity: [],
  ...overrides
});

describe('DashboardStore', () => {
  beforeEach(() => {
    // Reset store to initial state
    dashboardStore.set({ stats: null, loading: false, error: null });
  });

  describe('initial state', () => {
    it('has default values', () => {
      const s = getState();
      expect(s.stats).toBeNull();
      expect(s.loading).toBe(false);
      expect(s.error).toBeNull();
    });

    it('derived stores reflect initial state', () => {
      let statsVal: DashboardStats | null = null;
      let loadingVal = false;
      let errorVal: string | null = null;
      const unsubStats = stats.subscribe(v => statsVal = v);
      const unsubLoading = loading.subscribe(v => loadingVal = v);
      const unsubError = error.subscribe(v => errorVal = v);
      expect(statsVal).toBeNull();
      expect(loadingVal).toBe(false);
      expect(errorVal).toBeNull();
      unsubStats(); unsubLoading(); unsubError();
    });
  });

  describe('state mutations', () => {
    it('can set stats directly via set', () => {
      const newStats = mockStats();
      dashboardStore.set({ stats: newStats, loading: false, error: null });
      expect(getState().stats).toEqual(newStats);
    });

    it('can update loading flag via update', () => {
      dashboardStore.update(s => ({ ...s, loading: true }));
      expect(getState().loading).toBe(true);
      dashboardStore.update(s => ({ ...s, loading: false, error: null }));
      expect(getState().loading).toBe(false);
    });

    it('can set error', () => {
      dashboardStore.update(s => ({ ...s, error: 'Some error' }));
      expect(getState().error).toBe('Some error');
      expect(getState().loading).toBe(false);
    });
  });
});
