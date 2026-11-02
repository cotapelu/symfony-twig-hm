import { describe, it, expect, vi, beforeEach } from 'vitest';
import { BusinessService } from './businessService';
import { apiClient } from '../../../appcore/api-client';

// Mock the apiClient module
vi.mock('../../../appcore/api-client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}));

describe('BusinessService', () => {
  let service: BusinessService;

  beforeEach(() => {
    service = new BusinessService();
    vi.clearAllMocks();
  });

  describe('getBusinesses', () => {
    it('should fetch businesses with default params', async () => {
      const result = { businesses: [], total: 0, page: 1, totalPages: 0 };
      (apiClient.get as any).mockResolvedValue(result);

      const response = await service.getBusinesses();

      expect(response).toEqual(result);
      expect(apiClient.get).toHaveBeenCalledWith('/api/business/businesses?');
    });

    it('should fetch businesses with pagination and sorting', async () => {
      const result = { businesses: [], total: 10, page: 2, totalPages: 2 };
      (apiClient.get as any).mockResolvedValue(result);
      const params = { page: 2, pageSize: 10, sortBy: 'name', sortOrder: 'asc' };

      const response = await service.getBusinesses(params);

      expect(response).toEqual(result);
      const calledWith = (apiClient.get as any).mock.calls[0][0];
      expect(calledWith).toContain('page=2');
      expect(calledWith).toContain('pageSize=10');
      expect(calledWith).toContain('sortBy=name');
      expect(calledWith).toContain('sortOrder=asc');
    });

    it('should handle API errors', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Network error'));

      await expect(service.getBusinesses()).rejects.toThrow('Network error');
    });
  });

  describe('getBusiness', () => {
    it('should fetch business by id', async () => {
      const business = { id: 'b1', name: 'Biz' };
      (apiClient.get as any).mockResolvedValue(business);

      const result = await service.getBusiness('b1');

      expect(result).toEqual(business);
      expect(apiClient.get).toHaveBeenCalledWith('/api/business/businesses/b1');
    });

    it('should throw on failure', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Not found'));

      await expect(service.getBusiness('invalid')).rejects.toThrow('Not found');
    });
  });

  describe('getMyBusinesses', () => {
    it('should fetch my businesses', async () => {
      const businesses = [{ id: 'b1' }];
      (apiClient.get as any).mockResolvedValue(businesses);

      const result = await service.getMyBusinesses();

      expect(result).toEqual(businesses);
      expect(apiClient.get).toHaveBeenCalledWith('/api/business/my-businesses');
    });

    it('should throw on failure', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Auth error'));

      await expect(service.getMyBusinesses()).rejects.toThrow('Auth error');
    });
  });

  describe('createBusiness', () => {
    it('should create business successfully', async () => {
      const request = { name: 'New Biz', type: 'retail' };
      const created = { id: 'b2', ...request };
      (apiClient.post as any).mockResolvedValue(created);

      const result = await service.createBusiness(request);

      expect(result).toEqual(created);
      expect(apiClient.post).toHaveBeenCalledWith('/api/business/businesses', request);
    });

    it('should throw on failure', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Validation failed'));

      await expect(service.createBusiness({ name: 'X' })).rejects.toThrow('Validation failed');
    });
  });

  describe('updateBusiness', () => {
    it('should update business successfully', async () => {
      const updates = { name: 'Updated Biz' };
      const updated = { id: 'b1', ...updates };
      (apiClient.put as any).mockResolvedValue(updated);

      const result = await service.updateBusiness('b1', updates);

      expect(result).toEqual(updated);
      expect(apiClient.put).toHaveBeenCalledWith('/api/business/businesses/b1', updates);
    });

    it('should throw on failure', async () => {
      (apiClient.put as any).mockRejectedValue(new Error('Not allowed'));

      await expect(service.updateBusiness('b1', {})).rejects.toThrow('Not allowed');
    });
  });

  describe('getReviews', () => {
    it('should fetch reviews for a business', async () => {
      const reviews = [{ id: 'r1', rating: 5 }];
      (apiClient.get as any).mockResolvedValue(reviews);

      const result = await service.getReviews('b1');

      expect(result).toEqual(reviews);
      expect(apiClient.get).toHaveBeenCalledWith('/api/business/businesses/b1/reviews');
    });

    it('should throw on failure', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Failed'));

      await expect(service.getReviews('b1')).rejects.toThrow('Failed');
    });
  });

  describe('createReview', () => {
    it('should create review successfully', async () => {
      const request = { businessId: 'b1', rating: 5, comment: 'Great' };
      const review = { id: 'r1', ...request };
      (apiClient.post as any).mockResolvedValue(review);

      const result = await service.createReview(request);

      expect(result).toEqual(review);
      expect(apiClient.post).toHaveBeenCalledWith('/api/business/reviews', request);
    });

    it('should throw on failure', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Duplicate'));

      await expect(service.createReview({ businessId: 'b1', rating: 5 })).rejects.toThrow('Duplicate');
    });
  });

  describe('deleteBusiness', () => {
    it('should delete business successfully', async () => {
      (apiClient.delete as any).mockResolvedValue(undefined);

      await expect(service.deleteBusiness('b1')).resolves.toBeUndefined();
      expect(apiClient.delete).toHaveBeenCalledWith('/api/business/businesses/b1');
    });

    it('should throw on failure', async () => {
      (apiClient.delete as any).mockRejectedValue(new Error('In use'));

      await expect(service.deleteBusiness('b1')).rejects.toThrow('In use');
    });
  });
});
