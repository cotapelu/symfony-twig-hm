// Procedure & Application Store - Real API Integration with Svelte stores

import { writable, derived, get } from 'svelte/store';
import { procedureApi, applicationApi } from '../services/government.service';
import { GovernmentEventBus } from '../events/government.events';
import type { 
  AdministrativeProcedure, 
  ProcedureApplication,
  ProcedureFilters, 
  ApplicationFilters,
  CreateProcedureRequest,
  CreateApplicationRequest,
  ApplicationStatus,
  ProcedureCategory
} from '../contracts/government.types';

// ============================================
// State Stores
// ============================================
interface ProcedureState {
  procedures: AdministrativeProcedure[];
  applications: ProcedureApplication[];
  selectedProcedure: AdministrativeProcedure | null;
  selectedApplication: ProcedureApplication | null;
  loading: boolean;
  saving: boolean;
  error: string | null;
  procedureFilters: ProcedureFilters;
  applicationFilters: ApplicationFilters;
  page: number;
  perPage: number;
  hasMore: boolean;
}

const initialState: ProcedureState = {
  procedures: [],
  applications: [],
  selectedProcedure: null,
  selectedApplication: null,
  loading: false,
  saving: false,
  error: null,
  procedureFilters: {},
  applicationFilters: {},
  page: 1,
  perPage: 20,
  hasMore: true,
};

function createProcedureStore() {
  const { subscribe, set, update } = writable<ProcedureState>(initialState);
  
  return {
    subscribe,
    
    // ============================================
    // Procedure Actions
    // ============================================
    
    async loadProcedures(reset = false) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const currentState = get({ subscribe });
        const page = reset ? 1 : currentState.page;
        
        const response = await procedureApi.list(currentState.procedureFilters, page, currentState.perPage);
        
        update(state => ({
          ...state,
          procedures: reset ? response.data : [...state.procedures, ...response.data],
          page: page + 1,
          hasMore: response.data.length === state.perPage,
          loading: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to load procedures',
        }));
      }
    },
    
    async getProcedure(id: string) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const procedure = await procedureApi.getById(id);
        
        update(state => ({
          ...state,
          selectedProcedure: procedure,
          loading: false,
        }));
        
        return procedure;
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to get procedure',
        }));
        return null;
      }
    },
    
    async createProcedure(data: CreateProcedureRequest) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const procedure = await procedureApi.create(data);
        
        // Publish event
        GovernmentEventBus.publish({
          type: 'government.procedure.created',
          payload: {
            procedure,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          procedures: [procedure, ...state.procedures],
          saving: false,
        }));
        
        return procedure;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to create procedure',
        }));
        throw err;
      }
    },
    
    async loadActiveProcedures() {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const procedures = await procedureApi.getActive();
        
        update(state => ({
          ...state,
          procedures,
          loading: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to load active procedures',
        }));
      }
    },
    
    async loadProceduresByCategory(category: ProcedureCategory) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const procedures = await procedureApi.getByCategory(category);
        
        update(state => ({
          ...state,
          procedures,
          loading: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to load procedures by category',
        }));
      }
    },
    
    setProcedureFilters(filters: ProcedureFilters) {
      update(state => ({
        ...state,
        procedureFilters: filters,
        page: 1,
        hasMore: true,
      }));
      this.loadProcedures(true);
    },
    
    // ============================================
    // Application Actions
    // ============================================
    
    async loadApplications(reset = false) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const currentState = get({ subscribe });
        const page = reset ? 1 : currentState.page;
        
        const response = await applicationApi.list(currentState.applicationFilters, page, currentState.perPage);
        
        update(state => ({
          ...state,
          applications: reset ? response.data : [...state.applications, ...response.data],
          page: page + 1,
          hasMore: response.data.length === state.perPage,
          loading: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to load applications',
        }));
      }
    },
    
    async createApplication(data: CreateApplicationRequest) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const application = await applicationApi.create(data);
        
        // Publish event
        GovernmentEventBus.publish({
          type: 'government.application.submitted',
          payload: {
            application,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          applications: [application, ...state.applications],
          saving: false,
        }));
        
        return application;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to create application',
        }));
        throw err;
      }
    },
    
    async updateApplicationStatus(id: string, status: ApplicationStatus, notes?: string) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const application = await applicationApi.updateStatus(id, status, notes);
        const oldStatus = get({ subscribe }).applications.find(a => a.id === id)?.status;
        
        // Publish event
        if (status === 'completed') {
          GovernmentEventBus.publish({
            type: 'government.application.completed',
            payload: {
              application,
              result: notes || 'Hoàn thành',
              completedAt: new Date().toISOString(),
              timestamp: new Date().toISOString(),
            },
          });
        } else {
          GovernmentEventBus.publish({
            type: 'government.application.status_changed',
            payload: {
              applicationId: id,
              oldStatus: oldStatus || 'unknown',
              newStatus: status,
              processedBy: application.processedBy,
              notes: notes || null,
              timestamp: new Date().toISOString(),
            },
          });
        }
        
        update(state => ({
          ...state,
          applications: state.applications.map(a => a.id === id ? application : a),
          selectedApplication: state.selectedApplication?.id === id ? application : state.selectedApplication,
          saving: false,
        }));
        
        return application;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to update application status',
        }));
        throw err;
      }
    },
    
    async loadPendingApplications() {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const applications = await applicationApi.getPending();
        
        update(state => ({
          ...state,
          applications,
          loading: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to load pending applications',
        }));
      }
    },
    
    async loadApplicationsByCitizen(citizenId: string) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const applications = await applicationApi.getByCitizen(citizenId);
        
        update(state => ({
          ...state,
          applications,
          loading: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to load citizen applications',
        }));
      }
    },
    
    setApplicationFilters(filters: ApplicationFilters) {
      update(state => ({
        ...state,
        applicationFilters: filters,
        page: 1,
        hasMore: true,
      }));
      this.loadApplications(true);
    },
    
    // ============================================
    // Selection Actions
    // ============================================
    
    selectProcedure(procedure: AdministrativeProcedure | null) {
      update(state => ({ ...state, selectedProcedure: procedure }));
    },
    
    selectApplication(application: ProcedureApplication | null) {
      update(state => ({ ...state, selectedApplication: application }));
    },
    
    clearError() {
      update(state => ({ ...state, error: null }));
    },
    
    reset() {
      set(initialState);
    },
  };
}

