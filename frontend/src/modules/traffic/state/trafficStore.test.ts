import { describe, it, expect, vi, beforeEach } from 'vitest';
import { trafficStore, trafficAlerts, trafficLoading, trafficPagination } from './trafficStore';
import { TrafficService } from '../services/trafficService';
import type { TrafficAlert } from '../contracts/trafficTypes';

function getCurrentState() {
  let current: any;
  const unsub = trafficStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockAlert = (overrides: Partial<TrafficAlert> = {}): TrafficAlert => ({
  id: `alert-${Math.random().toString(36).substr(2, 5)}`,
  type: 'congestion',
  location: 'Hanoi',
  severity: 'high',
  description: 'Traffic jam',
  startTime: now,
  endTime: new Date(now.getTime() + 3600000),
  ...overrides
});

const mockResponse = (page: number, totalPages: number, items: TrafficAlert[]) => ({
  items,
  page,
  limit: 20,
  total: totalPages * items.length, // approximate total count
  totalPages
});

describe('TrafficStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    trafficStore.reset();
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.alerts).toEqual([]);
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
    expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
  });

  it('derived stores', () => {
    let a: TrafficAlert[] = [], l = false, p: any = null;
    const unsubA = trafficAlerts.subscribe(s => { a = s; });
    const unsubL = trafficLoading.subscribe(s => { l = s; });
    const unsubP = trafficPagination.subscribe(s => { p = s; });
    expect(a).toEqual([]);
    expect(l).toBe(false);
    expect(p).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    unsubA(); unsubL(); unsubP();
  });

  describe('loadAlerts', () => {
    it('loads alerts and sets pagination', async () => {
      const alerts = [mockAlert(), mockAlert()];
      const resp = mockResponse(1, 2, alerts);
      vi.spyOn(TrafficService.prototype, 'getAlerts').mockResolvedValue(resp);
      await trafficStore.loadAlerts();
      const state = getCurrentState();
      expect(state.alerts).toEqual(alerts);
      expect(state.pagination).toEqual({
        page: resp.page,
        limit: resp.limit,
        total: resp.total,
        totalPages: resp.totalPages
      });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      vi.spyOn(TrafficService.prototype, 'getAlerts').mockRejectedValue(new Error('Failed to load alerts'));
      await trafficStore.loadAlerts();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load alerts');
      expect(state.alerts).toEqual([]);
      expect(state.loading).toBe(false);
    });
  });

  describe('loadMore', () => {
    it('loads next page when available', async () => {
      // First page
      const page1 = mockResponse(1, 2, [mockAlert({ id: 'a1' })]);
      vi.spyOn(TrafficService.prototype, 'getAlerts').mockResolvedValueOnce(page1);
      await trafficStore.loadAlerts();
      let state = getCurrentState();
      expect(state.pagination.page).toBe(1);
      expect(state.pagination.totalPages).toBe(2);

      // Second page
      const page2 = mockResponse(2, 2, [mockAlert({ id: 'a2' })]);
      vi.spyOn(TrafficService.prototype, 'getAlerts').mockResolvedValueOnce(page2);
      await trafficStore.loadMore();
      state = getCurrentState();
      expect(state.alerts).toHaveLength(1); // alerts replaced by page2's items (single)
      expect(state.pagination.page).toBe(2);
      expect(state.pagination.totalPages).toBe(2);
    });

    it('does not load more if last page reached', async () => {
      const page1 = mockResponse(1, 1, [mockAlert()]); // totalPages 1
      const spy = vi.spyOn(TrafficService.prototype, 'getAlerts').mockResolvedValueOnce(page1);
      await trafficStore.loadAlerts();
      const state = getCurrentState();
      expect(state.pagination.totalPages).toBe(1);
      expect(spy).toHaveBeenCalledTimes(1);

      await trafficStore.loadMore();
      expect(spy).toHaveBeenCalledTimes(1);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(TrafficService.prototype, 'getAlerts').mockRejectedValue(new Error('err'));
      await trafficStore.loadAlerts();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      trafficStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(TrafficService.prototype, 'getAlerts').mockResolvedValue(mockResponse(1, 5, [mockAlert()]));
      await trafficStore.loadAlerts();
      let state = getCurrentState();
      expect(state.alerts.length).toBeGreaterThan(0);
      trafficStore.reset();
      state = getCurrentState();
      expect(state.alerts).toEqual([]);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
