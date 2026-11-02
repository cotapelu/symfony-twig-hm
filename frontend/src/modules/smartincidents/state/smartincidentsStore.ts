// Smart Incident Reporting Store - State Management with Svelte stores

import { writable, derived, get } from 'svelte/store';
import { smartIncidentApi } from '../services/smartincidents.service';
import { SmartIncidentEventBus, SmartIncidentEvents } from '../events/smartincidents.events';
import type { 
  SmartIncident,
  CreateIncidentRequest,
  UpdateIncidentRequest,
  IncidentFilters,
  IncidentStatistics,
  IncidentActivity,
  MapIncidentMarker,
  MapBounds,
  IncidentMessage,
  IncidentAttachment,
  IncidentHeatmapData,
} from '../contracts/smartincidents.types';

// ============================================
// State Store
// ============================================
interface SmartIncidentState {
  incidents: SmartIncident[];
  selectedIncident: SmartIncident | null;
  statistics: IncidentStatistics | null;
  activityLog: IncidentActivity[];
  mapMarkers: MapIncidentMarker[];
  heatmapData: IncidentHeatmapData | null;
  
  loading: boolean;
  saving: boolean;
  uploading: boolean;
  transcribing: boolean;
  error: string | null;
  
  filters: IncidentFilters;
  page: number;
  perPage: number;
  hasMore: boolean;
  
  // Real-time state
  unreadCount: number;
  isOnline: boolean;
}

const initialState: SmartIncidentState = {
  incidents: [],
  selectedIncident: null,
  statistics: null,
  activityLog: [],
  mapMarkers: [],
  heatmapData: null,
  loading: false,
  saving: false,
  uploading: false,
  transcribing: false,
  error: null,
  filters: {},
  page: 1,
  perPage: 20,
  hasMore: true,
  unreadCount: 0,
  isOnline: true,
};

