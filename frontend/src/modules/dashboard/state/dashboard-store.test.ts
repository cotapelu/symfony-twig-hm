import { vi, describe, it, expect, beforeEach } from 'vitest';

// Use dynamic imports to get a fresh store instance for each test
describe('DashboardStore', () => {
  let originalFetch: typeof global.fetch;

  beforeEach(() => {
    vi.resetModules();
    vi.clearAllMocks();
  });

  it('has correct initial state', async () => {
    const { dashboardStore } = await import('./dashboard-store');
    let state: any;
    dashboardStore.subscribe((s: any) => (state = s));
    expect(state).toEqual({ stats: null, loading: false, error: null });
  });

  describe('loadStats', () => {
    const mockStats = { messages: 5, groups: 2, files: 10, calls: 3 };

    it('successfully loads stats', async () => {
      const fetchMock = vi.fn().mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockStats),
      });
      vi.stubGlobal('fetch', fetchMock);

      const { dashboardStore } = await import('./dashboard-store');
      let state: any;
      dashboardStore.subscribe((s: any) => (state = s));

      await dashboardStore.loadStats();

      expect(fetchMock).toHaveBeenCalledWith('/api/dashboard/stats');
      expect(state).toEqual({ stats: mockStats, loading: false, error: null });
    });

    it('handles non-ok response', async () => {
      const fetchMock = vi.fn().mockResolvedValue({ ok: false });
      vi.stubGlobal('fetch', fetchMock);

      const { dashboardStore } = await import('./dashboard-store');
      let state: any;
      dashboardStore.subscribe((s: any) => (state = s));

      await expect(dashboardStore.loadStats()).rejects.toThrow(
        'Failed to load dashboard stats'
      );
      expect(state).toEqual({
        stats: null,
        loading: false,
        error: 'Failed to load dashboard stats',
      });
    });

    it('handles network error', async () => {
      const fetchMock = vi.fn().mockRejectedValue(new Error('Network down'));
      vi.stubGlobal('fetch', fetchMock);

      const { dashboardStore } = await import('./dashboard-store');
      let state: any;
      dashboardStore.subscribe((s: any) => (state = s));

      await expect(dashboardStore.loadStats()).rejects.toThrow('Network down');
      expect(state).toEqual({
        stats: null,
        loading: false,
        error: 'Network down',
      });
    });

    it('sets loading to true during fetch', async () => {
      let resolveFetch: (value: any) => void;
      const fetchPromise = new Promise((resolve) => {
        resolveFetch = resolve;
      });
      const fetchMock = vi.fn().mockReturnValue(fetchPromise);
      vi.stubGlobal('fetch', fetchMock);

      const { dashboardStore } = await import('./dashboard-store');
      let state: any;
      dashboardStore.subscribe((s: any) => (state = s));

      const loadPromise = dashboardStore.loadStats();

      // Wait for microtasks to process the update
      await new Promise((r) => setTimeout(r, 0));
      expect(state.loading).toBe(true);
      expect(state.error).toBeNull();

      // Resolve fetch
      resolveFetch({ ok: true, json: () => Promise.resolve(mockStats) });
      await loadPromise;

      expect(state.loading).toBe(false);
      expect(state.stats).toEqual(mockStats);
    });
  });

  describe('setError', () => {
    it('sets error state', async () => {
      const { dashboardStore } = await import('./dashboard-store');
      let state: any;
      dashboardStore.subscribe((s: any) => (state = s));
      expect(state.error).toBeNull();

      dashboardStore.setError('Some error');
      dashboardStore.subscribe((s: any) => (state = s));
      expect(state.error).toBe('Some error');
      expect(state.loading).toBe(false);
    });
  });

  describe('clearError', () => {
    it('clears error state', async () => {
      const { dashboardStore } = await import('./dashboard-store');
      dashboardStore.setError('Some error');
      let state: any;
      dashboardStore.subscribe((s: any) => (state = s));
      expect(state.error).toBe('Some error');

      dashboardStore.clearError();
      dashboardStore.subscribe((s: any) => (state = s));
      expect(state.error).toBeNull();
    });
  });
});
