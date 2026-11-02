import { writable, derived, get } from 'svelte/store';
import type { CivilService, CivilRequest, CivilState } from '../contracts/civilTypes';
import { civilServiceModule } from '../services/civilService';

interface ExtendedCivilState extends CivilState {
  operationInProgress: boolean;
}

const initialState: ExtendedCivilState = { 
  services: [], 
  requests: [], 
  loading: false, 
  error: null, 
  currentUserId: null,
  operationInProgress: false
};

function createCivilStore() {
  const { subscribe, set, update } = writable<ExtendedCivilState>(initialState);

  const checkOperation = (): boolean => {
    const state = get({ subscribe });
    if (state.operationInProgress) {
      update(s => ({ ...s, error: 'Another operation is in progress' }));
      return true;
    }
    return false;
  };

  return {
    subscribe,
    
    setCurrentUser(userId: string) {
      update(s => ({ ...s, currentUserId: userId }));
    },
    
    async loadServices() {
      if (checkOperation()) return;
      update(s => ({ ...s, loading: true, error: null, operationInProgress: true }));
      try {
        const services = await civilServiceModule.getServices();
        update(s => ({ ...s, services, loading: false, operationInProgress: false }));
      } catch (error) { 
        update(s => ({ ...s, error: (error as Error).message, loading: false, operationInProgress: false })); 
      }
    },
    
    async loadRequests() {
      if (checkOperation()) return;
      update(s => ({ ...s, loading: true, error: null, operationInProgress: true }));
      try {
        const requests = await civilServiceModule.getMyRequests();
        update(s => ({ ...s, requests, loading: false, operationInProgress: false }));
      } catch (error) { 
        update(s => ({ ...s, error: (error as Error).message, loading: false, operationInProgress: false })); 
      }
    },
    
    async loadRequest(id: string) {
      if (checkOperation()) return null;
      update(s => ({ ...s, loading: true, error: null, operationInProgress: true }));
      try {
        const state = get({ subscribe });
        const request = await civilServiceModule.getMyRequest(id);
        
        if (state.currentUserId && request.userId !== state.currentUserId) {
          throw new Error('Unauthorized: You can only access your own requests');
        }
        
        update(s => ({ 
          ...s, 
          requests: s.requests.some(r => r.id === id) 
            ? s.requests.map(r => r.id === id ? request : r) 
            : [...s.requests, request],
          loading: false,
          operationInProgress: false
        }));
        return request;
      } catch (error) { 
        update(s => ({ ...s, error: (error as Error).message, loading: false, operationInProgress: false })); 
        throw error;
      }
    },
    
    async submitRequest(serviceId: string, documents: string[]) {
      if (checkOperation()) return null;
      update(s => ({ ...s, loading: true, error: null, operationInProgress: true }));
      try {
        const request = await civilServiceModule.submitRequest(serviceId, documents);
        update(s => ({ ...s, requests: [...s.requests, request], loading: false, operationInProgress: false }));
        return request;
      } catch (error) { 
        update(s => ({ ...s, error: (error as Error).message, loading: false, operationInProgress: false })); 
        throw error;
      }
    },
    
    async cancelRequest(id: string) {
      if (checkOperation()) return;
      update(s => ({ ...s, loading: true, error: null, operationInProgress: true }));
      try {
        const request = await civilServiceModule.cancelRequest(id);
        update(s => ({ 
          ...s, 
          requests: s.requests.map(r => r.id === id ? request : r),
          loading: false,
          operationInProgress: false
        }));
        return request;
      } catch (error) { 
        update(s => ({ ...s, error: (error as Error).message, loading: false, operationInProgress: false })); 
        throw error;
      }
    },
    
    clearError() { update(s => ({ ...s, error: null })); },
    reset() { set(initialState); }
  };
}

export const civilStore = createCivilStore();
export const civilServices = derived(civilStore, $s => $s.services);
export const civilRequests = derived(civilStore, $s => $s.requests);
export const civilLoading = derived(civilStore, $s => $s.loading);
