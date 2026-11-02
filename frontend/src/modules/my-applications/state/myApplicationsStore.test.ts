import { describe, it, expect, vi, beforeEach } from 'vitest';
import { myApplicationsStore, myApplications, myApplicationsLoading } from './myApplicationsStore';
import { MyApplicationsService } from '../services/myApplicationsService';
import type { Application } from '../contracts/myApplicationsTypes';

function getCurrentState() {
  let current: any;
  const unsub = myApplicationsStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date();

const mockApplication = (overrides: Partial<Application> = {}): Application => ({
  id: `app-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Application Name',
  type: 'service',
  status: 'pending',
  submittedAt: now,
  ...overrides
});

describe('MyApplicationsStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    myApplicationsStore.reset();
  });

  it('initial state', () => {
    const state = getCurrentState();
    expect(state.applications).toEqual([]);
    expect(state.loading).toBe(false);
    expect(state.error).toBeNull();
  });

  it('derived stores', () => {
    let a: Application[] = [], l = false;
    const unsubA = myApplications.subscribe(s => { a = s; });
    const unsubL = myApplicationsLoading.subscribe(s => { l = s; });
    expect(a).toEqual([]);
    expect(l).toBe(false);
    unsubA(); unsubL();
  });

  describe('loadApplications', () => {
    it('loads applications', async () => {
      const apps = [mockApplication(), mockApplication()];
      vi.spyOn(MyApplicationsService.prototype, 'getApplications').mockResolvedValue(apps);
      await myApplicationsStore.loadApplications();
      const state = getCurrentState();
      expect(state.applications).toEqual(apps);
      expect(state.loading).toBe(false);
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(MyApplicationsService.prototype, 'getApplications').mockReturnValue(p as any);
      const loadPromise = myApplicationsStore.loadApplications();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockApplication()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates myApplications derived on success', async () => {
      const apps = [mockApplication()];
      vi.spyOn(MyApplicationsService.prototype, 'getApplications').mockResolvedValue(apps);
      await myApplicationsStore.loadApplications();
      let derivedApps: Application[] = [];
      const unsub = myApplications.subscribe(s => { derivedApps = s; });
      expect(derivedApps).toEqual(apps);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(MyApplicationsService.prototype, 'getApplications').mockResolvedValue([]);
      await myApplicationsStore.loadApplications();
      expect(getCurrentState().applications).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(MyApplicationsService.prototype, 'getApplications').mockRejectedValue(new Error('Failed to load'));
      await myApplicationsStore.loadApplications();
      const state = getCurrentState();
      expect(state.error).toBe('Failed to load');
      expect(state.loading).toBe(false);
      expect(state.applications).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(MyApplicationsService.prototype, 'getApplications').mockRejectedValue(new Error('err'));
      await myApplicationsStore.loadApplications();
      let state = getCurrentState();
      expect(state.error).toBe('err');
      myApplicationsStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets to initial state', async () => {
      vi.spyOn(MyApplicationsService.prototype, 'getApplications').mockResolvedValue([mockApplication()]);
      await myApplicationsStore.loadApplications();
      let state = getCurrentState();
      expect(state.applications.length).toBeGreaterThan(0);
      myApplicationsStore.reset();
      state = getCurrentState();
      expect(state.applications).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