function createSmartIncidentStore() {
  const { subscribe, set, update } = writable<SmartIncidentState>(initialState);
  
  return {
    subscribe,
    
    // ============================================
    // Incident CRUD Actions
    // ============================================
    
    async loadIncidents(reset = false) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const currentState = get({ subscribe });
        const page = reset ? 1 : currentState.page;
        
        const response = await smartIncidentApi.list(currentState.filters, page, currentState.perPage);
        
        update(state => ({
          ...state,
          incidents: reset ? response.data : [...state.incidents, ...response.data],
          page: page + 1,
          hasMore: response.hasMore || response.data.length === state.perPage,
          loading: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to load incidents',
        }));
      }
    },
    
    async searchIncidents(query: string) {
      if (!query.trim()) {
        await this.loadIncidents(true);
        return;
      }
      
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const results = await smartIncidentApi.search(query);
        
        SmartIncidentEventBus.publish(SmartIncidentEvents.updated(0, { searchResults: results.length }));
        
        update(state => ({
          ...state,
          incidents: results,
          loading: false,
          hasMore: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to search incidents',
        }));
      }
    },
    
    async getIncident(id: number) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const incident = await smartIncidentApi.getById(id);
        
        update(state => ({
          ...state,
          selectedIncident: incident,
          loading: false,
        }));
        
        return incident;
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to get incident',
        }));
        return null;
      }
    },
    
    async createIncident(data: CreateIncidentRequest, files?: File[]) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const incident = await smartIncidentApi.create(data, files);
        
        SmartIncidentEventBus.publish(SmartIncidentEvents.created(incident.id, incident));
        
        update(state => ({
          ...state,
          incidents: [incident, ...state.incidents],
          selectedIncident: incident,
          saving: false,
        }));
        
        return incident;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to create incident',
        }));
        throw err;
      }
    },
    
    async updateIncident(id: number, data: UpdateIncidentRequest) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const incident = await smartIncidentApi.update(id, data);
        
        SmartIncidentEventBus.publish(SmartIncidentEvents.updated(id, data));
        
        update(state => ({
          ...state,
          incidents: state.incidents.map(i => i.id === id ? incident : i),
          selectedIncident: state.selectedIncident?.id === id ? incident : state.selectedIncident,
          saving: false,
        }));
        
        return incident;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to update incident',
        }));
        throw err;
      }
    },
    
    // ============================================
    // Status & Assignment Actions
    // ============================================
    
    async updateStatus(id: number, status: string, notes?: string) {
      const currentState = get({ subscribe });
      const oldStatus = currentState.incidents.find(i => i.id === id)?.status || 'unknown';
      
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const incident = await smartIncidentApi.updateStatus(id, status, notes);
        
        SmartIncidentEventBus.publish(SmartIncidentEvents.statusChanged(id, oldStatus, status, 'current_user'));
        
        update(state => ({
          ...state,
          incidents: state.incidents.map(i => i.id === id ? incident : i),
          selectedIncident: state.selectedIncident?.id === id ? incident : state.selectedIncident,
          saving: false,
        }));
        
        return incident;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to update status',
        }));
        throw err;
      }
    },
    
    async assignIncident(id: number, officerId: string, team?: string) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const incident = await smartIncidentApi.assign(id, officerId, team);
        
        SmartIncidentEventBus.publish(SmartIncidentEvents.assigned(id, officerId, team, 'current_user'));
        
        update(state => ({
          ...state,
          incidents: state.incidents.map(i => i.id === id ? incident : i),
          selectedIncident: state.selectedIncident?.id === id ? incident : state.selectedIncident,
          saving: false,
        }));
        
        return incident;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to assign incident',
        }));
        throw err;
      }
    },
    
    async resolveIncident(id: number, summary: string) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const incident = await smartIncidentApi.resolve(id, summary);
        const resolutionTime = new Date().toISOString();
        
        SmartIncidentEventBus.publish(SmartIncidentEvents.resolved(id, summary, 'current_user', resolutionTime));
        
        update(state => ({
          ...state,
          incidents: state.incidents.map(i => i.id === id ? incident : i),
          selectedIncident: state.selectedIncident?.id === id ? incident : state.selectedIncident,
          saving: false,
        }));
        
        return incident;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to resolve incident',
        }));
        throw err;
      }
    },
    
    async escalateIncident(id: number, reason: string) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const incident = await smartIncidentApi.escalate(id, reason);
        
        SmartIncidentEventBus.publish(SmartIncidentEvents.escalated(id, reason, 'current_user'));
        
        update(state => ({
          ...state,
          incidents: state.incidents.map(i => i.id === id ? incident : i),
          selectedIncident: state.selectedIncident?.id === id ? incident : state.selectedIncident,
          saving: false,
        }));
        
        return incident;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to escalate incident',
        }));
        throw err;
      }
    },
    
    async closeIncident(id: number) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const incident = await smartIncidentApi.close(id);
        
        SmartIncidentEventBus.publish(SmartIncidentEvents.closed(id, 'current_user'));
        
        update(state => ({
          ...state,
          incidents: state.incidents.map(i => i.id === id ? incident : i),
          selectedIncident: state.selectedIncident?.id === id ? incident : state.selectedIncident,
          saving: false,
        }));
        
        return incident;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to close incident',
        }));
        throw err;
      }
    },
    
    // ============================================
    // Statistics & Filters
    // ============================================
    
    async loadStatistics(filters: Pick<IncidentFilters, 'wardCode' | 'dateFrom' | 'dateTo'> = {}) {
      try {
        const stats = await smartIncidentApi.getStatistics(filters);
        
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
    
    async loadActivityLog(id: number) {
      try {
        const activity = await smartIncidentApi.getActivityLog(id);
        
        update(state => ({
          ...state,
          activityLog: activity,
        }));
        
        return activity;
      } catch (err) {
        console.error('Failed to load activity log:', err);
        return [];
      }
    },
    
    setFilters(filters: IncidentFilters) {
      update(state => ({
        ...state,
        filters,
        page: 1,
        hasMore: true,
      }));
      this.loadIncidents(true);
    },
    
    selectIncident(incident: SmartIncident | null) {
      update(state => ({ ...state, selectedIncident: incident }));
    },
    
    clearError() {
      update(state => ({ ...state, error: null }));
    },
    
    reset() {
      set(initialState);
    },
    
    // ============================================
    // Map & Geospatial
    // ============================================
    
    async loadMapMarkers(bounds?: MapBounds, filters: IncidentFilters = {}) {
      try {
        const markers = await smartIncidentApi.getMapMarkers(bounds, filters);
        
        update(state => ({
          ...state,
          mapMarkers: markers,
        }));
        
        return markers;
      } catch (err) {
        console.error('Failed to load map markers:', err);
        return [];
      }
    },
    
    async loadHeatmapData(bounds?: MapBounds, timeframe: '24h' | '7d' | '30d' | '90d' = '30d') {
      try {
        const heatmap = await smartIncidentApi.getHeatmapData(bounds, timeframe);
        
        update(state => ({
          ...state,
          heatmapData: heatmap,
        }));
        
        return heatmap;
      } catch (err) {
        console.error('Failed to load heatmap data:', err);
        return null;
      }
    },
    
    // ============================================
    // Attachments
    // ============================================
    
    async addAttachment(incidentId: number, file: File, description?: string) {
      update(state => ({ ...state, uploading: true, error: null }));
      
      try {
        const attachment = await smartIncidentApi.addAttachment(incidentId, file, description);
        
        SmartIncidentEventBus.publish(SmartIncidentEvents.attachmentAdded(incidentId, attachment.id, attachment.fileType, 'current_user'));
        
        update(state => ({
          ...state,
          selectedIncident: state.selectedIncident ? {
            ...state.selectedIncident,
            attachments: [...state.selectedIncident.attachments, attachment],
          } : null,
          uploading: false,
        }));
        
        return attachment;
      } catch (err) {
        update(state => ({
          ...state,
          uploading: false,
          error: err instanceof Error ? err.message : 'Failed to add attachment',
        }));
        throw err;
      }
    },
    
    // ============================================
    // Messages/Chat
    // ============================================
    
    async sendMessage(incidentId: number, content: string, attachmentUrl?: string) {
      try {
        const message = await smartIncidentApi.sendMessage(incidentId, content, attachmentUrl);
        
        SmartIncidentEventBus.publish(SmartIncidentEvents.messageSent(incidentId, message.id, message.sender, message.senderName || ''));
        
        update(state => ({
          ...state,
          selectedIncident: state.selectedIncident ? {
            ...state.selectedIncident,
            messages: [...state.selectedIncident.messages, message],
          } : null,
        }));
        
        return message;
      } catch (err) {
        console.error('Failed to send message:', err);
        throw err;
      }
    },
    
    async loadMessages(incidentId: number) {
      try {
        const messages = await smartIncidentApi.getMessages(incidentId);
        
        update(state => ({
          ...state,
          selectedIncident: state.selectedIncident ? {
            ...state.selectedIncident,
            messages: messages,
          } : null,
        }));
        
        return messages;
      } catch (err) {
        console.error('Failed to load messages:', err);
        return [];
      }
    },
    
    async markMessagesRead(incidentId: number) {
      try {
        await smartIncidentApi.markMessagesRead(incidentId);
        
        update(state => ({
          ...state,
          selectedIncident: state.selectedIncident ? {
            ...state.selectedIncident,
            messages: state.selectedIncident.messages.map(m => ({ ...m, isRead: true })),
          } : null,
          unreadCount: Math.max(0, state.unreadCount - 1),
        }));
      } catch (err) {
        console.error('Failed to mark messages as read:', err);
      }
    },
    
    // ============================================
    // AI & Voice Features
    // ============================================
    
    async transcribeAudio(audioFile: File): Promise<{ text: string; confidence: number }> {
      update(state => ({ ...state, transcribing: true, error: null }));
      
      try {
        const result = await smartIncidentApi.transcribeAudio(audioFile);
        
        update(state => ({
          ...state,
          transcribing: false,
        }));
        
        return result;
      } catch (err) {
        update(state => ({
          ...state,
          transcribing: false,
          error: err instanceof Error ? err.message : 'Transcription failed',
        }));
        throw err;
      }
    },
    
    async classifyIncident(description: string): Promise<{
      incidentType: string;
      severity: string;
      confidence: number;
      suggestions: string[];
    }> {
      try {
        const result = await smartIncidentApi.classifyIncident(description);
        return result;
      } catch (err) {
        console.error('Classification failed:', err);
        return {
          incidentType: 'other',
          severity: 'medium',
          confidence: 0,
          suggestions: [],
        };
      }
    },
    
    // ============================================
    // Real-time / Connection
    // ============================================
    
    setOnlineStatus(isOnline: boolean) {
      update(state => ({ ...state, isOnline }));
    },
    
    setUnreadCount(count: number) {
      update(state => ({ ...state, unreadCount: count }));
    },
  };
}

