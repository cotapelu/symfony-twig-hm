import { writable, derived } from 'svelte/store';
import type { Delivery, DeliveryState, CreateDeliveryRequest, UpdateDeliveryStatusRequest } from '../contracts/deliveryTypes';
import { deliveryService } from '../services/deliveryService';

const initialState: DeliveryState = {
  deliveries: [],
  currentDelivery: null,
  loading: false,
  error: null
};

function createDeliveryStore() {
  const { subscribe, set, update } = writable<DeliveryState>(initialState);

  return {
    subscribe,

    async loadDeliveries() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const deliveries = await deliveryService.getDeliveries();
        update(s => ({ ...s, deliveries, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadDelivery(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const delivery = await deliveryService.getDelivery(id);
        update(s => ({ ...s, currentDelivery: delivery, loading: false }));
        return delivery;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async createDelivery(request: CreateDeliveryRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const delivery = await deliveryService.createDelivery(request);
        update(s => ({ ...s, deliveries: [...s.deliveries, delivery], loading: false }));
        return delivery;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async updateDeliveryStatus(id: string, request: UpdateDeliveryStatusRequest) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const delivery = await deliveryService.updateDeliveryStatus(id, request);
        update(s => ({
          ...s,
          deliveries: s.deliveries.map(d => d.id === id ? delivery : d),
          currentDelivery: s.currentDelivery?.id === id ? delivery : s.currentDelivery,
          loading: false
        }));
        return delivery;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async cancelDelivery(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const delivery = await deliveryService.cancelDelivery(id);
        update(s => ({
          ...s,
          deliveries: s.deliveries.map(d => d.id === id ? delivery : d),
          loading: false
        }));
        return delivery;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadActiveDeliveries() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const deliveries = await deliveryService.getActiveDeliveries();
        update(s => ({ ...s, deliveries, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    clearCurrentDelivery() {
      update(s => ({ ...s, currentDelivery: null }));
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const deliveryStore = createDeliveryStore();

export const deliveries = derived(deliveryStore, $s => $s.deliveries);
export const currentDelivery = derived(deliveryStore, $s => $s.currentDelivery);
export const deliveryLoading = derived(deliveryStore, $s => $s.loading);
export const deliveryError = derived(deliveryStore, $s => $s.error);

export const activeDeliveries = derived(deliveryStore, $s =>
  $s.deliveries.filter(d => ['assigned', 'picked_up', 'in_transit'].includes(d.status))
);
