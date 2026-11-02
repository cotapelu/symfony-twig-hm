import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  emergencyStore,
  emergencyAlerts,
  emergencyLoading
} from './emergencyStore';
import { emergencyService } from '../services/emergencyService';
import type { EmergencyAlert } from '../contracts/emergencyTypes';

function getCurrentState() {
  let current: any;
  const unsub = emergencyStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockAlert = (overrides: Partial<EmergencyAlert> = {}): EmergencyAlert => ({
  id: `alert-${Math.random().toString(36).substr(2, 5)}`,
  type: 'weather',
  title: 'Storm Warning',
  description: 'Severe storm approaching',
  location: 'Downtown',
  severity: 'high' as const,
  createdAt: new Date().toISOString(),
  ...overrides
});

describe('EmergencyStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    emergencyStore.reset();
  });

  describe('initial state', () => {
    it('has empty alerts and defaults', () => {
      const state = getCurrentState();
      expect(state.alerts).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived emergencyAlerts matches state.alerts', () => {
      let alts: EmergencyAlert[] = [];
      const unsub = emergencyAlerts.subscribe(s => { alts = s; });
      expect(alts).toEqual([]);
      unsub();
    });

    it('derived emergencyLoading matches state.loading', () => {
      let load = false;
      const unsub = emergencyLoading.subscribe(s => { load = s; });
      expect(load).toBe(false);
      unsub();
    });
  });

  describe('loadAlerts', () => {
    it('loads alerts and updates state', async () => {
      const alts = [mockAlert(), mockAlert()];
      vi.spyOn(emergencyService, 'getAlerts').mockResolvedValue(alts);
      await emergencyStore.loadAlerts();
      const state = getCurrentState();
      expect(state.alerts).toEqual(alts);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(emergencyService, 'getAlerts').mockReturnValue(p as any);
      const loadPromise = emergencyStore.loadAlerts();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockAlert()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates emergencyAlerts derived on success', async () => {
      const alts = [mockAlert()];
      vi.spyOn(emergencyService, 'getAlerts').mockResolvedValue(alts);
      await emergencyStore.loadAlerts();
      let a: EmergencyAlert[] = [];
      const unsub = emergencyAlerts.subscribe(s => { a = s; });
      expect(a).toEqual(alts);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(emergencyService, 'getAlerts').mockResolvedValue([]);
      await emergencyStore.loadAlerts();
      expect(getCurrentState().alerts).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(emergencyService, 'getAlerts').mockRejectedValue(new Error('Service down'));
      await emergencyStore.loadAlerts();
      const state = getCurrentState();
      expect(state.error).toBe('Service down');
      expect(state.loading).toBe(false);
      expect(state.alerts).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(emergencyService, 'getAlerts').mockRejectedValue(new Error('test'));
      try { await emergencyStore.loadAlerts(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      emergencyStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const alts = [mockAlert()];
      vi.spyOn(emergencyService, 'getAlerts').mockResolvedValue(alts);
      await emergencyStore.loadAlerts();
      let state = getCurrentState();
      expect(state.alerts.length).toBeGreaterThan(0);
      emergencyStore.reset();
      state = getCurrentState();
      expect(state.alerts).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
