// sample-module Store - Real API Integration with Svelte stores
// Replace sample-module with the actual module name during generation

import { writable, derived, get } from 'svelte/store';
import { sampleModuleApi } from '../services/sample-module.service';
import { SampleModuleEventBus } from '../events/sample-module.events';
import type { SampleModuleSearchedEvent } from '../events/sample-module.events';
import type { 
  SampleModuleItem, 
  SampleModuleFilters, 
  SampleModuleStatistics,
  CreateSampleModuleRequest,
  UpdateSampleModuleRequest 
} from '../contracts/sample-module.types';

// ============================================
// State Stores
// ============================================
interface SampleModuleState {
  items: SampleModuleItem[];
  selectedItem: SampleModuleItem | null;
  statistics: SampleModuleStatistics | null;
  loading: boolean;
  saving: boolean;
  error: string | null;
  filters: SampleModuleFilters;
  page: number;
  perPage: number;
  hasMore: boolean;
}

const initialState: SampleModuleState = {
  items: [],
  selectedItem: null,
  statistics: null,
  loading: false,
  saving: false,
  error: null,
  filters: {},
  page: 1,
  perPage: 20,
  hasMore: true,
};

function createSampleModuleStore() {
  const { subscribe, set, update } = writable<SampleModuleState>(initialState);
  
  return {
    subscribe,
    
    // ============================================
    // Actions
    // ============================================
    
    async loadItems(reset = false) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const currentState = get({ subscribe });
        const page = reset ? 1 : currentState.page;
        
        const response = await sampleModuleApi.list(currentState.filters, page, currentState.perPage);
        
        update(state => ({
          ...state,
          items: reset ? response.data : [...state.items, ...response.data],
          page: page + 1,
          hasMore: response.data.length === state.perPage,
          loading: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to load SampleModule items',
        }));
      }
    },
    
    async searchItems(query: string) {
      if (!query.trim()) {
        await this.loadItems(true);
        return;
      }
      
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const results = await sampleModuleApi.search(query);
        
        // Publish search event
        SampleModuleEventBus.publish({
          type: 'sample-module.item.search',
          payload: {
            query,
            resultsCount: results.length,
            timestamp: new Date().toISOString(),
          } satisfies SampleModuleSearchedEvent['payload'],
        });
        
        update(state => ({
          ...state,
          items: results,
          loading: false,
          hasMore: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to search SampleModule items',
        }));
      }
    },
    
    async getItem(id: string) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const item = await sampleModuleApi.getById(id);
        
        update(state => ({
          ...state,
          selectedItem: item,
          loading: false,
        }));
        
        return item;
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to get SampleModule item',
        }));
        return null;
      }
    },
    
    async createItem(data: CreateSampleModuleRequest) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const item = await sampleModuleApi.create(data);
        
        // Publish event
        SampleModuleEventBus.publish({
          type: 'sample-module.item.created',
          payload: {
            item,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          items: [item, ...state.items],
          saving: false,
        }));
        
        return item;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to create SampleModule item',
        }));
        throw err;
      }
    },
    
    async updateItem(id: string, data: UpdateSampleModuleRequest) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const item = await sampleModuleApi.update(id, data);
        
        // Publish event
        SampleModuleEventBus.publish({
          type: 'sample-module.item.updated',
          payload: {
            item,
            changes: data,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          items: state.items.map(i => i.id === id ? item : i),
          selectedItem: state.selectedItem?.id === id ? item : state.selectedItem,
          saving: false,
        }));
        
        return item;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to update SampleModule item',
        }));
        throw err;
      }
    },
    
    async deleteItem(id: string) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        await sampleModuleApi.delete(id);
        
        // Publish event
        SampleModuleEventBus.publish({
          type: 'sample-module.item.deleted',
          payload: {
            id,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          items: state.items.filter(i => i.id !== id),
          selectedItem: state.selectedItem?.id === id ? null : state.selectedItem,
          saving: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to delete SampleModule item',
        }));
        throw err;
      }
    },
    
    async loadStatistics() {
      try {
        const stats = await sampleModuleApi.getStatistics();
        
        update(state => ({
          ...state,
          statistics: stats,
        }));
        
        return stats;
      } catch (err) {
        console.error('Failed to load SampleModule statistics:', err);
        return null;
      }
    },
    
    setFilters(filters: SampleModuleFilters) {
      update(state => ({
        ...state,
        filters,
        page: 1,
        hasMore: true,
      }));
      this.loadItems(true);
    },
    
    selectItem(item: SampleModuleItem | null) {
      update(state => ({ ...state, selectedItem: item }));
    },
    
    clearError() {
      update(state => ({ ...state, error: null }));
    },
    
    reset() {
      set(initialState);
    },
  };
}

export const SampleModuleStore = createSampleModuleStore();

// ============================================
// Derived Stores
// ============================================
export const activeSampleModuleItems = derived(
  SampleModuleStore,
  $store => $store.items.filter(i => i.isActive)
);

export const SampleModuleByStatus = derived(
  SampleModuleStore,
  $store => {
    const grouped: Record<string, SampleModuleItem[]> = { active: [], inactive: [] };
    $store.items.forEach(i => {
      const key = i.isActive ? 'active' : 'inactive';
      grouped[key].push(i);
    });
    return grouped;
  }
);

export const isLoading = derived(SampleModuleStore, $store => $store.loading);
export const isSaving = derived(SampleModuleStore, $store => $store.saving);
export const SampleModuleError = derived(SampleModuleStore, $store => $store.error);
export const selectedSampleModuleItem = derived(SampleModuleStore, $store => $store.selectedItem);
// Value export renamed from SampleModuleStatistics: a same-named derived value
// collided with the SampleModuleStatistics *type* imported from contracts.
export const sampleModuleStatistics = derived(SampleModuleStore, $store => $store.statistics);
