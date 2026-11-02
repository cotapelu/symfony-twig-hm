import { writable, derived, get } from 'svelte/store';
import type {
  AnalyticsReport,
  AnalyticsMetric,
  AnalyticsDashboard,
  AnalyticsEvent,
  AnalyticsState,
  CreateReportRequest,
  CreateDashboardRequest,
  TrackEventRequest
} from '../contracts/analyticsTypes';
import { analyticsService } from '../services/analyticsService';

interface ExtendedAnalyticsState extends AnalyticsState {
  reportsPage: number;
  reportsTotal: number;
  dashboardsPage: number;
  dashboardsTotal: number;
  operationInProgress: boolean;
  saving: boolean; // for create/update/delete operations
  deleteConfirmCallback: ((id: string, type: 'report' | 'dashboard') => Promise<boolean>) | null;
}

const initialState: ExtendedAnalyticsState = {
  reports: [],
  metrics: [],
  dashboards: [],
  events: [],
  loading: false,
  error: null,
  reportsPage: 1,
  reportsTotal: 0,
  dashboardsPage: 1,
  dashboardsTotal: 0,
  operationInProgress: false,
  saving: false,
  deleteConfirmCallback: null
};

function createAnalyticsStore() {
  const { subscribe, set, update } = writable<ExtendedAnalyticsState>(initialState);

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

    setDeleteConfirmCallback(callback: (id: string, type: 'report' | 'dashboard') => Promise<boolean>) {
      update(s => ({ ...s, deleteConfirmCallback: callback }));
    },

    async loadReports(page = 1, limit = 20) {
      if (checkOperation()) return;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        const result = await analyticsService.getReports(page, limit);
        update(s => ({ 
          ...s, 
          reports: result.data, 
          reportsTotal: result.total,
          reportsPage: page,
          loading: false, 
          saving: false,
          operationInProgress: false 
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
      }
    },

    async loadReport(id: string) {
      if (checkOperation()) return null;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        const report = await analyticsService.getReport(id);
        update(s => {
          const exists = s.reports.find(r => r.id === id);
          return {
            ...s,
            reports: exists ? s.reports.map(r => r.id === id ? report : r) : [...s.reports, report],
            loading: false,
            saving: false,
            operationInProgress: false
          };
        });
        return report;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
        return null;
      }
    },

    async createReport(request: CreateReportRequest) {
      if (checkOperation()) return null;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        const report = await analyticsService.createReport(request);
        update(s => ({ 
          ...s, 
          reports: [...s.reports, report], 
          reportsTotal: s.reportsTotal + 1,
          loading: false, 
          saving: false,
          operationInProgress: false 
        }));
        return report;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
        throw error;
      }
    },

    async deleteReport(id: string) {
      const state = get({ subscribe });
      if (state.deleteConfirmCallback) {
        const confirmed = await state.deleteConfirmCallback(id, 'report');
        if (!confirmed) return;
      }
      if (checkOperation()) return;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        const originalReports = get({ subscribe }).reports;
        update(s => ({ ...s, reports: s.reports.filter(r => r.id !== id), loading: false, saving: false, operationInProgress: false }));
        await analyticsService.deleteReport(id);
        update(s => ({ ...s, reportsTotal: Math.max(0, s.reportsTotal - 1) }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
        throw error;
      }
    },

    async loadMetrics(metricNames?: string[]) {
      if (checkOperation()) return;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        const metrics = await analyticsService.getMetrics(metricNames);
        update(s => ({ ...s, metrics, loading: false, saving: false, operationInProgress: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
      }
    },

    clearMetricsCache() {
      analyticsService.clearMetricsCache();
    },

    async loadDashboards(page = 1, limit = 20) {
      if (checkOperation()) return;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        const result = await analyticsService.getDashboards(page, limit);
        update(s => ({ 
          ...s, 
          dashboards: result.data, 
          dashboardsTotal: result.total,
          dashboardsPage: page,
          loading: false, 
          saving: false,
          operationInProgress: false 
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
      }
    },

    async loadDashboard(id: string) {
      if (checkOperation()) return null;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        const dashboard = await analyticsService.getDashboard(id);
        update(s => {
          const exists = s.dashboards.find(d => d.id === id);
          return {
            ...s,
            dashboards: exists ? s.dashboards.map(d => d.id === id ? dashboard : d) : [...s.dashboards, dashboard],
            loading: false,
            saving: false,
            operationInProgress: false
          };
        });
        return dashboard;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
        return null;
      }
    },

    async createDashboard(request: CreateDashboardRequest) {
      if (checkOperation()) return null;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        const dashboard = await analyticsService.createDashboard(request);
        update(s => ({ 
          ...s, 
          dashboards: [...s.dashboards, dashboard], 
          dashboardsTotal: s.dashboardsTotal + 1,
          loading: false, 
          saving: false,
          operationInProgress: false 
        }));
        return dashboard;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
        throw error;
      }
    },

    async updateDashboard(id: string, request: CreateDashboardRequest) {
      if (checkOperation()) return null;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        const dashboard = await analyticsService.updateDashboard(id, request);
        update(s => ({
          ...s,
          dashboards: s.dashboards.map(d => d.id === id ? dashboard : d),
          loading: false,
          saving: false,
          operationInProgress: false
        }));
        return dashboard;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
        throw error;
      }
    },

    async deleteDashboard(id: string) {
      const state = get({ subscribe });
      if (state.deleteConfirmCallback) {
        const confirmed = await state.deleteConfirmCallback(id, 'dashboard');
        if (!confirmed) return;
      }
      if (checkOperation()) return;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        await analyticsService.deleteDashboard(id);
        update(s => ({ 
          ...s, 
          dashboards: s.dashboards.filter(d => d.id !== id), 
          loading: false, 
          saving: false,
          operationInProgress: false,
          dashboardsTotal: Math.max(0, s.dashboardsTotal - 1)
        }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
        throw error;
      }
    },

    async trackEvent(request: TrackEventRequest) {
      try {
        const event = await analyticsService.trackEvent(request);
        update(s => ({ ...s, events: [...s.events, event] }));
        return event;
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message }));
        throw error;
      }
    },

    async loadEvents(eventName?: string, limit = 100) {
      if (checkOperation()) return;
      update(s => ({ ...s, loading: true, saving: true, error: null, operationInProgress: true }));
      try {
        const events = await analyticsService.getEvents(eventName, limit);
        const existingIds = new Set(get({ subscribe }).events.map(e => e.id));
        const newEvents = events.filter(e => !existingIds.has(e.id));
        update(s => ({ ...s, events: [...s.events, ...newEvents], loading: false, saving: false, operationInProgress: false }));
      } catch (error) {
        update(s => ({ ...s, error: (error as Error).message, loading: false, saving: false, operationInProgress: false }));
      }
    },

    clearError() {
      update(s => ({ ...s, error: null }));
    },

    reset() {
      analyticsService.clearMetricsCache();
      set(initialState);
    }
  };
}

export const analyticsStore = createAnalyticsStore();

export const reports = derived(analyticsStore, $s => $s.reports);
export const metrics = derived(analyticsStore, $s => $s.metrics);
export const dashboards = derived(analyticsStore, $s => $s.dashboards);
export const events = derived(analyticsStore, $s => $s.events);
export const analyticsLoading = derived(analyticsStore, $s => $s.loading);
export const analyticsError = derived(analyticsStore, $s => $s.error);

// Deprecated/alternative aliases for backward compatibility with existing tests
export const isLoading = analyticsLoading;
export const isSaving = derived(analyticsStore, $s => $s.saving);
export const isOperationInProgress = derived(analyticsStore, $s => $s.operationInProgress);
export const error = analyticsError;
export const reportsPage = derived(analyticsStore, $s => $s.reportsPage);
export const reportsTotal = derived(analyticsStore, $s => $s.reportsTotal);
export const dashboardsPage = derived(analyticsStore, $s => $s.dashboardsPage);
export const dashboardsTotal = derived(analyticsStore, $s => $s.dashboardsTotal);
