import { writable, derived } from 'svelte/store';
import type { EmergencyState } from '../contracts/emergencyTypes';
import { emergencyService } from '../services/emergencyService';

const initialState: EmergencyState = { alerts: [], loading: false, error: null };

function createEmergencyStore() {
  const { subscribe, set, update } = writable<EmergencyState>(initialState);
  return {
    subscribe,
    async loadAlerts() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const alerts = await emergencyService.getAlerts();
        update(s => ({ ...s, alerts, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const emergencyStore = createEmergencyStore();
export const emergencyAlerts = derived(emergencyStore, $s => $s.alerts);
export const emergencyLoading = derived(emergencyStore, $s => $s.loading);
