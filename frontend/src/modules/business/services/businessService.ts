import type { Business, BusinessReview, CreateBusinessRequest, CreateReviewRequest, PaginationParams } from '../contracts/businessTypes';
import { apiClient } from '../../../appcore/api-client';

const API_BASE = '/api/business';

export class BusinessService {
  async getBusinesses(params?: PaginationParams): Promise<{ businesses: Business[]; total: number; page: number; totalPages: number }> {
    const queryParams = new URLSearchParams();
    if (params?.page) queryParams.set('page', String(params.page));
    if (params?.pageSize) queryParams.set('pageSize', String(params.pageSize));
    if (params?.sortBy) queryParams.set('sortBy', params.sortBy);
    if (params?.sortOrder) queryParams.set('sortOrder', params.sortOrder);
    
    return apiClient.get(`${API_BASE}/businesses?${queryParams}`);
  }

  async getBusiness(id: string): Promise<Business> {
    return apiClient.get<Business>(`${API_BASE}/businesses/${id}`);
  }

  async getMyBusinesses(): Promise<Business[]> {
    return apiClient.get<Business[]>(`${API_BASE}/my-businesses`);
  }

  async createBusiness(request: CreateBusinessRequest): Promise<Business> {
    return apiClient.post<Business>(`${API_BASE}/businesses`, request);
  }

  async updateBusiness(id: string, request: Partial<CreateBusinessRequest>): Promise<Business> {
    return apiClient.put<Business>(`${API_BASE}/businesses/${id}`, request);
  }

  async getReviews(businessId: string): Promise<BusinessReview[]> {
    return apiClient.get<BusinessReview[]>(`${API_BASE}/businesses/${businessId}/reviews`);
  }

  async createReview(request: CreateReviewRequest): Promise<BusinessReview> {
    return apiClient.post<BusinessReview>(`${API_BASE}/reviews`, request);
  }

  async deleteBusiness(id: string): Promise<void> {
    return apiClient.delete<void>(`${API_BASE}/businesses/${id}`);
  }
}

export const businessService = new BusinessService();
export default businessService;