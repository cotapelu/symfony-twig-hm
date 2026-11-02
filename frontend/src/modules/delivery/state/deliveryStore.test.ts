import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  deliveryStore,
  deliveries,
  currentDelivery,
  deliveryLoading,
  deliveryError,
  activeDeliveries
} from './deliveryStore';
import { deliveryService } from '../services/deliveryService';
import type { Delivery, CreateDeliveryRequest, UpdateDeliveryStatusRequest } from '../contracts/deliveryTypes';

function getCurrentState(store) {
  let current: any;
  const unsub = store.subscribe(s => { current = s; });
  unsub();
  return current;
}

const now = new Date().toISOString();

const mockDelivery = (overrides: Partial<Delivery> = {}): Delivery => ({
  id: `del-${Math.random().toString(36).substr(2, 5)}`,
  userId: 'user-1',
  orderId: `order-${Math.floor(Math.random() * 1000)}`,
  type: 'food' as const,
  status: 'pending' as const,
  pickupAddress: {
    street: '123 Pickup St',
    city: 'Pickup City',
    state: 'PC',
    zipCode: '12345',
    country: 'Testland'
  },
  deliveryAddress: {
    street: '456 Delivery Ave',
    city: 'Delivery City',
    state: 'DC',
    zipCode: '67890',
    country: 'Testland'
  },
  package: {
    description: 'Test package',
    weight: 1.5,
    dimensions: { length: 10, width: 5, height: 3 },
    value: 25.99
  },
  courier: {
    id: 'courier-1',
    name: 'John Courier',
    phone: '123-456-7890',
    vehicle: 'Motorcycle'
  },
  estimatedDelivery: now,
  actualDelivery: undefined,
  fee: 5.99,
  currency: 'USD',
  tips: 0,
  createdAt: now,
  updatedAt: now,
  ...overrides
});

const mockCreateRequest = (overrides: Partial<CreateDeliveryRequest> = {}): CreateDeliveryRequest => ({
  type: 'food',
  pickupAddress: {
    street: '123 Pickup St',
    city: 'Pickup City',
    state: 'PC',
    zipCode: '12345',
    country: 'Testland'
  },
  deliveryAddress: {
    street: '456 Delivery Ave',
    city: 'Delivery City',
    state: 'DC',
    zipCode: '67890',
    country: 'Testland'
  },
  package: {
    description: 'Food delivery',
    weight: 1.0,
    dimensions: { length: 8, width: 4, height: 2 },
    value: 15.99
  },
  orderId: 'order-123',
  ...overrides
});

const mockStatusUpdate = (overrides: Partial<UpdateDeliveryStatusRequest> = {}): UpdateDeliveryStatusRequest => ({
  status: 'assigned' as const,
  courierId: 'courier-1',
  ...overrides
});

