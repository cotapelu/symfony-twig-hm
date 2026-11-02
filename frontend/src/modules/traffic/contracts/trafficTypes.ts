export interface TrafficInfo {
  id: string;
  location: string;
  description: string;
  severity: 'low' | 'medium' | 'high';
  updatedAt: string;
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface TrafficState {
  alerts: TrafficInfo[];
  loading: boolean;
  error: string | null;
  pagination: {
    page: number;
    limit: number;
    total: number;
    totalPages: number;
  };
}
