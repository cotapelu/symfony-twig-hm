export interface TripPlan {
  id: string;
  userId: string;
  name: string;
  destinations: string[];
  startDate: string;
  endDate: string;
  activities?: string[];
  status?: 'planning' | 'confirmed' | 'completed' | 'cancelled';
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface TripPlannerState {
  trips: TripPlan[];
  currentTrip: TripPlan | null;
  loading: boolean;
  error: string | null;
  pagination: {
    page: number;
    limit: number;
    total: number;
    totalPages: number;
  };
}
