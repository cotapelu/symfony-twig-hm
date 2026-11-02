// Citizen Store - Real API Integration with Svelte stores

import { writable, derived, get } from 'svelte/store';
import { citizenApi } from '../services/government.service';
import { GovernmentEventBus } from '../events/government.events';
import type { 
  Citizen, 
  CitizenFilters, 
  CitizenStatistics,
  CreateCitizenRequest,
  UpdateCitizenRequest 
} from '../contracts/government.types';

// ============================================
// State Stores
// ============================================
interface CitizenState {
  citizens: Citizen[];
  selectedCitizen: Citizen | null;
  statistics: CitizenStatistics | null;
  loading: boolean;
  saving: boolean;
  error: string | null;
  filters: CitizenFilters;
  page: number;
  perPage: number;
  hasMore: boolean;
}

const initialState: CitizenState = {
  citizens: [],
  selectedCitizen: null,
  statistics: null,
  loading: false,
  saving: false,
  error: null,
  filters: {},
  page: 1,
  perPage: 20,
  hasMore: true,
};

function createCitizenStore() {
  const { subscribe, set, update } = writable<CitizenState>(initialState);
  
  return {
    subscribe,
    
    // ============================================
    // Actions
    // ============================================
    
    async loadCitizens(reset = false) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const currentState = get({ subscribe });
        const page = reset ? 1 : currentState.page;
        
        const response = await citizenApi.list(currentState.filters, page, currentState.perPage);
        
        update(state => ({
          ...state,
          citizens: reset ? response.data : [...state.citizens, ...response.data],
          page: page + 1,
          hasMore: response.data.length === state.perPage,
          loading: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to load citizens',
        }));
      }
    },
    
    async searchCitizens(query: string) {
      if (!query.trim()) {
        await this.loadCitizens(true);
        return;
      }
      
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const results = await citizenApi.search(query);
        
        // Publish search event
        GovernmentEventBus.publish({
          type: 'government.citizen.search',
          payload: {
            query,
            resultsCount: results.length,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          citizens: results,
          loading: false,
          hasMore: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to search citizens',
        }));
      }
    },
    
    async getCitizen(id: string) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const citizen = await citizenApi.getById(id);
        
        update(state => ({
          ...state,
          selectedCitizen: citizen,
          loading: false,
        }));
        
        return citizen;
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to get citizen',
        }));
        return null;
      }
    },
    
    async createCitizen(data: CreateCitizenRequest) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const citizen = await citizenApi.create(data);
        
        // Publish event
        GovernmentEventBus.publish({
          type: 'government.citizen.created',
          payload: {
            citizen,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          citizens: [citizen, ...state.citizens],
          saving: false,
        }));
        
        return citizen;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to create citizen',
        }));
        throw err;
      }
    },
    
    async updateCitizen(id: string, data: UpdateCitizenRequest) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const citizen = await citizenApi.update(id, data);
        
        // Publish event
        GovernmentEventBus.publish({
          type: 'government.citizen.updated',
          payload: {
            citizen,
            changes: data,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          citizens: state.citizens.map(c => c.id === id ? citizen : c),
          selectedCitizen: state.selectedCitizen?.id === id ? citizen : state.selectedCitizen,
          saving: false,
        }));
        
        return citizen;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to update citizen',
        }));
        throw err;
      }
    },
    
    async deactivateCitizen(id: string) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        await citizenApi.deactivate(id);
        
        const citizen = get({ subscribe }).citizens.find(c => c.id === id);
        
        // Publish event
        GovernmentEventBus.publish({
          type: 'government.citizen.status_changed',
          payload: {
            citizenId: id,
            oldStatus: true,
            newStatus: false,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          citizens: state.citizens.map(c => c.id === id ? { ...c, isActive: false } : c),
          saving: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to deactivate citizen',
        }));
        throw err;
      }
    },
    
    async activateCitizen(id: string) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        await citizenApi.activate(id);
        
        // Publish event
        GovernmentEventBus.publish({
          type: 'government.citizen.status_changed',
          payload: {
            citizenId: id,
            oldStatus: false,
            newStatus: true,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          citizens: state.citizens.map(c => c.id === id ? { ...c, isActive: true } : c),
          saving: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to activate citizen',
        }));
        throw err;
      }
    },
    
    async loadStatistics(filters: Pick<CitizenFilters, 'province' | 'district'> = {}) {
      try {
        const stats = await citizenApi.getStatistics(filters);
        
        update(state => ({
          ...state,
          statistics: stats,
        }));
        
        return stats;
      } catch (err) {
        console.error('Failed to load statistics:', err);
        return null;
      }
    },
    
    setFilters(filters: CitizenFilters) {
      update(state => ({
        ...state,
        filters,
        page: 1,
        hasMore: true,
      }));
      this.loadCitizens(true);
    },
    
    selectCitizen(citizen: Citizen | null) {
      update(state => ({ ...state, selectedCitizen: citizen }));
    },
    
    clearError() {
      update(state => ({ ...state, error: null }));
    },
    
    reset() {
      set(initialState);
    },
  };
}

export const citizenStore = createCitizenStore();

// ============================================
// Derived Stores
// ============================================
export const activeCitizens = derived(
  citizenStore,
  $store => $store.citizens.filter(c => c.isActive)
);

export const citizensByGender = derived(
  citizenStore,
  $store => ({
    male: $store.citizens.filter(c => c.gender === 'male'),
    female: $store.citizens.filter(c => c.gender === 'female'),
    other: $store.citizens.filter(c => c.gender === 'other'),
  })
);

export const citizensByWard = derived(
  citizenStore,
  $store => {
    const grouped: Record<string, Citizen[]> = {};
    $store.citizens.forEach(c => {
      if (!grouped[c.ward]) grouped[c.ward] = [];
      grouped[c.ward].push(c);
    });
    return grouped;
  }
);

export const isLoading = derived(citizenStore, $store => $store.loading);
export const isSaving = derived(citizenStore, $store => $store.saving);
export const citizenError = derived(citizenStore, $store => $store.error);
export const selectedCitizen = derived(citizenStore, $store => $store.selectedCitizen);
export const citizenStatistics = derived(citizenStore, $store => $store.statistics);
