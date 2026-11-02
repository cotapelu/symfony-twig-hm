export interface SecurityOrder {
  id: string;
  userId: string;
  location: string;
  serviceType: string;
  status: 'pending' | 'confirmed' | 'completed';
  scheduledAt: string;
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface SecurityOrderState {
  orders: SecurityOrder[];
  loading: boolean;
  error: string | null;
  pagination: {
    page: number;
    limit: number;
    total: number;
    totalPages: number;
  };
}
