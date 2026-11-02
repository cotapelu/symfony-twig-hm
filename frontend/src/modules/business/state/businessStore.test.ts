import { describe, it, expect, vi, beforeEach } from 'vitest';
import { businessStore } from './businessStore';
import { businessService } from '../services/businessService';
import type { Business, BusinessReview, PaginationParams } from '../contracts/businessTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = businessStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockBusiness = (overrides: Partial<Business> = {}): Business => ({
  id: `biz-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test Business',
  description: 'A test business',
  category: 'restaurant',
  address: '123 Main St',
  phone: '123-456-7890',
  email: 'test@example.com',
  website: 'https://example.com',
  ownerId: `user-${Math.random().toString(36).substr(2, 5)}`,
  rating: 4.5,
  reviewCount: 10,
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
  ...overrides
});

const mockReview = (overrides: Partial<BusinessReview> = {}): BusinessReview => ({
  id: `rev-${Math.random().toString(36).substr(2, 5)}`,
  businessId: 'biz-1',
  userId: 'user-1',
  rating: 5,
  comment: 'Great!',
  createdAt: new Date().toISOString(),
  ...overrides
});

const mockCreateRequest = (overrides: Partial<Business> = {}): any => ({
  name: 'New Business',
  description: 'New desc',
  category: 'retail',
  address: '456 Elm St',
  phone: '987-654-3210',
  email: 'new@example.com',
  website: 'https://new.example.com',
  ...overrides
});

const mockUpdateRequest = (overrides: Partial<Business> = {}): any => ({
  name: 'Updated Business',
  description: 'Updated desc',
  category: 'service',
  address: '789 Oak St',
  phone: '555-123-4567',
  email: 'updated@example.com',
  website: 'https://updated.example.com',
  ...overrides
});

describe('BusinessStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Reset store to initial state before each test
    businessStore.reset();
    // Set no current user by default
    getState().currentUserId = null;
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.businesses).toEqual([]);
      expect(state.selectedBusiness).toBeNull();
      expect(state.reviews).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({
        page: 1,
        pageSize: 10,
        total: 0,
        totalPages: 0
      });
      expect(state.currentUserId).toBeNull();
    });
  });

  describe('loadBusinesses', () => {
    it('loads businesses successfully and updates pagination', async () => {
      const businesses = [mockBusiness(), mockBusiness()];
      const result = {
        businesses,
        page: 1,
        total: 2,
        totalPages: 1
      };
      vi.spyOn(businessService, 'getBusinesses').mockResolvedValue(result);

      await businessStore.loadBusinesses();

      expect(businessService.getBusinesses).toHaveBeenCalledWith(undefined);
      const state = getState();
      expect(state.businesses).toEqual(businesses);
      expect(state.pagination).toEqual({
        page: 1,
        pageSize: 10,
        total: 2,
        totalPages: 1
      });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('uses provided pagination params', async () => {
      const businesses = [mockBusiness()];
      const params: PaginationParams = { page: 2, pageSize: 20 };
      const result = { businesses, page: 2, total: 20, totalPages: 1 };
      vi.spyOn(businessService, 'getBusinesses').mockResolvedValue(result);

      await businessStore.loadBusinesses(params);

      expect(businessService.getBusinesses).toHaveBeenCalledWith(params);
      const state = getState();
      expect(state.pagination.page).toBe(2);
      expect(state.pagination.pageSize).toBe(20);
    });

    it('sets loading during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(businessService, 'getBusinesses').mockReturnValue(p as any);

      const loadPromise = businessStore.loadBusinesses();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise({ businesses: [mockBusiness()], page: 1, total: 1, totalPages: 1 });
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch businesses');
      vi.spyOn(businessService, 'getBusinesses').mockRejectedValue(error);

      await businessStore.loadBusinesses();

      const state = getState();
      expect(state.error).toBe('Failed to fetch businesses');
      expect(state.loading).toBe(false);
      expect(state.businesses).toEqual([]);
    });
  });

  describe('loadMyBusinesses', () => {
    it('loads user businesses successfully', async () => {
      const businesses = [mockBusiness(), mockBusiness()];
      vi.spyOn(businessService, 'getMyBusinesses').mockResolvedValue(businesses);

      await businessStore.loadMyBusinesses();

      expect(businessService.getMyBusinesses).toHaveBeenCalled();
      const state = getState();
      expect(state.businesses).toEqual(businesses);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      const error = new Error('Auth required');
      vi.spyOn(businessService, 'getMyBusinesses').mockRejectedValue(error);

      await businessStore.loadMyBusinesses();

      const state = getState();
      expect(state.error).toBe('Auth required');
      expect(state.loading).toBe(false);
    });
  });

  describe('loadBusiness', () => {
    it('loads business and sets selectedBusiness', async () => {
      const biz = mockBusiness({ id: 'biz-1', ownerId: 'user-1' });
      vi.spyOn(businessService, 'getBusiness').mockResolvedValue(biz);

      businessStore.setCurrentUser('user-1');
      await businessStore.loadBusiness('biz-1');

      expect(businessService.getBusiness).toHaveBeenCalledWith('biz-1');
      const state = getState();
      expect(state.selectedBusiness).toEqual({ ...biz, isOwner: true });
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets isOwner false when currentUserId does not match', async () => {
      const biz = mockBusiness({ id: 'biz-1', ownerId: 'owner-99' });
      vi.spyOn(businessService, 'getBusiness').mockResolvedValue(biz);

      businessStore.setCurrentUser('user-1');
      await businessStore.loadBusiness('biz-1');

      const state = getState();
      expect(state.selectedBusiness?.isOwner).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Not found');
      vi.spyOn(businessService, 'getBusiness').mockRejectedValue(error);

      await businessStore.loadBusiness('biz-1');

      const state = getState();
      expect(state.error).toBe('Not found');
      expect(state.selectedBusiness).toBeNull();
    });
  });

  describe('createBusiness', () => {
    it('creates business and adds to list', async () => {
      const newBiz = mockBusiness({ id: 'new-biz' });
      vi.spyOn(businessService, 'createBusiness').mockResolvedValue(newBiz);

      const request = mockCreateRequest();
      const result = await businessStore.createBusiness(request);

      expect(result).toEqual(newBiz);
      expect(businessService.createBusiness).toHaveBeenCalledWith(request);
      const state = getState();
      expect(state.businesses).toContainEqual(newBiz);
    });

    it('sets loading during request', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(businessService, 'createBusiness').mockReturnValue(p as any);

      const request = mockCreateRequest();
      const loadPromise = businessStore.createBusiness(request);

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise(mockBusiness({ id: 'new-biz' }));
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('throws and sets error on failure', async () => {
      const error = new Error('Create failed');
      vi.spyOn(businessService, 'createBusiness').mockRejectedValue(error);

      const request = mockCreateRequest();
      await expect(businessStore.createBusiness(request)).rejects.toThrow('Create failed');

      const state = getState();
      expect(state.error).toBe('Create failed');
      // businesses list remains unchanged (empty initially)
      expect(state.businesses).toEqual([]);
    });
  });

  describe('updateBusiness', () => {
    it('updates business as owner and updates list', async () => {
      const existing = mockBusiness({ id: 'biz-1', ownerId: 'user-1' });
      const updated = { ...existing, name: 'Updated Name' };
      vi.spyOn(businessService, 'updateBusiness').mockResolvedValue(updated);
      vi.spyOn(businessService, 'getBusinesses').mockResolvedValue({
        businesses: [existing],
        page: 1,
        total: 1,
        totalPages: 1
      });
      await businessStore.loadBusinesses();

      businessStore.setCurrentUser('user-1');
      const result = await businessStore.updateBusiness('biz-1', mockUpdateRequest());

      expect(result).toEqual(updated);
      expect(businessService.updateBusiness).toHaveBeenCalledWith('biz-1', expect.objectContaining(mockUpdateRequest()));
      const state = getState();
      expect(state.businesses[0]).toEqual(updated);
    });

    it('updates selectedBusiness when it is the one being updated', async () => {
      const existing = mockBusiness({ id: 'biz-1', ownerId: 'user-1' });
      const updated = { ...existing, name: 'Updated Name' };
      vi.spyOn(businessService, 'updateBusiness').mockResolvedValue(updated);
      vi.spyOn(businessService, 'getBusinesses').mockResolvedValue({
        businesses: [existing],
        page: 1,
        total: 1,
        totalPages: 1
      });
      await businessStore.loadBusinesses();

      businessStore.setCurrentUser('user-1');
      businessStore.selectBusiness(existing);
      const result = await businessStore.updateBusiness('biz-1', mockUpdateRequest());

      expect(result).toEqual(updated);
      expect(getState().selectedBusiness).toEqual(updated);
    });

    it('throws when not owner', async () => {
      const existing = mockBusiness({ id: 'biz-1', ownerId: 'owner-1' });
      vi.spyOn(businessService, 'getBusinesses').mockResolvedValue({
        businesses: [existing],
        page: 1,
        total: 1,
        totalPages: 1
      });
      await businessStore.loadBusinesses();

      businessStore.setCurrentUser('user-1');
      await expect(businessStore.updateBusiness('biz-1', mockUpdateRequest()))
        .rejects.toThrow('Unauthorized: You do not own this business');
      // unauthorized throws before any state update, so error remains null
      const state = getState();
      expect(state.error).toBeNull();
    });

    it('throws when business not found', async () => {
      // Empty list, so business not found
      businessStore.setCurrentUser('user-1');
      await expect(businessStore.updateBusiness('biz-1', mockUpdateRequest()))
        .rejects.toThrow('Unauthorized: You do not own this business');
    });

    it('handles service error', async () => {
      const existing = mockBusiness({ id: 'biz-1', ownerId: 'user-1' });
      vi.spyOn(businessService, 'getBusinesses').mockResolvedValue({
        businesses: [existing],
        page: 1,
        total: 1,
        totalPages: 1
      });
      await businessStore.loadBusinesses();

      const error = new Error('Update failed');
      vi.spyOn(businessService, 'updateBusiness').mockRejectedValue(error);

      businessStore.setCurrentUser('user-1');
      await expect(businessStore.updateBusiness('biz-1', mockUpdateRequest())).rejects.toThrow('Update failed');

      const state = getState();
      expect(state.error).toBe('Update failed');
    });
  });

  describe('loadReviews', () => {
    it('loads reviews for business', async () => {
      const reviews = [mockReview(), mockReview()];
      vi.spyOn(businessService, 'getReviews').mockResolvedValue(reviews);

      await businessStore.loadReviews('biz-1');

      expect(businessService.getReviews).toHaveBeenCalledWith('biz-1');
      const state = getState();
      expect(state.reviews).toEqual(reviews);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('handles error', async () => {
      const error = new Error('Reviews fetch failed');
      vi.spyOn(businessService, 'getReviews').mockRejectedValue(error);

      await businessStore.loadReviews('biz-1');

      const state = getState();
      expect(state.error).toBe('Reviews fetch failed');
      expect(state.reviews).toEqual([]);
    });
  });

  describe('createReview', () => {
    it('creates review and adds to list', async () => {
      const review = mockReview({ id: 'new-rev' });
      vi.spyOn(businessService, 'createReview').mockResolvedValue(review);

      const result = await businessStore.createReview('biz-1', 5, 'Awesome!');

      expect(result).toEqual(review);
      expect(businessService.createReview).toHaveBeenCalledWith({ businessId: 'biz-1', rating: 5, comment: 'Awesome!' });
      const state = getState();
      expect(state.reviews).toContainEqual(review);
    });

    it('handles error', async () => {
      const error = new Error('Create review failed');
      vi.spyOn(businessService, 'createReview').mockRejectedValue(error);

      await expect(businessStore.createReview('biz-1', 3)).rejects.toThrow('Create review failed');

      const state = getState();
      expect(state.error).toBe('Create review failed');
    });
  });

  describe('deleteBusiness', () => {
    it('deletes business as owner', async () => {
      const biz = mockBusiness({ id: 'biz-1', ownerId: 'user-1' });
      vi.spyOn(businessService, 'getBusinesses').mockResolvedValue({
        businesses: [biz],
        page: 1,
        total: 1,
        totalPages: 1
      });
      await businessStore.loadBusinesses();

      vi.spyOn(businessService, 'deleteBusiness').mockResolvedValue(undefined);
      businessStore.setCurrentUser('user-1');
      await businessStore.deleteBusiness('biz-1');

      expect(businessService.deleteBusiness).toHaveBeenCalledWith('biz-1');
      const state = getState();
      expect(state.businesses).toHaveLength(0);
    });

    it('throws when not owner', async () => {
      const biz = mockBusiness({ id: 'biz-1', ownerId: 'owner-1' });
      vi.spyOn(businessService, 'getBusinesses').mockResolvedValue({
        businesses: [biz],
        page: 1,
        total: 1,
        totalPages: 1
      });
      await businessStore.loadBusinesses();

      businessStore.setCurrentUser('user-1');
      await expect(businessStore.deleteBusiness('biz-1'))
        .rejects.toThrow('Unauthorized: You do not own this business');
      // unauthorized throws before any state update; error remains null
      const state = getState();
      expect(state.error).toBeNull();
      expect(state.businesses).toHaveLength(1); // unchanged
    });

    it('handles error', async () => {
      const biz = mockBusiness({ id: 'biz-1', ownerId: 'user-1' });
      vi.spyOn(businessService, 'getBusinesses').mockResolvedValue({
        businesses: [biz],
        page: 1,
        total: 1,
        totalPages: 1
      });
      await businessStore.loadBusinesses();

      const error = new Error('Delete failed');
      vi.spyOn(businessService, 'deleteBusiness').mockRejectedValue(error);

      businessStore.setCurrentUser('user-1');
      await expect(businessStore.deleteBusiness('biz-1')).rejects.toThrow('Delete failed');

      const state = getState();
      expect(state.error).toBe('Delete failed');
      expect(state.businesses).toHaveLength(1);
    });
  });

  describe('setCurrentUser', () => {
    it('sets currentUserId', () => {
      businessStore.setCurrentUser('user-42');
      expect(getState().currentUserId).toBe('user-42');
    });
  });

  describe('selectBusiness', () => {
    it('selects a business', () => {
      const biz = mockBusiness();
      businessStore.selectBusiness(biz);
      expect(getState().selectedBusiness).toEqual(biz);
    });

    it('clears selection when null', () => {
      businessStore.selectBusiness(mockBusiness());
      businessStore.selectBusiness(null);
      expect(getState().selectedBusiness).toBeNull();
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      const error = new Error('test error');
      vi.spyOn(businessService, 'getBusinesses').mockRejectedValue(error);
      await businessStore.loadBusinesses(); // resolves, but sets error
      expect(getState().error).toBe('test error');
      businessStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Populate some state
      const biz = mockBusiness();
      vi.spyOn(businessService, 'getBusinesses').mockResolvedValue({
        businesses: [biz],
        page: 1, total: 1, totalPages: 1
      });
      await businessStore.loadBusinesses();
      businessStore.setCurrentUser('user-1');
      businessStore.selectBusiness(biz);
      expect(getState().businesses).toHaveLength(1);
      expect(getState().currentUserId).toBe('user-1');
      expect(getState().selectedBusiness).not.toBeNull();

      businessStore.reset();
      const state = getState();
      expect(state.businesses).toEqual([]);
      expect(state.selectedBusiness).toBeNull();
      expect(state.reviews).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({
        page: 1,
        pageSize: 10,
        total: 0,
        totalPages: 0
      });
      expect(state.currentUserId).toBeNull();
    });
  });
});
