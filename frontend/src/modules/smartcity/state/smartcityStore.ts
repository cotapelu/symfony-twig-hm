import { writable, derived } from 'svelte/store';
import type { SmartCityService, SmartCityAlert, SmartCityState } from '../contracts/smartcityTypes';
import { smartcityService } from '../services/smartcityService';

const initialState: SmartCityState = {
  services: [],
  alerts: [],
  selectedService: null,
  loading: false,
  error: null
};

function createSmartcityStore() {
  const { subscribe, set, update } = writable<SmartCityState>(initialState);

  return {
    subscribe,

    async loadServices() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const services = await smartcityService.getServices();
        update(s => ({ ...s, services, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async loadService(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const selectedService = await smartcityService.getService(id);
        update(s => ({ ...s, selectedService, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async updateService(id: string, request: { name?: string; description?: string; status?: 'operational' | 'maintenance' | 'offline' }) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const service = await smartcityService.updateService(id, request);
        update(s => ({
          ...s,
          services: s.services.map(s => s.id === id ? service : s),
          selectedService: s.selectedService?.id === id ? service : s.selectedService,
          loading: false
        }));
        return service;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    async loadAlerts() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const alerts = await smartcityService.getAlerts();
        update(s => ({ ...s, alerts, loading: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
      }
    },

    async acknowledgeAlert(id: string) {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const alert = await smartcityService.acknowledgeAlert(id);
        update(s => ({
          ...s,
          alerts: s.alerts.map(a => a.id === id ? alert : a),
          loading: false
        }));
        return alert;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false }));
        throw error;
      }
    },

    selectService(service: SmartCityService | null) {
      update(s => ({ ...s, selectedService: service }));
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      set(initialState);
    }
  };
}

export const smartcityStore = createSmartcityStore();

export const services = derived(smartcityStore, $s => $s.services);
export const alerts = derived(smartcityStore, $s => $s.alerts);
export const selectedService = derived(smartcityStore, $s => $s.selectedService);
export const smartcityLoading = derived(smartcityStore, $s => $s.loading);
export const smartcityError = derived(smartcityStore, $s => $s.error);
export const criticalAlerts = derived(smartcityStore, $s => $s.alerts.filter(a => a.severity === 'critical' && !a.acknowledged));
