// sample-module API Service - Handles API calls
// Replace sample-module with the actual module name during generation

import type {
  SampleModuleItem,
  CreateSampleModuleRequest,
  UpdateSampleModuleRequest,
  SampleModuleFilters,
  SampleModuleStatistics,
  ApiResponse,
  PaginatedResponse,
} from '../contracts/sample-module.types';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080';

// ============================================
// HTTP Helper
// ============================================
async function fetchApi<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_BASE_URL}${endpoint}`;
  
  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
  
  // Add auth token if available
  const token = localStorage.getItem('auth_token');
  if (token) {
    defaultHeaders['Authorization'] = `Bearer ${token}`;
  }
  
  const response = await fetch(url, {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers,
    },
  });
  
  if (!response.ok) {
    const error = await response.json().catch(() => ({ error: 'Unknown error' }));
    throw new Error(error.error || `HTTP ${response.status}: ${response.statusText}`);
  }
  
  return response.json();
}

// ============================================
// SampleModule API
// ============================================
export const sampleModuleApi = {
  async create(data: CreateSampleModuleRequest): Promise<SampleModuleItem> {
    const response = await fetchApi<ApiResponse<SampleModuleItem>>(`/api/sample-module/items`, {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data;
  },
  
  async getById(id: string): Promise<SampleModuleItem> {
    const response = await fetchApi<ApiResponse<SampleModuleItem>>(`/api/sample-module/items/${id}`);
    return response.data;
  },
  
  async list(filters: SampleModuleFilters = {}, page = 1, perPage = 20): Promise<PaginatedResponse<SampleModuleItem>> {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('perPage', String(perPage));
    
    if (filters.search) params.set('search', filters.search);
    if (filters.active !== undefined) params.set('active', String(filters.active));
    
    const response = await fetchApi<PaginatedResponse<SampleModuleItem>>(`/api/sample-module/items?${params}`);
    return response;
  },
  
  async search(query: string): Promise<SampleModuleItem[]> {
    const response = await fetchApi<ApiResponse<SampleModuleItem[]>>(`/api/sample-module/items/search?q=${encodeURIComponent(query)}`);
    return response.data;
  },
  
  async update(id: string, data: UpdateSampleModuleRequest): Promise<SampleModuleItem> {
    const response = await fetchApi<ApiResponse<SampleModuleItem>>(`/api/sample-module/items/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return response.data;
  },
  
  async deactivate(id: string): Promise<void> {
    await fetchApi<ApiResponse<void>>(`/api/sample-module/items/${id}/deactivate`, {
      method: 'POST',
    });
  },
  
  async activate(id: string): Promise<void> {
    await fetchApi<ApiResponse<void>>(`/api/sample-module/items/${id}/activate`, {
      method: 'POST',
    });
  },
  
  async getStatistics(filters: Pick<SampleModuleFilters, undefined> = {}): Promise<SampleModuleStatistics> {
    const response = await fetchApi<ApiResponse<SampleModuleStatistics>>(`/api/sample-module/items/statistics`);
    return response.data;
  },
};
