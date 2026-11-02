export interface Business {
  id: string;
  ownerId: string;
  name: string;
  description?: string;
  category: string;
  address: string;
  phone?: string;
  email?: string;
  website?: string;
  logo?: string;
  isVerified: boolean;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface BusinessReview {
  id: string;
  businessId: string;
  userId: string;
  rating: number;
  comment?: string;
  createdAt: string;
}

export interface BusinessState {
  businesses: Business[];
  selectedBusiness: Business | null;
  reviews: BusinessReview[];
  loading: boolean;
  error: string | null;
  pagination: {
    page: number;
    pageSize: number;
    total: number;
    totalPages: number;
  };
  currentUserId: string | null;
}

export interface PaginationParams {
  page?: number;
  pageSize?: number;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
}

export interface CreateBusinessRequest {
  name: string;
  description?: string;
  category: string;
  address: string;
  phone?: string;
  email?: string;
  website?: string;
}

export interface CreateReviewRequest {
  businessId: string;
  rating: number;
  comment?: string;
}
