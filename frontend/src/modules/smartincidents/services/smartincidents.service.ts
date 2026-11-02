// Smart Incident Reporting API Service - Handles all API calls to PHP Symfony backend

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
  ApiResponse,
  PaginatedResponse,
} from '../contracts/smartincidents.types';

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
// File Upload Helper
// ============================================
async function uploadFile(file: File, incidentId?: number): Promise<IncidentAttachment> {
  const formData = new FormData();
  formData.append('file', file);
  if (incidentId) {
    formData.append('incidentId', String(incidentId));
  }
  
  const token = localStorage.getItem('auth_token');
  const url = `${API_BASE_URL}/api/smart-incidents/upload`;
  
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      ...(token && { 'Authorization': `Bearer ${token}` }),
    },
    body: formData,
  });
  
  if (!response.ok) {
    const error = await response.json().catch(() => ({ error: 'Upload failed' }));
    throw new Error(error.error || `Upload failed: ${response.statusText}`);
  }
  
  const result = await response.json();
  return result.data;
}

// ============================================
// Incident API
// ============================================
export const smartIncidentApi = {
  async create(data: CreateIncidentRequest, files?: File[]): Promise<SmartIncident> {
    // First create the incident
    const response = await fetchApi<ApiResponse<SmartIncident>>('/api/smart-incidents', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    
    const incident = response.data;
    
    // Upload files if any
    if (files && files.length > 0) {
      const uploadPromises = files.map(file => uploadFile(file, incident.id));
      const attachments = await Promise.all(uploadPromises);
      incident.attachments = attachments;
    }
    
    return incident;
  },
  
  async getById(id: number): Promise<SmartIncident> {
    const response = await fetchApi<ApiResponse<SmartIncident>>(`/api/smart-incidents/${id}`);
    return response.data;
  },
  
  async list(filters: IncidentFilters = {}, page = 1, perPage = 20): Promise<PaginatedResponse<SmartIncident>> {
    const params = new URLSearchParams();
    params.set('page', String(page));
    params.set('perPage', String(perPage));
    
    if (filters.search) params.set('search', filters.search);
    if (filters.incidentType) params.set('incidentType', filters.incidentType);
    if (filters.severity) params.set('severity', filters.severity);
    if (filters.status) params.set('status', filters.status);
    if (filters.wardCode) params.set('wardCode', filters.wardCode);
    if (filters.assignedOfficerId) params.set('assignedOfficerId', filters.assignedOfficerId);
    if (filters.dateFrom) params.set('dateFrom', filters.dateFrom);
    if (filters.dateTo) params.set('dateTo', filters.dateTo);
    if (filters.reporterAnonymous !== undefined) params.set('reporterAnonymous', String(filters.reporterAnonymous));
    if (filters.hasAttachments !== undefined) params.set('hasAttachments', String(filters.hasAttachments));
    
    const response = await fetchApi<PaginatedResponse<SmartIncident>>(`/api/smart-incidents?${params}`);
    return response;
  },
  
  async search(query: string, limit = 20): Promise<SmartIncident[]> {
    const response = await fetchApi<ApiResponse<SmartIncident[]>>(
      `/api/smart-incidents/search?q=${encodeURIComponent(query)}&limit=${limit}`
    );
    return response.data;
  },
  
  async update(id: number, data: UpdateIncidentRequest): Promise<SmartIncident> {
    const response = await fetchApi<ApiResponse<SmartIncident>>(`/api/smart-incidents/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return response.data;
  },
  
  async updateStatus(id: number, status: string, notes?: string): Promise<SmartIncident> {
    const response = await fetchApi<ApiResponse<SmartIncident>>(`/api/smart-incidents/${id}/status`, {
      method: 'POST',
      body: JSON.stringify({ status, notes }),
    });
    return response.data;
  },
  
  async assign(id: number, officerId: string, team?: string): Promise<SmartIncident> {
    const response = await fetchApi<ApiResponse<SmartIncident>>(`/api/smart-incidents/${id}/assign`, {
      method: 'POST',
      body: JSON.stringify({ officerId, team }),
    });
    return response.data;
  },
  
  async resolve(id: number, summary: string): Promise<SmartIncident> {
    const response = await fetchApi<ApiResponse<SmartIncident>>(`/api/smart-incidents/${id}/resolve`, {
      method: 'POST',
      body: JSON.stringify({ summary }),
    });
    return response.data;
  },
  
  async escalate(id: number, reason: string): Promise<SmartIncident> {
    const response = await fetchApi<ApiResponse<SmartIncident>>(`/api/smart-incidents/${id}/escalate`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    });
    return response.data;
  },
  
  async close(id: number): Promise<SmartIncident> {
    const response = await fetchApi<ApiResponse<SmartIncident>>(`/api/smart-incidents/${id}/close`, {
      method: 'POST',
    });
    return response.data;
  },
  
  async getNearby(latitude: number, longitude: number, radiusKm = 1, limit = 20): Promise<SmartIncident[]> {
    const response = await fetchApi<ApiResponse<SmartIncident[]>>(
      `/api/smart-incidents/nearby?lat=${latitude}&lon=${longitude}&radius=${radiusKm}&limit=${limit}`
    );
    return response.data;
  },
  
  async getByOfficer(officerId: string, status?: string): Promise<SmartIncident[]> {
    const params = new URLSearchParams();
    if (status) params.set('status', status);
    
    const response = await fetchApi<ApiResponse<SmartIncident[]>>(
      `/api/smart-incidents/officer/${officerId}?${params}`
    );
    return response.data;
  },
  
  async getStatistics(filters: Pick<IncidentFilters, 'wardCode' | 'dateFrom' | 'dateTo'> = {}): Promise<IncidentStatistics> {
    const params = new URLSearchParams();
    if (filters.wardCode) params.set('wardCode', filters.wardCode);
    if (filters.dateFrom) params.set('dateFrom', filters.dateFrom);
    if (filters.dateTo) params.set('dateTo', filters.dateTo);
    
    const response = await fetchApi<ApiResponse<IncidentStatistics>>(`/api/smart-incidents/statistics?${params}`);
    return response.data;
  },
  
  async getActivityLog(id: number): Promise<IncidentActivity[]> {
    const response = await fetchApi<ApiResponse<IncidentActivity[]>>(`/api/smart-incidents/${id}/activity`);
    return response.data;
  },
  
  // ============================================
  // Attachments
  // ============================================
  async addAttachment(incidentId: number, file: File, description?: string): Promise<IncidentAttachment> {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('incidentId', String(incidentId));
    if (description) formData.append('description', description);
    
    const token = localStorage.getItem('auth_token');
    const response = await fetch(`${API_BASE_URL}/api/smart-incidents/${incidentId}/attachments`, {
      method: 'POST',
      headers: {
        ...(token && { 'Authorization': `Bearer ${token}` }),
      },
      body: formData,
    });
    
    if (!response.ok) {
      throw new Error('Failed to add attachment');
    }
    
    const result = await response.json();
    return result.data;
  },
  
  async getAttachments(incidentId: number): Promise<IncidentAttachment[]> {
    const response = await fetchApi<ApiResponse<IncidentAttachment[]>>(`/api/smart-incidents/${incidentId}/attachments`);
    return response.data;
  },
  
  async deleteAttachment(incidentId: number, attachmentId: number): Promise<void> {
    await fetchApi<ApiResponse<void>>(`/api/smart-incidents/${incidentId}/attachments/${attachmentId}`, {
      method: 'DELETE',
    });
  },
  
  // ============================================
  // Messages/Chat
  // ============================================
  async sendMessage(incidentId: number, content: string, attachmentUrl?: string): Promise<IncidentMessage> {
    const response = await fetchApi<ApiResponse<IncidentMessage>>(`/api/smart-incidents/${incidentId}/messages`, {
      method: 'POST',
      body: JSON.stringify({ content, attachmentUrl }),
    });
    return response.data;
  },
  
  async getMessages(incidentId: number): Promise<IncidentMessage[]> {
    const response = await fetchApi<ApiResponse<IncidentMessage[]>>(`/api/smart-incidents/${incidentId}/messages`);
    return response.data;
  },
  
  async markMessagesRead(incidentId: number): Promise<void> {
    await fetchApi<ApiResponse<void>>(`/api/smart-incidents/${incidentId}/messages/read`, {
      method: 'POST',
    });
  },
  
  // ============================================
  // Map & Geospatial
  // ============================================
  async getMapMarkers(bounds?: MapBounds, filters: IncidentFilters = {}): Promise<MapIncidentMarker[]> {
    const params = new URLSearchParams();
    
    if (bounds) {
      params.set('north', String(bounds.north));
      params.set('south', String(bounds.south));
      params.set('east', String(bounds.east));
      params.set('west', String(bounds.west));
    }
    
    if (filters.incidentType) params.set('incidentType', filters.incidentType);
    if (filters.severity) params.set('severity', filters.severity);
    if (filters.status) params.set('status', filters.status);
    if (filters.dateFrom) params.set('dateFrom', filters.dateFrom);
    if (filters.dateTo) params.set('dateTo', filters.dateTo);
    
    const response = await fetchApi<ApiResponse<MapIncidentMarker[]>>(`/api/smart-incidents/map/markers?${params}`);
    return response.data;
  },
  
  async getHeatmapData(
    bounds?: MapBounds,
    timeframe: '24h' | '7d' | '30d' | '90d' = '30d'
  ): Promise<IncidentHeatmapData> {
    const params = new URLSearchParams();
    params.set('timeframe', timeframe);
    
    if (bounds) {
      params.set('north', String(bounds.north));
      params.set('south', String(bounds.south));
      params.set('east', String(bounds.east));
      params.set('west', String(bounds.west));
    }
    
    const response = await fetchApi<ApiResponse<IncidentHeatmapData>>(`/api/smart-incidents/map/heatmap?${params}`);
    return response.data;
  },
  
  async getHotspots(limit = 10, timeframe: '7d' | '30d' | '90d' = '30d'): Promise<{
    latitude: number;
    longitude: number;
    radius: number;
    incidentCount: number;
    incidentTypes: string[];
    wardName: string;
  }[]> {
    const response = await fetchApi<ApiResponse<{
      latitude: number;
      longitude: number;
      radius: number;
      incidentCount: number;
      incidentTypes: string[];
      wardName: string;
    }[]>>(`/api/smart-incidents/map/hotspots?limit=${limit}&timeframe=${timeframe}`);
    return response.data;
  },
  
  // ============================================
  // Voice & AI Features
  // ============================================
  async transcribeAudio(audioFile: File): Promise<{ text: string; confidence: number }> {
    const formData = new FormData();
    formData.append('audio', audioFile);
    
    const token = localStorage.getItem('auth_token');
    const response = await fetch(`${API_BASE_URL}/api/smart-incidents/transcribe`, {
      method: 'POST',
      headers: {
        ...(token && { 'Authorization': `Bearer ${token}` }),
      },
      body: formData,
    });
    
    if (!response.ok) {
      throw new Error('Transcription failed');
    }
    
    const result = await response.json();
    return result.data;
  },
  
  async classifyIncident(description: string): Promise<{
    incidentType: string;
    severity: string;
    confidence: number;
    suggestions: string[];
  }> {
    const response = await fetchApi<ApiResponse<{
      incidentType: string;
      severity: string;
      confidence: number;
      suggestions: string[];
    }>>('/api/smart-incidents/classify', {
      method: 'POST',
      body: JSON.stringify({ description }),
    });
    return response.data;
  },
};
