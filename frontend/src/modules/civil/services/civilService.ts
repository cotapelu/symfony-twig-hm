import type { CivilService, CivilRequest } from '../contracts/civilTypes';

const API_BASE = '/api/civil';
const MAX_RETRIES = 3;

export class CivilServiceModule {
  private validateServiceId(serviceId: string): void {
    if (!serviceId || typeof serviceId !== 'string') {
      throw new Error('Service ID is required');
    }
    if (serviceId.length > 100) {
      throw new Error('Service ID is too long');
    }
  }

  private validateDocuments(documents: string[]): void {
    if (!Array.isArray(documents)) {
      throw new Error('Documents must be an array');
    }
    if (documents.length === 0) {
      throw new Error('At least one document is required');
    }
    if (documents.length > 20) {
      throw new Error('Cannot submit more than 20 documents');
    }
    documents.forEach((doc, i) => {
      if (!doc || typeof doc !== 'string') {
        throw new Error(`Document at index ${i} is invalid`);
      }
      if (doc.length > 500) {
        throw new Error(`Document at index ${i} is too long (max 500 chars)`);
      }
    });
  }

  private async withRetry<T>(fn: () => Promise<T>): Promise<T> {
    let lastError: Error | null = null;
    for (let i = 0; i < MAX_RETRIES; i++) {
      try {
        return await fn();
      } catch (error) {
        lastError = error as Error;
        if (i < MAX_RETRIES - 1) {
          await new Promise(resolve => setTimeout(resolve, Math.pow(2, i) * 1000));
        }
      }
    }
    throw lastError;
  }

  async getServices(): Promise<CivilService[]> {
    const response = await this.withRetry(() => fetch(`${API_BASE}/services`));
    if (!response.ok) throw new Error(`Failed to fetch services: ${response.statusText}`);
    return response.json();
  }

  async getMyRequests(): Promise<CivilRequest[]> {
    const response = await this.withRetry(() => fetch(`${API_BASE}/my-requests`));
    if (!response.ok) throw new Error(`Failed to fetch my requests: ${response.statusText}`);
    return response.json();
  }

  async getMyRequest(id: string): Promise<CivilRequest> {
    if (!id || typeof id !== 'string') {
      throw new Error('Request ID is required');
    }
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/my-requests/${encodeURIComponent(id)}`)
    );
    if (!response.ok) throw new Error(`Failed to fetch request: ${response.statusText}`);
    return response.json();
  }

  async submitRequest(serviceId: string, documents: string[]): Promise<CivilRequest> {
    this.validateServiceId(serviceId);
    this.validateDocuments(documents);
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/requests`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ serviceId, documents })
      })
    );
    if (!response.ok) throw new Error(`Failed to submit request: ${response.statusText}`);
    return response.json();
  }

  async cancelRequest(id: string): Promise<CivilRequest> {
    if (!id || typeof id !== 'string') {
      throw new Error('Request ID is required');
    }
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/my-requests/${encodeURIComponent(id)}/cancel`, {
        method: 'POST'
      })
    );
    if (!response.ok) throw new Error(`Failed to cancel request: ${response.statusText}`);
    return response.json();
  }
}

export const civilServiceModule = new CivilServiceModule();
export default civilServiceModule;

// Exported for testing only
export const __testing__ = {
  clearCache: () => {}
};