export const smartIncidentStore = createSmartIncidentStore();

// ============================================
// Derived Stores
// ============================================
export const incidentsByStatus = derived(
  smartIncidentStore,
  $store => {
    const grouped: Record<string, SmartIncident[]> = {};
    $store.incidents.forEach(i => {
      if (!grouped[i.status]) grouped[i.status] = [];
      grouped[i.status].push(i);
    });
    return grouped;
  }
);

export const incidentsBySeverity = derived(
  smartIncidentStore,
  $store => {
    const grouped: Record<string, SmartIncident[]> = {};
    $store.incidents.forEach(i => {
      if (!grouped[i.severity]) grouped[i.severity] = [];
      grouped[i.severity].push(i);
    });
    return grouped;
  }
);

export const openIncidents = derived(
  smartIncidentStore,
  $store => $store.incidents.filter(i => 
    ['reported', 'assigned', 'in_progress'].includes(i.status)
  )
);

export const urgentIncidents = derived(
  smartIncidentStore,
  $store => $store.incidents.filter(i => 
    ['high', 'critical'].includes(i.severity) && 
    ['reported', 'assigned', 'in_progress'].includes(i.status)
  )
);

export const isLoading = derived(smartIncidentStore, $store => $store.loading);
export const isSaving = derived(smartIncidentStore, $store => $store.saving);
export const isUploading = derived(smartIncidentStore, $store => $store.uploading);
export const isTranscribing = derived(smartIncidentStore, $store => $store.transcribing);
export const smartIncidentError = derived(smartIncidentStore, $store => $store.error);
export const selectedIncident = derived(smartIncidentStore, $store => $store.selectedIncident);
export const incidentStatistics = derived(smartIncidentStore, $store => $store.statistics);
export const hasUnreadMessages = derived(smartIncidentStore, $store => $store.unreadCount > 0);
