import { describe, it, expect, vi, beforeEach } from 'vitest';
import { reportsStore, reports, reportsLoading } from './reportsStore';
import { ReportsService } from '../services/reportsService';
import type { Report } from '../contracts/reportsTypes';

function getCurrentState() {
  let current: any;
  const unsub = reportsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockReport = (overrides: Partial<Report> = {}): Report => ({
  id: `rep-${Math.random().toString(36).substr(2, 5)}`,
  title: 'Report Title',
  description: 'Description',
  category: 'issue',
  status: 'pending',
  createdAt: now,
  updatedAt: now,
  ...overrides
});

describe('ReportsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    reportsStore.reset();
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.reports).toEqual([]);
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
  });

  it('derived stores', () => {
    let r: Report[] = [], l = false;
    const unsubR = reports.subscribe(s => { r = s; });
    const unsubL = reportsLoading.subscribe(s => { l = s; });
    expect(r).toEqual([]);
    expect(l).toBe(false);
    unsubR(); unsubL();
  });

  describe('loadReports', () => {
    it('loads reports', async () => {
      const reps = [mockReport(), mockReport()];
      vi.spyOn(ReportsService.prototype, 'getReports').mockResolvedValue(reps);
      await reportsStore.loadReports();
      const state = getCurrentState();
      expect(state.reports).toEqual(reps);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(ReportsService.prototype, 'getReports').mockReturnValue(p as any);
      const loadPromise = reportsStore.loadReports();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockReport()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates reports derived on success', async () => {
      const reps = [mockReport()];
      vi.spyOn(ReportsService.prototype, 'getReports').mockResolvedValue(reps);
      await reportsStore.loadReports();
      let r: Report[] = [];
      const unsub = reports.subscribe(s => { r = s; });
      expect(r).toEqual(reps);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(ReportsService.prototype, 'getReports').mockResolvedValue([]);
      await reportsStore.loadReports();
      expect(getCurrentState().reports).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(ReportsService.prototype, 'getReports').mockRejectedValue(new Error('Failed to load'));
      await reportsStore.loadReports();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load');
      expect(state.loading).toBe(false);
      expect(state.reports).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(ReportsService.prototype, 'getReports').mockRejectedValue(new Error('err'));
      await reportsStore.loadReports();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      reportsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(ReportsService.prototype, 'getReports').mockResolvedValue([mockReport()]);
      await reportsStore.loadReports();
      let state = getCurrentState();
      expect(state.reports.length).toBeGreaterThan(0);
      reportsStore.reset();
      state = getCurrentState();
      expect(state.reports).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
