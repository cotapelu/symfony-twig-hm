import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { get } from 'svelte/store';
import { rewardsStore } from './rewards';

// Mock fetch globally
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Helper to get auth headers mock
function getMockHeaders() {
  return { Authorization: `Bearer test-token` };
}

describe('Rewards Store', () => {
  beforeEach(() => {
    mockFetch.mockClear();
    // Reset store to initial state
    rewardsStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.points).toBe(0);
      expect(state.pointsHistory).toEqual([]);
      expect(state.badges).toEqual([]);
      expect(state.leaderboard).toEqual([]);
      expect(state.canClaimDailyPoints).toBe(true);
      expect(state.lastClaimedAt).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('loadPoints', () => {
    it('loads points successfully', async () => {
      const mockResponse = { points: 100, history: [], canClaimDaily: true, lastClaimedAt: null };
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      await rewardsStore.loadPoints();

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.points).toBe(100);
      expect(state.canClaimDailyPoints).toBe(true);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles fetch error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
      });

      await rewardsStore.loadPoints();

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.error).toBe('Failed to load points');
      expect(state.loading).toBe(false);
    });

    it('sets loading true during fetch', async () => {
      // Create a promise that never resolves to observe loading state
      const pendingPromise = new Promise(() => {});
      mockFetch.mockReturnValueOnce(pendingPromise);

      rewardsStore.loadPoints();

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.loading).toBe(true);
    });
  });

  describe('loadBadges', () => {
    it('loads badges successfully', async () => {
      const mockBadges = [{ id: '1', name: 'Test Badge', earned: false }];
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ badges: mockBadges }),
      });

      await rewardsStore.loadBadges();

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.badges).toEqual(mockBadges);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error loading badges', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });

      await rewardsStore.loadBadges();

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.error).toBe('Failed to load badges');
    });
  });

  describe('loadLeaderboard', () => {
    it('loads leaderboard successfully', async () => {
      const mockLeaderboard = [{ rank: 1, userId: 'u1', userName: 'Alice', points: 500 }];
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ leaderboard: mockLeaderboard }),
      });

      await rewardsStore.loadLeaderboard();

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.leaderboard).toEqual(mockLeaderboard);
      expect(state.loading).toBe(false);
    });

    it('handles empty leaderboard', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ leaderboard: [] }),
      });

      await rewardsStore.loadLeaderboard();

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.leaderboard).toEqual([]);
    });
  });

  describe('claimDailyPoints', () => {
    it('claims daily points successfully', async () => {
      const mockData = { points: 10 };
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockData,
      });

      await rewardsStore.claimDailyPoints();

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.points).toBe(10);
      expect(state.canClaimDailyPoints).toBe(false);
      expect(state.lastClaimedAt).toBeInstanceOf(Date);
      expect(state.pointsHistory.length).toBe(1);
      expect(state.pointsHistory[0].type).toBe('earned');
      expect(state.pointsHistory[0].points).toBe(10);
    });

    it('throws error on failure', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });

      await expect(rewardsStore.claimDailyPoints()).rejects.toThrow('Failed to claim daily points');
    });

    it('adds points history entry with correct icon', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ points: 10 }),
      });

      await rewardsStore.claimDailyPoints();

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.pointsHistory[0].icon).toBe('🎁');
      expect(state.pointsHistory[0].description).toBe('Daily login bonus');
    });
  });

  describe('earnBadge', () => {
    it('earns badge and updates points', async () => {
      // Setup initial badges
      rewardsStore.reset();
      const initialBadges = [{ id: 'badge1', name: 'Test', earned: false }];
      // Directly set state
      rewardsStore.set({ ...get(rewardsStore), badges: initialBadges });

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ pointsAwarded: 50 }),
      });

      await rewardsStore.earnBadge('badge1');

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.badges[0].earned).toBe(true);
      expect(state.points).toBe(50);
    });

    it('handles error and logs to console', async () => {
      mockFetch.mockResolvedValueOnce({ ok: false });

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      try {
        await rewardsStore.earnBadge('badge1');
      } catch (e) {
        // Expected: method rethrows after logging
      }
      expect(consoleSpy).toHaveBeenCalledWith('Error earning badge:', expect.any(Error));
      consoleSpy.mockRestore();
    });
  });

  describe('addPoints', () => {
    it('adds positive points and updates history', async () => {
      rewardsStore.reset();
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });

      await rewardsStore.addPoints(50, 'Test description', '⭐');

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.points).toBe(50);
      expect(state.pointsHistory.length).toBe(1);
      expect(state.pointsHistory[0].points).toBe(50);
      expect(state.pointsHistory[0].type).toBe('earned');
      expect(state.pointsHistory[0].icon).toBe('⭐');
      expect(state.pointsHistory[0].description).toBe('Test description');
    });

    it('handles negative points (spent)', async () => {
      rewardsStore.reset();
      rewardsStore.set({ ...get(rewardsStore), points: 100 });
      mockFetch.mockResolvedValueOnce({ ok: true, json: async () => ({}) });

      await rewardsStore.addPoints(-20, 'Spent points', '💸');

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.points).toBe(80);
      expect(state.pointsHistory[0].type).toBe('spent');
      expect(state.pointsHistory[0].points).toBe(-20);
    });
  });

  describe('reset', () => {
    it('resets store to initial state', () => {
      rewardsStore.set({ ...get(rewardsStore), points: 100, loading: true, error: 'Some error' });
      rewardsStore.reset();

      let state: any;
      rewardsStore.subscribe((value) => { state = value; });
      expect(state.points).toBe(0);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
