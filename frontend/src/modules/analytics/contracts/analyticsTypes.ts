export interface AnalyticsReport {
  id: string;
  userId: string;
  type: 'revenue' | 'users' | 'bookings' | 'conversion' | 'performance' | 'custom';
  name: string;
  description?: string;
  dateRange: {
    start: string;
    end: string;
  };
  data: Record<string, unknown>;
  filters?: Record<string, unknown>;
  createdAt: string;
  updatedAt: string;
}

export interface AnalyticsMetric {
  id: string;
  name: string;
  value: number;
  previousValue?: number;
  change?: number;
  changePercent?: number;
  unit?: string;
  timestamp: string;
}

export interface AnalyticsDashboard {
  id: string;
  userId: string;
  name: string;
  widgets: AnalyticsWidget[];
  layout?: string;
  createdAt: string;
  updatedAt: string;
}

export interface AnalyticsWidget {
  id: string;
  type: 'chart' | 'metric' | 'table' | 'map';
  title: string;
  metric?: string;
  chartType?: 'line' | 'bar' | 'pie' | 'area' | 'scatter';
  dataSource?: string;
  config?: Record<string, unknown>;
}

export interface AnalyticsEvent {
  id: string;
  userId: string;
  eventName: string;
  eventType: 'click' | 'view' | 'conversion' | 'error' | 'custom';
  properties?: Record<string, unknown>;
  timestamp: string;
}

export interface AnalyticsState {
  reports: AnalyticsReport[];
  metrics: AnalyticsMetric[];
  dashboards: AnalyticsDashboard[];
  events: AnalyticsEvent[];
  loading: boolean;
  error: string | null;
}

export interface CreateReportRequest {
  type: AnalyticsReport['type'];
  name: string;
  title?: string; // additive: some callers/validators reference `title`; avoiding a behavior change
  description?: string;
  dateRange: {
    start: string;
    end: string;
  };
  filters?: Record<string, unknown>;
}

export interface CreateDashboardRequest {
  name: string;
  widgets: Omit<AnalyticsWidget, 'id'>[];
  layout?: string;
  config?: Record<string, unknown>;
}

export interface TrackEventRequest {
  eventName: string;
  eventType: AnalyticsEvent['eventType'];
  properties?: Record<string, unknown>;
}
