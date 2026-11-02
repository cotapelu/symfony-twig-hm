// Government API Service - Handles all API calls to PHP Symfony backend

import type {
  Citizen,
  CreateCitizenRequest,
  UpdateCitizenRequest,
  CitizenFilters,
  CitizenStatistics,
  GovernmentDocument,
  CreateDocumentRequest,
  DocumentFilters,
  DocumentStatistics,
  AdministrativeProcedure,
  CreateProcedureRequest,
  ProcedureFilters,
  ProcedureApplication,
  CreateApplicationRequest,
  ApplicationFilters,
  ApiResponse,
  PaginatedResponse,
} from '../contracts/government.types';

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
// Citizen API
// ============================================
export const citizenApi = {
  async create(data: CreateCitizenRequest): Promise<Citizen> {
    const response = await fetchApi<ApiResponse<Citizen>>('/api/government/citizens', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data;
  },
  
  async getById(id: string): Promise<Citizen> {
    const response = await fetchApi<ApiResponse<Citizen>>(`/api/government/citizens/${id}`);
    return response.data;
  },
  
  async list(filters: CitizenFilters = {}, page = 1, perPage = 20): Promise<PaginatedResponse<Citizen>> {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('perPage', String(perPage));
    
    if (filters.search) params.set('search', filters.search);
    if (filters.province) params.set('province', filters.province);
    if (filters.district) params.set('district', filters.district);
    if (filters.ward) params.set('ward', filters.ward);
    if (filters.active !== undefined) params.set('active', String(filters.active));
    
    const response = await fetchApi<PaginatedResponse<Citizen>>(`/api/government/citizens?${params}`);
    return response;
  },
  
  async search(query: string): Promise<Citizen[]> {
    const response = await fetchApi<ApiResponse<Citizen[]>>(`/api/government/citizens/search?q=${encodeURIComponent(query)}`);
    return response.data;
  },
  
  async update(id: string, data: UpdateCitizenRequest): Promise<Citizen> {
    const response = await fetchApi<ApiResponse<Citizen>>(`/api/government/citizens/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return response.data;
  },
  
  async deactivate(id: string): Promise<void> {
    await fetchApi<ApiResponse<void>>(`/api/government/citizens/${id}/deactivate`, {
      method: 'POST',
    });
  },
  
  async activate(id: string): Promise<void> {
    await fetchApi<ApiResponse<void>>(`/api/government/citizens/${id}/activate`, {
      method: 'POST',
    });
  },
  
  async getByHousehold(householdId: string): Promise<Citizen[]> {
    const response = await fetchApi<ApiResponse<Citizen[]>>(`/api/government/citizens/household/${householdId}`);
    return response.data;
  },
  
  async getStatistics(filters: Pick<CitizenFilters, 'province' | 'district'> = {}): Promise<CitizenStatistics> {
    const params = new URLSearchParams();
    if (filters.province) params.set('province', filters.province);
    if (filters.district) params.set('district', filters.district);
    
    const response = await fetchApi<ApiResponse<CitizenStatistics>>(`/api/government/citizens/statistics?${params}`);
    return response.data;
  },
};

// ============================================
// Document API
// ============================================
export const documentApi = {
  async create(data: CreateDocumentRequest): Promise<GovernmentDocument> {
    const response = await fetchApi<ApiResponse<GovernmentDocument>>('/api/government/documents', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data;
  },
  
  async list(filters: DocumentFilters = {}, page = 1, perPage = 20): Promise<PaginatedResponse<GovernmentDocument>> {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('perPage', String(perPage));
    
    if (filters.type) params.set('type', filters.type);
    if (filters.status) params.set('status', filters.status);
    
    const response = await fetchApi<ApiResponse<PaginatedResponse<GovernmentDocument>>>(`/api/government/documents?${params}`);
    return response.data;
  },
  
  async search(keyword: string, limit = 20): Promise<GovernmentDocument[]> {
    const response = await fetchApi<ApiResponse<GovernmentDocument[]>>(
      `/api/government/documents/search?q=${encodeURIComponent(keyword)}&limit=${limit}`
    );
    return response.data;
  },
  
  async getById(id: string): Promise<GovernmentDocument> {
    const response = await fetchApi<ApiResponse<GovernmentDocument>>(`/api/government/documents/${id}`);
    return response.data;
  },
  
  async publish(id: string): Promise<void> {
    await fetchApi<ApiResponse<void>>(`/api/government/documents/${id}/publish`, {
      method: 'POST',
    });
  },
  
  async archive(id: string): Promise<void> {
    await fetchApi<ApiResponse<void>>(`/api/government/documents/${id}/archive`, {
      method: 'POST',
    });
  },
  
  async getStatistics(): Promise<DocumentStatistics> {
    const response = await fetchApi<ApiResponse<DocumentStatistics>>('/api/government/documents/statistics');
    return response.data;
  },
};

// ============================================
// Procedure API (TTHC)
// ============================================
export const procedureApi = {
  async create(data: CreateProcedureRequest): Promise<AdministrativeProcedure> {
    const response = await fetchApi<ApiResponse<AdministrativeProcedure>>('/api/government/procedures', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data;
  },
  
  async list(filters: ProcedureFilters = {}, page = 1, perPage = 20): Promise<PaginatedResponse<AdministrativeProcedure>> {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('perPage', String(perPage));
    
    if (filters.category) params.set('category', filters.category);
    if (filters.level) params.set('level', filters.level);
    if (filters.search) params.set('search', filters.search);
    
    const response = await fetchApi<ApiResponse<PaginatedResponse<AdministrativeProcedure>>>(`/api/government/procedures?${params}`);
    return response.data;
  },
  
  async getById(id: string): Promise<AdministrativeProcedure> {
    const response = await fetchApi<ApiResponse<AdministrativeProcedure>>(`/api/government/procedures/${id}`);
    return response.data;
  },
  
  async update(id: string, data: Partial<CreateProcedureRequest>): Promise<AdministrativeProcedure> {
    const response = await fetchApi<ApiResponse<AdministrativeProcedure>>(`/api/government/procedures/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return response.data;
  },
  
  async delete(id: string): Promise<void> {
    await fetchApi<ApiResponse<void>>(`/api/government/procedures/${id}`, {
      method: 'DELETE',
    });
  },
  
  async getActive(): Promise<AdministrativeProcedure[]> {
    const response = await fetchApi<ApiResponse<AdministrativeProcedure[]>>('/api/government/procedures/active');
    return response.data;
  },
  
  async getByCategory(category: string): Promise<AdministrativeProcedure[]> {
    const response = await fetchApi<ApiResponse<AdministrativeProcedure[]>>(`/api/government/procedures/category/${category}`);
    return response.data;
  },
};

// ============================================
// Application API
// ============================================
export const applicationApi = {
  async create(data: CreateApplicationRequest): Promise<ProcedureApplication> {
    const response = await fetchApi<ApiResponse<ProcedureApplication>>('/api/government/applications', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data;
  },
  
  async list(filters: ApplicationFilters = {}, page = 1, perPage = 20): Promise<PaginatedResponse<ProcedureApplication>> {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('perPage', String(perPage));
    
    if (filters.procedureId) params.set('procedureId', filters.procedureId);
    if (filters.citizenId) params.set('citizenId', filters.citizenId);
    if (filters.status) params.set('status', filters.status);
    if (filters.search) params.set('search', filters.search);
    
    const response = await fetchApi<ApiResponse<PaginatedResponse<ProcedureApplication>>>(`/api/government/applications?${params}`);
    return response.data;
  },
  
  async getById(id: string): Promise<ProcedureApplication> {
    const response = await fetchApi<ApiResponse<ProcedureApplication>>(`/api/government/applications/${id}`);
    return response.data;
  },
  
  async updateStatus(id: string, status: string, notes?: string): Promise<ProcedureApplication> {
    const response = await fetchApi<ApiResponse<ProcedureApplication>>(`/api/government/applications/${id}/status`, {
      method: 'POST',
      body: JSON.stringify({ status, notes }),
    });
    return response.data;
  },
  
  async getPending(): Promise<ProcedureApplication[]> {
    const response = await fetchApi<ApiResponse<ProcedureApplication[]>>('/api/government/applications/pending');
    return response.data;
  },
  
  async getByCitizen(citizenId: string): Promise<ProcedureApplication[]> {
    const response = await fetchApi<ApiResponse<ProcedureApplication[]>>(`/api/government/applications/citizen/${citizenId}`);
    return response.data;
  },
  
  async getStatistics(): Promise<{
    total: number;
    byStatus: Record<string, number>;
    pending: number;
    completedThisMonth: number;
  }> {
    const response = await fetchApi<ApiResponse<{
      total: number;
      byStatus: Record<string, number>;
      pending: number;
      completedThisMonth: number;
    }>>('/api/government/applications/statistics');
    return response.data;
  },
};
