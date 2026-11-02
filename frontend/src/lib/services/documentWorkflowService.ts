import { writable } from 'svelte/store';
import { browser } from '$app/environment';

interface DocumentWorkflow {
  id: string;
  documentId: string;
  status: 'pending' | 'approved' | 'rejected' | 'published' | 'archived';
  processedBy?: string | null;
  comments?: string | null;
  processedAt?: string; // ISO date string
  createdAt: string; // ISO date string
  updatedAt: string; // ISO date string
}

interface DocumentFilters {
  status?: string;
  page?: number;
  perPage?: number;
}

class DocumentWorkflowService {
  private apiUrl = '/api/government/document-workflows';
  private workflowsStore = writable<DocumentWorkflow[]>([]);
  private loadingStore = writable<boolean>(false);
  private errorStore = writable<string | null>(null);
  private totalStore = writable<number>(0);

  workflows = this.workflowsStore;
  loading = this.loadingStore;
  error = this.errorStore;
  total = this.totalStore;

  async fetchWorkflows(filters: DocumentFilters = {}): Promise<void> {
    if (!browser) return;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const params = new URLSearchParams();
      
      if (filters.status) params.append('status', filters.status);
      if (filters.page) params.append('page', String(filters.page));
      if (filters.perPage) params.append('perPage', String(filters.perPage));

      const response = await fetch(`${this.apiUrl}?${params}`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      this.workflowsStore.set(data.data || []);
      this.totalStore.set(parseInt(data.pagination?.total) || 0);
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to fetch document workflows:', err);
    } finally {
      this.loadingStore.set(false);
    }
  }

  async submitForReview(documentId: string): Promise<DocumentWorkflow | null> {
    if (!browser) return null;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const response = await fetch(`${this.apiUrl}/${documentId}/submit`, {
        method: 'POST'
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      // Refresh the workflow list
      await this.fetchWorkflows();
      return result.data;
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to submit document for review:', err);
      return null;
    } finally {
      this.loadingStore.set(false);
    }
  }

  async approve(documentId: string, approvedBy: string, comments?: string | null): Promise<DocumentWorkflow | null> {
    if (!browser) return null;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const response = await fetch(`${this.apiUrl}/${documentId}/approve`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          approvedBy,
          comments
        })
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      // Refresh the workflow list
      await this.fetchWorkflows();
      return result.data;
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to approve document:', err);
      return null;
    } finally {
      this.loadingStore.set(false);
    }
  }

  async reject(documentId: string, rejectedBy: string | null, reason: string): Promise<DocumentWorkflow | null> {
    if (!browser) return null;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const response = await fetch(`${this.apiUrl}/${documentId}/reject`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          rejectedBy,
          reason
        })
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      // Refresh the workflow list
      await this.fetchWorkflows();
      return result.data;
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to reject document:', err);
      return null;
    } finally {
      this.loadingStore.set(false);
    }
  }

  async publish(documentId: string): Promise<DocumentWorkflow | null> {
    if (!browser) return null;

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const response = await fetch(`${this.apiUrl}/${documentId}/publish`, {
        method: 'POST'
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      // Refresh the workflow list
      await this.fetchWorkflows();
      return result.data;
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to publish document:', err);
      return null;
    } finally {
      this.loadingStore.set(false);
    }
  }

  async getWorkflowHistory(documentId: string): Promise<DocumentWorkflow[]> {
    if (!browser) return [];

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const response = await fetch(`${this.apiUrl}/${documentId}/history`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data.data || [];
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to fetch workflow history:', err);
      return [];
    } finally {
      this.loadingStore.set(false);
    }
  }

  async getPendingDocuments(): Promise<DocumentWorkflow[]> {
    if (!browser) return [];

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const response = await fetch(`${this.apiUrl}/pending`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data.data || [];
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to fetch pending documents:', err);
      return [];
    } finally {
      this.loadingStore.set(false);
    }
  }

  async getDocumentsByStatus(status: string): Promise<DocumentWorkflow[]> {
    if (!browser) return [];

    this.loadingStore.set(true);
    this.errorStore.set(null);

    try {
      const response = await fetch(`${this.apiUrl}/by-status/${status}`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data.data || [];
    } catch (err) {
      this.errorStore.set(err instanceof Error ? err.message : 'Unknown error');
      console.error('Failed to fetch documents by status:', err);
      return [];
    } finally {
      this.loadingStore.set(false);
    }
  }
}

export const documentWorkflowService = new DocumentWorkflowService();