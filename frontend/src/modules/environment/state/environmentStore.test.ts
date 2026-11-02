import { describe, it, expect, vi, beforeEach } from 'vitest';
import { environmentStore, environmentReports, environmentLoading } from './environmentStore';
import { EnvironmentService } from '../services/environmentService';
import type { EnvironmentReport } from '../contracts/environmentTypes';

function getCurrentState() {
  let current: any;
  const unsub = environmentStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockReport = (overrides: Partial<EnvironmentReport> = {}): EnvironmentReport => ({
  id: `env-${Math.random().toString(36).substr(2, 5)}`,
  type: 'pollution',
  value: 50,
  unit: 'AQI',
  location: 'Hanoi',
  recordedAt: now,
  ...overrides
});

describe('EnvironmentStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    environmentStore.reset();
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.reports).toEqual([]);
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
  });

  it('derived stores', () => {
    let r: EnvironmentReport[] = [], l = false;
    const unsubR = environmentReports.subscribe(s => { r = s; });
    const unsubL = environmentLoading.subscribe(s => { l = s; });
    expect(r).toEqual([]);
    expect(l).toBe(false);
    unsubR(); unsubL();
  });

  describe('loadReports', () => {
    it('loads reports', async () => {
      const reports = [mockReport(), mockReport()];
      vi.spyOn(EnvironmentService.prototype, 'getReports').mockResolvedValue(reports);
      await environmentStore.loadReports();
      const state = getCurrentState();
      expect(state.reports).toEqual(reports);
      expect(state.loading).toBe(false);
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(EnvironmentService.prototype, 'getReports').mockReturnValue(p as any);
      const loadPromise = environmentStore.loadReports();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockReport()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates environmentReports derived on success', async () => {
      const reports = [mockReport()];
      vi.spyOn(EnvironmentService.prototype, 'getReports').mockResolvedValue(reports);
      await environmentStore.loadReports();
      let r: EnvironmentReport[] = [];
      const unsub = environmentReports.subscribe(s => { r = s; });
      expect(r).toEqual(reports);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(EnvironmentService.prototype, 'getReports').mockResolvedValue([]);
      await environmentStore.loadReports();
      expect(getCurrentState().reports).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(EnvironmentService.prototype, 'getReports').mockRejectedValue(new Error('Failed to load reports'));
      await environmentStore.loadReports();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load reports');
      expect(state.loading).toBe(false);
      expect(state.reports).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(EnvironmentService.prototype, 'getReports').mockRejectedValue(new Error('err'));
      await environmentStore.loadReports();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      environmentStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(EnvironmentService.prototype, 'getReports').mockResolvedValue([mockReport()]);
      await environmentStore.loadReports();
      let state = getCurrentState();
      expect(state.reports.length).toBeGreaterThan(0);
      environmentStore.reset();
      state = getCurrentState();
      expect(state.reports).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
