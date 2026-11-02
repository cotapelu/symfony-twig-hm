import { writable, derived } from 'svelte/store';

interface DashboardStats {
  messages: number;
  groups: number;
  files: number;
  calls: number;
}

interface DashboardState {
  stats: DashboardStats | null;
  loading: boolean;
  error: string | null;
}

function createDashboardStore() {
  const { subscribe, set, update } = writable<DashboardState>({
    stats: null,
    loading: false,
    error: null
  });

  return {
    subscribe,

    loadStats: async (): Promise<DashboardStats> => {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const response = await fetch('/api/dashboard/stats');
        
        if (!response.ok) {
          throw new Error('Failed to load dashboard stats');
        }

        const stats = await response.json();
        
        update(state => ({ ...state, stats, loading: false }));
        
        return stats;
      } catch (error) {
        update(state => ({ 
          ...state, 
          loading: false, 
          error: (error as Error).message 
        }));
        throw error;
      }
    },

    setError: (error: string | null) => {
      update(state => ({ ...state, error }));
    },

    clearError: () => {
      update(state => ({ ...state, error: null }));
    }
  };
}

export const dashboardStore = createDashboardStore();

export const dashboardStats = derived(
  dashboardStore,
  $dashboardStore => $dashboardStore.stats
);

export const dashboardLoading = derived(
  dashboardStore,
  $dashboardStore => $dashboardStore.loading
);