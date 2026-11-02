export interface VolunteerOpportunity {
  id: string;
  title: string;
  description: string;
  location: string;
  startDate: string;
  endDate: string;
  slots: number;
  filledSlots?: number;
  organization?: string;
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

export interface VolunteerState {
  opportunities: VolunteerOpportunity[];
  loading: boolean;
  error: string | null;
  pagination: {
    page: number;
    limit: number;
    total: number;
    totalPages: number;
  };
}