export const procedureStore = createProcedureStore();

// ============================================
// Derived Stores
// ============================================
export const activeProcedures = derived(
  procedureStore,
  $store => $store.procedures.filter(p => p.isActive)
);

export const proceduresByCategory = derived(
  procedureStore,
  $store => {
    const grouped: Record<string, AdministrativeProcedure[]> = {};
    $store.procedures.forEach(p => {
      if (!grouped[p.category]) grouped[p.category] = [];
      grouped[p.category].push(p);
    });
    return grouped;
  }
);

export const pendingApplications = derived(
  procedureStore,
  $store => $store.applications.filter(a => 
    ['submitted', 'received', 'processing', 'awaiting_additional_docs'].includes(a.status)
  )
);

export const completedApplications = derived(
  procedureStore,
  $store => $store.applications.filter(a => a.status === 'completed')
);

export const applicationsByStatus = derived(
  procedureStore,
  $store => {
    const grouped: Record<string, ProcedureApplication[]> = {};
    $store.applications.forEach(a => {
      if (!grouped[a.status]) grouped[a.status] = [];
      grouped[a.status].push(a);
    });
    return grouped;
  }
);

export const isProcedureLoading = derived(procedureStore, $store => $store.loading);
export const isProcedureSaving = derived(procedureStore, $store => $store.saving);
export const procedureError = derived(procedureStore, $store => $store.error);
