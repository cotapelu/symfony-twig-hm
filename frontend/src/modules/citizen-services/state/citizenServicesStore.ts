import { writable, derived } from 'svelte/store';
import type { CitizenService, CitizenServiceRequest, CitizenServicesState } from '../contracts/citizenServicesTypes';
import { citizenServicesService } from '../services/citizenServicesService';

const initialState: CitizenServicesState = { services: [], requests: [], loading: false, error: null };

function createCitizenServicesStore() {
  const { subscribe, set, update } = writable<CitizenServicesState>(initialState);
  return {
    subscribe,
    async loadServices() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const services = await citizenServicesService.getServices();
        update(s => ({ ...s, services, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    async loadRequests() {
      update(s => ({ ...s, loading: true, error: null }));
      try {
        const requests = await citizenServicesService.getRequests();
        update(s => ({ ...s, requests, loading: false }));
      } catch (error) { update(s => ({ ...s, error: (error as Error).message, loading: false })); }
    },
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const citizenServicesStore = createCitizenServicesStore();
export const citizenServiceList = derived(citizenServicesStore, $s => $s.services);
export const citizenServiceRequests = derived(citizenServicesStore, $s => $s.requests);
export const citizenServicesLoading = derived(citizenServicesStore, $s => $s.loading);
