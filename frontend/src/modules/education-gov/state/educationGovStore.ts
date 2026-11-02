import { writable, derived } from 'svelte/store';
import type { EducationGovState } from '../contracts/educationGovTypes';
import { educationGovServiceModule } from '../services/educationGovService';

const initialState: EducationGovState = { services: [], loading: false, error: null };

function createEducationGovStore() {
  const { subscribe, set, update } = writable<EducationGovState>(initialState);
  return {
    subscribe,
    async loadServices() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const services = await educationGovServiceModule.getServices();
        update(s => ({ ...s, services, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const educationGovStore = createEducationGovStore();
export const educationGovServices = derived(educationGovStore, $s => $s.services);
export const educationGovLoading = derived(educationGovStore, $s => $s.loading);
