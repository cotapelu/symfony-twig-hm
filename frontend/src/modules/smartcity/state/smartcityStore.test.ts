import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  smartcityStore,
  services,
  alerts,
  selectedService,
  smartcityLoading,
  smartcityError,
  criticalAlerts
} from './smartcityStore';
import { smartcityService } from '../services/smartcityService';
import type { SmartCityService, SmartCityAlert } from '../contracts/smartcityTypes';

function getCurrentState() {
  let current: any;
  const unsub = smartcityStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockService = (overrides: Partial<SmartCityService> = {}): SmartCityService => ({
  id: `serv-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Water Service',
  category: 'water',
  description: 'Manages water distribution',
  status: 'operational',
  data: { pressure: 'normal' },
  updatedAt: new Date().toISOString(),
  ...overrides
});

const mockAlert = (overrides: Partial<SmartCityAlert> = {}): SmartCityAlert => ({
  id: `alert-${Math.random().toString(36).substr(2, 5)}`,
  serviceId: 'serv-1',
  severity: 'info' as const,
  message: 'Routine check',
  acknowledged: false,
  createdAt: new Date().toISOString(),
  ...overrides
});

describe('SmartcityStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    smartcityStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and defaults', () => {
      const state = getCurrentState();
      expect(state.services).toEqual([]);
      expect(state.alerts).toEqual([]);
      expect(state.selectedService).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived services matches state.services', () => {
      let svcs: SmartCityService[] = [];
      const unsub = services.subscribe(s => { svcs = s; });
      expect(svcs).toEqual([]);
      unsub();
    });

    it('derived alerts matches state.alerts', () => {
      let alts: SmartCityAlert[] = [];
      const unsub = alerts.subscribe(s => { alts = s; });
      expect(alts).toEqual([]);
      unsub();
    });

    it('derived selectedService matches state.selectedService', () => {
      let sel: SmartCityService | null = null;
      const unsub = selectedService.subscribe(s => { sel = s; });
      expect(sel).toBeNull();
      unsub();
    });

    it('derived smartcityLoading and smartcityError', () => {
      let load = false, err: string | null = null;
      const unsubL = smartcityLoading.subscribe(s => { load = s; });
      const unsubE = smartcityError.subscribe(s => { err = s; });
      expect(load).toBe(false);
      expect(err).toBeNull();
      unsubL(); unsubE();
    });

    it('derived criticalAlerts filters correctly', () => {
      let crit: SmartCityAlert[] = [];
      const unsub = criticalAlerts.subscribe(s => { crit = s; });
      expect(crit).toEqual([]);
      unsub();
    });
  });

  describe('loadServices', () => {
    it('loads services and updates state', async () => {
      const svcs = [mockService(), mockService()];
      vi.spyOn(smartcityService, 'getServices').mockResolvedValue(svcs);
      await smartcityStore.loadServices();
      const state = getCurrentState();
      expect(state.services).toEqual(svcs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(smartcityService, 'getServices').mockReturnValue(p as any);
      const loadPromise = smartcityStore.loadServices();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockService()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates services derived on success', async () => {
      const svcs = [mockService()];
      vi.spyOn(smartcityService, 'getServices').mockResolvedValue(svcs);
      await smartcityStore.loadServices();
      let derivedServices: SmartCityService[] = [];
      const unsub = services.subscribe(s => { derivedServices = s; });
      expect(derivedServices).toEqual(svcs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(smartcityService, 'getServices').mockResolvedValue([]);
      await smartcityStore.loadServices();
      expect(getCurrentState().services).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(smartcityService, 'getServices').mockRejectedValue(new Error('Service down'));
      await smartcityStore.loadServices();
      const state = getCurrentState();
      expect(state.error).toBe('Service down');
      expect(state.loading).toBe(false);
      expect(state.services).toEqual([]);
    });
  });

  describe('loadService', () => {
    it('loads service and sets as selectedService', async () => {
      const svc = mockService({ id: 's1' });
      vi.spyOn(smartcityService, 'getService').mockResolvedValue(svc);
      await smartcityStore.loadService('s1');
      const state = getCurrentState();
      expect(state.selectedService).toEqual(svc);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(smartcityService, 'getService').mockReturnValue(p as any);
      const loadPromise = smartcityStore.loadService('s1');
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise(mockService({ id: 's1' }));
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates selectedService derived on success', async () => {
      const svc = mockService({ id: 's1' });
      vi.spyOn(smartcityService, 'getService').mockResolvedValue(svc);
      await smartcityStore.loadService('s1');
      let sel: SmartCityService | null = null;
      const unsub = selectedService.subscribe(s => { sel = s; });
      expect(sel).toEqual(svc);
      unsub();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(smartcityService, 'getService').mockRejectedValue(new Error('Not found'));
      try {
        await smartcityStore.loadService('missing');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
      expect(state.selectedService).toBeNull();
    });
  });

  describe('updateService', () => {
    it('updates service in list and selectedService', async () => {
      const original = mockService({ id: 's1', name: 'Old Name' });
      const updated = mockService({ id: 's1', name: 'New Name' });
      // First set services and selectedService
      vi.spyOn(smartcityService, 'getServices').mockResolvedValue([original]);
      await smartcityStore.loadServices();
      vi.spyOn(smartcityService, 'getService').mockResolvedValue(original);
      await smartcityStore.loadService('s1');
      // Mock update
      vi.spyOn(smartcityService, 'updateService').mockResolvedValue(updated);
      const result = await smartcityStore.updateService('s1', { name: 'New Name' });
      expect(result).toEqual(updated);
      let state = getCurrentState();
      expect(state.services.find(s => s.id === 's1')?.name).toBe('New Name');
      expect(state.selectedService?.name).toBe('New Name');
    });

    it('propagates errors', async () => {
      vi.spyOn(smartcityService, 'updateService').mockRejectedValue(new Error('Update failed'));
      try {
        await smartcityStore.updateService('s1', { name: 'X' });
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Update failed');
    });
  });

  describe('loadAlerts', () => {
    it('loads alerts and updates state', async () => {
      const alts = [mockAlert(), mockAlert()];
      vi.spyOn(smartcityService, 'getAlerts').mockResolvedValue(alts);
      await smartcityStore.loadAlerts();
      const state = getCurrentState();
      expect(state.alerts).toEqual(alts);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(smartcityService, 'getAlerts').mockReturnValue(p as any);
      const loadPromise = smartcityStore.loadAlerts();
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise([mockAlert()]);
      await loadPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates alerts derived on success', async () => {
      const alts = [mockAlert()];
      vi.spyOn(smartcityService, 'getAlerts').mockResolvedValue(alts);
      await smartcityStore.loadAlerts();
      let a: SmartCityAlert[] = [];
      const unsub = alerts.subscribe(s => { a = s; });
      expect(a).toEqual(alts);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(smartcityService, 'getAlerts').mockResolvedValue([]);
      await smartcityStore.loadAlerts();
      expect(getCurrentState().alerts).toEqual([]);
      expect(getCurrentState().loading).toBe(false);
      expect(getCurrentState().error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(smartcityService, 'getAlerts').mockRejectedValue(new Error('Fetch failed'));
      await smartcityStore.loadAlerts();
      const state = getCurrentState();
      expect(state.error).toBe('Fetch failed');
      expect(state.loading).toBe(false);
      expect(state.alerts).toEqual([]);
    });
  });

  describe('acknowledgeAlert', () => {
    it('acknowledges alert and updates alerts array', async () => {
      const original = mockAlert({ id: 'a1', acknowledged: false });
      const acked = mockAlert({ id: 'a1', acknowledged: true });
      vi.spyOn(smartcityService, 'getAlerts').mockResolvedValue([original]);
      await smartcityStore.loadAlerts();
      vi.spyOn(smartcityService, 'acknowledgeAlert').mockResolvedValue(acked);
      const result = await smartcityStore.acknowledgeAlert('a1');
      expect(result).toEqual(acked);
      let state = getCurrentState();
      expect(state.alerts.find(a => a.id === 'a1')?.acknowledged).toBe(true);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during request and false after', async () => {
      const original = mockAlert({ id: 'a1', acknowledged: false });
      vi.spyOn(smartcityService, 'getAlerts').mockResolvedValue([original]);
      await smartcityStore.loadAlerts();
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(smartcityService, 'acknowledgeAlert').mockReturnValue(p as any);
      const ackPromise = smartcityStore.acknowledgeAlert('a1');
      await new Promise(process.nextTick);
      expect(getCurrentState().loading).toBe(true);
      resolvePromise(mockAlert({ id: 'a1', acknowledged: true }));
      await ackPromise;
      expect(getCurrentState().loading).toBe(false);
    });

    it('updates alerts derived on success', async () => {
      const original = mockAlert({ id: 'a1', acknowledged: false });
      const acked = mockAlert({ id: 'a1', acknowledged: true });
      vi.spyOn(smartcityService, 'getAlerts').mockResolvedValue([original]);
      await smartcityStore.loadAlerts();
      vi.spyOn(smartcityService, 'acknowledgeAlert').mockResolvedValue(acked);
      await smartcityStore.acknowledgeAlert('a1');
      let a: SmartCityAlert[] = [];
      const unsub = alerts.subscribe(s => { a = s; });
      expect(a.find(x => x.id === 'a1')?.acknowledged).toBe(true);
      unsub();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(smartcityService, 'acknowledgeAlert').mockRejectedValue(new Error('Ack failed'));
      try {
        await smartcityStore.acknowledgeAlert('a1');
      } catch {}
      const state = getCurrentState();
      expect(state.error).toBe('Ack failed');
      expect(state.loading).toBe(false);
    });
  });

  describe('selectService', () => {
    it('sets selectedService', async () => {
      const svc = mockService();
      smartcityStore.selectService(svc);
      let state = getCurrentState();
      expect(state.selectedService).toEqual(svc);
      smartcityStore.selectService(null);
      state = getCurrentState();
      expect(state.selectedService).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(smartcityService, 'getServices').mockRejectedValue(new Error('test'));
      try { await smartcityStore.loadServices(); } catch {}
      let state = getCurrentState();
      expect(state.error).toBe('test');
      smartcityStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const svcs = [mockService()];
      vi.spyOn(smartcityService, 'getServices').mockResolvedValue(svcs);
      await smartcityStore.loadServices();
      vi.spyOn(smartcityService, 'getAlerts').mockResolvedValue([mockAlert()]);
      await smartcityStore.loadAlerts();
      let state = getCurrentState();
      expect(state.services.length).toBeGreaterThan(0);
      expect(state.alerts.length).toBeGreaterThan(0);
      smartcityStore.reset();
      state = getCurrentState();
      expect(state.services).toEqual([]);
      expect(state.alerts).toEqual([]);
      expect(state.selectedService).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });

  describe('criticalAlerts derived', () => {
    it('filters alerts with severity critical and not acknowledged', async () => {
      const a1 = mockAlert({ id: 'a1', severity: 'critical', acknowledged: false });
      const a2 = mockAlert({ id: 'a2', severity: 'critical', acknowledged: true });
      const a3 = mockAlert({ id: 'a3', severity: 'info', acknowledged: false });
      vi.spyOn(smartcityService, 'getAlerts').mockResolvedValue([a1, a2, a3]);
      await smartcityStore.loadAlerts();
      let crit: SmartCityAlert[] = [];
      const unsub = criticalAlerts.subscribe(s => { crit = s; });
      expect(crit).toContainEqual(a1);
      expect(crit).not.toContainEqual(a2);
      expect(crit).not.toContainEqual(a3);
      expect(crit.length).toBe(1);
      unsub();
    });
  });
});
