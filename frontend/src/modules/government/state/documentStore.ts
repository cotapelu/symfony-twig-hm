// Document Store - Real API Integration with Svelte stores

import { writable, derived, get } from 'svelte/store';
import { documentApi } from '../services/government.service';
import { GovernmentEventBus } from '../events/government.events';
import type { 
  GovernmentDocument, 
  DocumentFilters, 
  DocumentStatistics,
  CreateDocumentRequest,
  DocumentType,
  DocumentStatus
} from '../contracts/government.types';

// ============================================
// State Stores
// ============================================
interface DocumentState {
  documents: GovernmentDocument[];
  selectedDocument: GovernmentDocument | null;
  statistics: DocumentStatistics | null;
  loading: boolean;
  saving: boolean;
  error: string | null;
  filters: DocumentFilters;
  page: number;
  perPage: number;
  hasMore: boolean;
}

const initialState: DocumentState = {
  documents: [],
  selectedDocument: null,
  statistics: null,
  loading: false,
  saving: false,
  error: null,
  filters: {},
  page: 1,
  perPage: 20,
  hasMore: true,
};

function createDocumentStore() {
  const { subscribe, set, update } = writable<DocumentState>(initialState);
  
  return {
    subscribe,
    
    // ============================================
    // Actions
    // ============================================
    
    async loadDocuments(reset = false) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const currentState = get({ subscribe });
        const page = reset ? 1 : currentState.page;
        
        const response = await documentApi.list(currentState.filters, page, currentState.perPage);
        
        update(state => ({
          ...state,
          documents: reset ? response.data : [...state.documents, ...response.data],
          page: page + 1,
          hasMore: response.data.length === state.perPage,
          loading: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to load documents',
        }));
      }
    },
    
    async searchDocuments(keyword: string) {
      if (!keyword.trim()) {
        await this.loadDocuments(true);
        return;
      }
      
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const results = await documentApi.search(keyword);
        
        // Publish search event
        GovernmentEventBus.publish({
          type: 'government.document.search',
          payload: {
            keyword,
            resultsCount: results.length,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          documents: results,
          loading: false,
          hasMore: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to search documents',
        }));
      }
    },
    
    async getDocument(id: string) {
      update(state => ({ ...state, loading: true, error: null }));
      
      try {
        const document = await documentApi.getById(id);
        
        update(state => ({
          ...state,
          selectedDocument: document,
          loading: false,
        }));
        
        return document;
      } catch (err) {
        update(state => ({
          ...state,
          loading: false,
          error: err instanceof Error ? err.message : 'Failed to get document',
        }));
        return null;
      }
    },
    
    async createDocument(data: CreateDocumentRequest) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        const document = await documentApi.create(data);
        
        // Publish event
        GovernmentEventBus.publish({
          type: 'government.document.created',
          payload: {
            document,
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          documents: [document, ...state.documents],
          saving: false,
        }));
        
        return document;
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to create document',
        }));
        throw err;
      }
    },
    
    async publishDocument(id: string) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        await documentApi.publish(id);
        
        // Publish event
        GovernmentEventBus.publish({
          type: 'government.document.published',
          payload: {
            documentId: id,
            publishedAt: new Date().toISOString(),
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          documents: state.documents.map(d => 
            d.id === id ? { ...d, status: 'published' as DocumentStatus } : d
          ),
          selectedDocument: state.selectedDocument?.id === id 
            ? { ...state.selectedDocument, status: 'published' as DocumentStatus }
            : state.selectedDocument,
          saving: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to publish document',
        }));
        throw err;
      }
    },
    
    async archiveDocument(id: string) {
      update(state => ({ ...state, saving: true, error: null }));
      
      try {
        await documentApi.archive(id);
        
        // Publish event
        GovernmentEventBus.publish({
          type: 'government.document.archived',
          payload: {
            documentId: id,
            archivedAt: new Date().toISOString(),
            timestamp: new Date().toISOString(),
          },
        });
        
        update(state => ({
          ...state,
          documents: state.documents.map(d => 
            d.id === id ? { ...d, status: 'archived' as DocumentStatus } : d
          ),
          selectedDocument: state.selectedDocument?.id === id 
            ? { ...state.selectedDocument, status: 'archived' as DocumentStatus }
            : state.selectedDocument,
          saving: false,
        }));
      } catch (err) {
        update(state => ({
          ...state,
          saving: false,
          error: err instanceof Error ? err.message : 'Failed to archive document',
        }));
        throw err;
      }
    },
    
    async loadStatistics() {
      try {
        const stats = await documentApi.getStatistics();
        
        update(state => ({
          ...state,
          statistics: stats,
        }));
        
        return stats;
      } catch (err) {
        console.error('Failed to load document statistics:', err);
        return null;
      }
    },
    
    setFilters(filters: DocumentFilters) {
      update(state => ({
        ...state,
        filters,
        page: 1,
        hasMore: true,
      }));
      this.loadDocuments(true);
    },
    
    setTypeFilter(type: DocumentType | undefined) {
      this.setFilters({ ...get({ subscribe }).filters, type });
    },
    
    setStatusFilter(status: DocumentStatus | undefined) {
      this.setFilters({ ...get({ subscribe }).filters, status });
    },
    
    selectDocument(document: GovernmentDocument | null) {
      update(state => ({ ...state, selectedDocument: document }));
    },
    
    clearError() {
      update(state => ({ ...state, error: null }));
    },
    
    reset() {
      set(initialState);
    },
  };
}

export const documentStore = createDocumentStore();

// ============================================
// Derived Stores
// ============================================
export const publishedDocuments = derived(
  documentStore,
  $store => $store.documents.filter(d => d.status === 'published')
);

export const documentsByType = derived(
  documentStore,
  $store => {
    const grouped: Record<string, GovernmentDocument[]> = {};
    $store.documents.forEach(d => {
      if (!grouped[d.type]) grouped[d.type] = [];
      grouped[d.type].push(d);
    });
    return grouped;
  }
);

export const recentDocuments = derived(
  documentStore,
  $store => [...$store.documents]
    .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
    .slice(0, 10)
);

export const isDocumentLoading = derived(documentStore, $store => $store.loading);
export const isDocumentSaving = derived(documentStore, $store => $store.saving);
export const documentError = derived(documentStore, $store => $store.error);
