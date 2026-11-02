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

const API_BASE = '/api/analytics';
const CACHE_TTL = 5 * 60 * 1000; // 5 minutes
const MAX_RETRIES = 3;

interface CacheEntry<T> {
  data: T;
  timestamp: number;
}

export class AnalyticsService {
  private metricsCache: Map<string, CacheEntry<AnalyticsMetric[]>> = new Map();
  private operationInProgress = false;

  // Input Validation
  private validateReportRequest(request: CreateReportRequest): void {
    if (!request.title || typeof request.title !== 'string') {
      throw new Error('Report title is required');
    }
    if (request.title.length < 3 || request.title.length > 200) {
      throw new Error('Report title must be between 3 and 200 characters');
    }
    if (request.description && request.description.length > 2000) {
      throw new Error('Report description must not exceed 2000 characters');
    }
  }

  private validateDashboardRequest(request: CreateDashboardRequest): void {
    if (!request.name || typeof request.name !== 'string') {
      throw new Error('Dashboard name is required');
    }
    if (request.name.length < 3 || request.name.length > 100) {
      throw new Error('Dashboard name must be between 3 and 100 characters');
    }
    if (request.config && typeof request.config !== 'object') {
      throw new Error('Dashboard config must be a valid object');
    }
  }

  private validateMetricNames(metricNames?: string[]): void {
    if (metricNames && !Array.isArray(metricNames)) {
      throw new Error('Metric names must be an array');
    }
    if (metricNames && metricNames.some(m => typeof m !== 'string')) {
      throw new Error('All metric names must be strings');
    }
    if (metricNames && metricNames.length > 50) {
      throw new Error('Cannot request more than 50 metrics at once');
    }
  }

  private validateEventRequest(request: TrackEventRequest): void {
    if (!request.eventName || typeof request.eventName !== 'string') {
      throw new Error('Event name is required');
    }
    if (request.eventName.length > 100) {
      throw new Error('Event name must not exceed 100 characters');
    }
    if (request.properties && typeof request.properties !== 'object') {
      throw new Error('Event properties must be a valid object');
    }
  }

  // Authorization Check
  private checkPermission(action: 'create' | 'update' | 'delete' | 'read', resource: 'report' | 'dashboard'): boolean {
    const userRole = localStorage.getItem('userRole') || 'guest';
    const permissions: Record<string, string[]> = {
      admin: ['create', 'update', 'delete', 'read'],
      manager: ['create', 'read'],
      user: ['read'],
      guest: ['read']
    };
    return permissions[userRole]?.includes(action) ?? false;
  }

  private requirePermission(action: 'create' | 'update' | 'delete' | 'read', resource: 'report' | 'dashboard'): void {
    if (!this.checkPermission(action, resource)) {
      throw new Error(`Permission denied: Cannot ${action} ${resource}s`);
    }
  }

  // Retry Logic
  private async withRetry<T>(fn: () => Promise<T>, retries = MAX_RETRIES): Promise<T> {
    let lastError: Error | null = null;
    for (let i = 0; i < retries; i++) {
      try {
        return await fn();
      } catch (error) {
        lastError = error as Error;
        if (i < retries - 1) {
          await new Promise(resolve => setTimeout(resolve, Math.pow(2, i) * 1000));
        }
      }
    }
    throw lastError;
  }

  // Cache Management
  private getCachedMetrics(key: string): AnalyticsMetric[] | null {
    const entry = this.metricsCache.get(key);
    if (entry && Date.now() - entry.timestamp < CACHE_TTL) {
      return entry.data;
    }
    return null;
  }

  private setCachedMetrics(key: string, data: AnalyticsMetric[]): void {
    this.metricsCache.set(key, { data, timestamp: Date.now() });
  }

  // Sanitize Event Data
  private sanitizeEventProperties(properties?: Record<string, unknown>): Record<string, unknown> | undefined {
    if (!properties) return undefined;
    const sensitiveKeys = ['password', 'token', 'secret', 'apiKey', 'creditCard'];
    const sanitized: Record<string, unknown> = {};
    for (const [key, value] of Object.entries(properties)) {
      if (!sensitiveKeys.includes(key.toLowerCase())) {
        sanitized[key] = value;
      }
    }
    return sanitized;
  }