describe('DeliveryStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    deliveryStore.reset();
  });

  describe('initial state', () => {
    it('has empty arrays and defaults', () => {
      const state = getCurrentState(deliveryStore);
      expect(state.deliveries).toEqual([]);
      expect(state.currentDelivery).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived deliveries matches state.deliveries', () => {
      expect(getCurrentState(deliveries)).toEqual([]);
    });

    it('derived currentDelivery matches state.currentDelivery', () => {
      expect(getCurrentState(currentDelivery)).toBeNull();
    });

    it('derived deliveryLoading matches state.loading', () => {
      expect(getCurrentState(deliveryLoading)).toBe(false);
    });

    it('derived deliveryError matches state.error', () => {
      expect(getCurrentState(deliveryError)).toBeNull();
    });

    it('derived activeDeliveries filters by status', async () => {
      const active = mockDelivery({ status: 'assigned' as const });
      const delivered = mockDelivery({ status: 'delivered' as const });
      vi.spyOn(deliveryService, 'getDeliveries').mockResolvedValue([active, delivered]);
      await deliveryStore.loadDeliveries();
      const activeList = getCurrentState(activeDeliveries);
      expect(activeList).toContainEqual(active);
      expect(activeList.length).toBe(1);
    });
  });

  describe('loadDeliveries', () => {
    it('loads deliveries and updates state', async () => {
      const delivs = [mockDelivery(), mockDelivery()];
      vi.spyOn(deliveryService, 'getDeliveries').mockResolvedValue(delivs);
      await deliveryStore.loadDeliveries();
      const state = getCurrentState(deliveryStore);
      expect(state.deliveries).toEqual(delivs);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(deliveryService, 'getDeliveries').mockReturnValue(p as any);
      const loadPromise = deliveryStore.loadDeliveries();
      await new Promise(process.nextTick);
      expect(getCurrentState(deliveryStore).loading).toBe(true);
      resolvePromise([mockDelivery()]);
      await loadPromise;
      expect(getCurrentState(deliveryStore).loading).toBe(false);
    });

    it('updates deliveries derived on success', async () => {
      const delivs = [mockDelivery()];
      vi.spyOn(deliveryService, 'getDeliveries').mockResolvedValue(delivs);
      await deliveryStore.loadDeliveries();
      let d: Delivery[] = [];
      const unsub = deliveries.subscribe(s => { d = s; });
      expect(d).toEqual(delivs);
      unsub();
    });

    it('handles empty response', async () => {
      vi.spyOn(deliveryService, 'getDeliveries').mockResolvedValue([]);
      await deliveryStore.loadDeliveries();
      expect(getCurrentState(deliveryStore).deliveries).toEqual([]);
      expect(getCurrentState(deliveryStore).loading).toBe(false);
      expect(getCurrentState(deliveryStore).error).toBeNull();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(deliveryService, 'getDeliveries').mockRejectedValue(new Error('Network error'));
      await deliveryStore.loadDeliveries();
      const state = getCurrentState(deliveryStore);
      expect(state.error).toBe('Network error');
      expect(state.loading).toBe(false);
      expect(state.deliveries).toEqual([]);
    });
  });

  describe('loadDelivery', () => {
    it('loads delivery and sets as currentDelivery', async () => {
      const del = mockDelivery({ id: 'del-x' });
      vi.spyOn(deliveryService, 'getDelivery').mockResolvedValue(del);
      const result = await deliveryStore.loadDelivery('del-x');
      expect(result).toEqual(del);
      const state = getCurrentState(deliveryStore);
      expect(state.currentDelivery).toEqual(del);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch and false after', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(deliveryService, 'getDelivery').mockReturnValue(p as any);
      const loadPromise = deliveryStore.loadDelivery('del-x');
      await new Promise(process.nextTick);
      expect(getCurrentState(deliveryStore).loading).toBe(true);
      resolvePromise(mockDelivery({ id: 'del-x' }));
      await loadPromise;
      expect(getCurrentState(deliveryStore).loading).toBe(false);
    });

    it('updates currentDelivery derived on success', async () => {
      const del = mockDelivery({ id: 'del-x' });
      vi.spyOn(deliveryService, 'getDelivery').mockResolvedValue(del);
      await deliveryStore.loadDelivery('del-x');
      let cd: Delivery | null = null;
      const unsub = currentDelivery.subscribe(s => { cd = s; });
      expect(cd).toEqual(del);
      unsub();
    });

    it('handles error and resets loading', async () => {
      vi.spyOn(deliveryService, 'getDelivery').mockRejectedValue(new Error('Not found'));
      try {
        await deliveryStore.loadDelivery('missing');
      } catch {}
      const state = getCurrentState(deliveryStore);
      expect(state.error).toBe('Not found');
      expect(state.loading).toBe(false);
      expect(state.currentDelivery).toBeNull();
    });
  });

  describe('createDelivery', () => {
    it('creates delivery and adds to list', async () => {
      const del = mockDelivery();
      vi.spyOn(deliveryService, 'createDelivery').mockResolvedValue(del);
      const request = mockCreateRequest();
      const result = await deliveryStore.createDelivery(request);
      expect(result).toEqual(del);
      let state = getCurrentState(deliveryStore);
      expect(state.deliveries[0]).toEqual(del);
    });

    it('propagates errors', async () => {
      vi.spyOn(deliveryService, 'createDelivery').mockRejectedValue(new Error('Validation failed'));
      try {
        await deliveryStore.createDelivery(mockCreateRequest());
      } catch {}
      const state = getCurrentState(deliveryStore);
      expect(state.error).toBe('Validation failed');
    });
  });

  describe('updateDeliveryStatus', () => {
    it('updates delivery in list and currentDelivery', async () => {
      const original = mockDelivery({ id: 'd1', status: 'pending' });
      const updated = mockDelivery({ id: 'd1', status: 'assigned' });
      // First load delivery into store via loadDeliveries
      vi.spyOn(deliveryService, 'getDeliveries').mockResolvedValue([original]);
      await deliveryStore.loadDeliveries();
      // Set currentDelivery via loadDelivery (must await)
      vi.spyOn(deliveryService, 'getDelivery').mockResolvedValue(original);
      await deliveryStore.loadDelivery('d1');
      let state = getCurrentState(deliveryStore);
      expect(state.currentDelivery?.id).toBe('d1');
      // Mock update
      vi.spyOn(deliveryService, 'updateDeliveryStatus').mockResolvedValue(updated);
      await deliveryStore.updateDeliveryStatus('d1', { status: 'assigned' });
      state = getCurrentState(deliveryStore);
      expect(state.deliveries.find(d => d.id === 'd1')?.status).toBe('assigned');
      expect(state.currentDelivery?.status).toBe('assigned');
    });

    it('handles error', async () => {
      vi.spyOn(deliveryService, 'updateDeliveryStatus').mockRejectedValue(new Error('Update failed'));
      try {
        await deliveryStore.updateDeliveryStatus('d1', { status: 'assigned' });
      } catch {}
      const state = getCurrentState(deliveryStore);
      expect(state.error).toBe('Update failed');
    });
  });

  describe('cancelDelivery', () => {
    it('cancels delivery and updates list and currentDelivery', async () => {
      const original = mockDelivery({ id: 'd1', status: 'assigned' });
      const cancelled = mockDelivery({ id: 'd1', status: 'cancelled' as const });
      // Add delivery first
      vi.spyOn(deliveryService, 'getDeliveries').mockResolvedValue([original]);
      await deliveryStore.loadDeliveries();
      let state = getCurrentState(deliveryStore);
      expect(state.deliveries.some(d => d.id === 'd1')).toBe(true);
      // Mock cancel
      vi.spyOn(deliveryService, 'cancelDelivery').mockResolvedValue(cancelled);
      await deliveryStore.cancelDelivery('d1');
      state = getCurrentState(deliveryStore);
      expect(state.deliveries.find(d => d.id === 'd1')?.status).toBe('cancelled');
    });

    it('handles error', async () => {
      vi.spyOn(deliveryService, 'cancelDelivery').mockRejectedValue(new Error('Cancel failed'));
      try {
        await deliveryStore.cancelDelivery('d1');
      } catch {}
      const state = getCurrentState(deliveryStore);
      expect(state.error).toBe('Cancel failed');
    });
  });

  describe('loadActiveDeliveries', () => {
    it('loads active deliveries only', async () => {
      const active = mockDelivery({ status: 'assigned' as const });
      const delivered = mockDelivery({ status: 'delivered' as const });
      vi.spyOn(deliveryService, 'getActiveDeliveries').mockResolvedValue([active, delivered]);
      await deliveryStore.loadActiveDeliveries();
      const state = getCurrentState(deliveryStore);
      expect(state.deliveries).toEqual([active, delivered]); // replaces list
      const activeList = getCurrentState(activeDeliveries);
      expect(activeList).toContainEqual(active);
      expect(activeList.length).toBe(1);
    });

    it('handles error', async () => {
      vi.spyOn(deliveryService, 'getActiveDeliveries').mockRejectedValue(new Error('Fail'));
      await deliveryStore.loadActiveDeliveries();
      const state = getCurrentState(deliveryStore);
      expect(state.error).toBe('Fail');
    });
  });

  describe('clearCurrentDelivery', () => {
    it('clears currentDelivery', async () => {
      const del = mockDelivery();
      vi.spyOn(deliveryService, 'getDelivery').mockResolvedValue(del);
      await deliveryStore.loadDelivery('del-1');
      let state = getCurrentState(deliveryStore);
      expect(state.currentDelivery).toEqual(del);
      deliveryStore.clearCurrentDelivery();
      state = getCurrentState(deliveryStore);
      expect(state.currentDelivery).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(deliveryService, 'getDeliveries').mockRejectedValue(new Error('test'));
      try { await deliveryStore.loadDeliveries(); } catch {}
      let state = getCurrentState(deliveryStore);
      expect(state.error).toBe('test');
      deliveryStore.clearError();
      state = getCurrentState(deliveryStore);
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const delivs = [mockDelivery()];
      vi.spyOn(deliveryService, 'getDeliveries').mockResolvedValue(delivs);
      await deliveryStore.loadDeliveries();
      let state = getCurrentState(deliveryStore);
      expect(state.deliveries.length).toBeGreaterThan(0);
      deliveryStore.reset();
      state = getCurrentState(deliveryStore);
      expect(state.deliveries).toEqual([]);
      expect(state.currentDelivery).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