  // Reports API
  async getReports(page = 1, limit = 20): Promise<{ data: AnalyticsReport[]; total: number }> {
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/reports?page=${page}&limit=${limit}`)
    );
    if (!response.ok) throw new Error(`Failed to fetch reports: ${response.statusText}`);
    return response.json();
  }

  async getReport(id: string): Promise<AnalyticsReport> {
    if (!id || typeof id !== 'string') {
      throw new Error('Report ID is required');
    }
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/reports/${encodeURIComponent(id)}`)
    );
    if (!response.ok) throw new Error(`Failed to fetch report: ${response.statusText}`);
    return response.json();
  }

  async createReport(request: CreateReportRequest): Promise<AnalyticsReport> {
    this.validateReportRequest(request);
    this.requirePermission('create', 'report');
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/reports`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      })
    );
    if (!response.ok) throw new Error(`Failed to create report: ${response.statusText}`);
    return response.json();
  }

  async deleteReport(id: string): Promise<void> {
    if (!id || typeof id !== 'string') {
      throw new Error('Report ID is required');
    }
    this.requirePermission('delete', 'report');
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/reports/${encodeURIComponent(id)}`, { method: 'DELETE' })
    );
    if (!response.ok) throw new Error(`Failed to delete report: ${response.statusText}`);
  }

  // Metrics API
  async getMetrics(metricNames?: string[]): Promise<AnalyticsMetric[]> {
    this.validateMetricNames(metricNames);
    const cacheKey = metricNames?.sort().join(',') || 'all';
    const cached = this.getCachedMetrics(cacheKey);
    if (cached) return cached;
    
    const params = metricNames ? `?metrics=${encodeURIComponent(metricNames.join(','))}` : '';
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/metrics${params}`)
    );
    if (!response.ok) throw new Error(`Failed to fetch metrics: ${response.statusText}`);
    const data = await response.json();
    this.setCachedMetrics(cacheKey, data);
    return data;
  }

  clearMetricsCache(): void {
    this.metricsCache.clear();
  }

  // Dashboards API
  async getDashboards(page = 1, limit = 20): Promise<{ data: AnalyticsDashboard[]; total: number }> {
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/dashboards?page=${page}&limit=${limit}`)
    );
    if (!response.ok) throw new Error(`Failed to fetch dashboards: ${response.statusText}`);
    return response.json();
  }

  async getDashboard(id: string): Promise<AnalyticsDashboard> {
    if (!id || typeof id !== 'string') {
      throw new Error('Dashboard ID is required');
    }
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/dashboards/${encodeURIComponent(id)}`)
    );
    if (!response.ok) throw new Error(`Failed to fetch dashboard: ${response.statusText}`);
    return response.json();
  }

  async createDashboard(request: CreateDashboardRequest): Promise<AnalyticsDashboard> {
    this.validateDashboardRequest(request);
    this.requirePermission('create', 'dashboard');
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/dashboards`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      })
    );
    if (!response.ok) throw new Error(`Failed to create dashboard: ${response.statusText}`);
    return response.json();
  }

  async updateDashboard(id: string, request: CreateDashboardRequest): Promise<AnalyticsDashboard> {
    if (!id || typeof id !== 'string') {
      throw new Error('Dashboard ID is required');
    }
    this.validateDashboardRequest(request);
    this.requirePermission('update', 'dashboard');
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/dashboards/${encodeURIComponent(id)}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      })
    );
    if (!response.ok) throw new Error(`Failed to update dashboard: ${response.statusText}`);
    return response.json();
  }

  async deleteDashboard(id: string): Promise<void> {
    if (!id || typeof id !== 'string') {
      throw new Error('Dashboard ID is required');
    }
    this.requirePermission('delete', 'dashboard');
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/dashboards/${encodeURIComponent(id)}`, { method: 'DELETE' })
    );
    if (!response.ok) throw new Error(`Failed to delete dashboard: ${response.statusText}`);
  }

  // Events API
  async trackEvent(request: TrackEventRequest): Promise<AnalyticsEvent> {
    this.validateEventRequest(request);
    const sanitizedRequest = {
      ...request,
      properties: this.sanitizeEventProperties(request.properties)
    };
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/events`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(sanitizedRequest)
      })
    );
    if (!response.ok) throw new Error(`Failed to track event: ${response.statusText}`);
    return response.json();
  }

  async getEvents(eventName?: string, limit = 100): Promise<AnalyticsEvent[]> {
    const params = new URLSearchParams();
    if (eventName) params.append('event', eventName);
    params.append('limit', Math.min(limit, 1000).toString());
    const response = await this.withRetry(() => 
      fetch(`${API_BASE}/events?${params}`)
    );
    if (!response.ok) throw new Error(`Failed to fetch events: ${response.statusText}`);
    return response.json();
  }

  // Utility
  isOperationInProgress(): boolean {
    return this.operationInProgress;
  }

  setOperationInProgress(value: boolean): void {
    this.operationInProgress = value;
  }
}

// Exported for testing only
export const __testing__ = {
  clearCache: () => {
    // No module-level cache to clear, instance caches are fresh per instance
  }
};

export const analyticsService = new AnalyticsService();
export default analyticsService;
