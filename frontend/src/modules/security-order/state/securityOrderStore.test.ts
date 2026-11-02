import { describe, it, expect, vi, beforeEach } from 'vitest';
import { get } from 'svelte/store';

// Mock service before importing store
const mockGetOrders = vi.fn();

vi.mock('../services/securityOrderService', () => ({
  securityOrderService: {
    getOrders: mockGetOrders
  }
}));

// Import store after mocking
const { securityOrderStore, securityOrders, securityOrderLoading, securityOrderPagination } = await import('./securityOrderStore');

describe('securityOrderStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    securityOrderStore.reset();
  });

  describe('initial state', () => {
    it('should have correct initial state', () => {
      const state = get(securityOrderStore);
      expect(state.orders).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });

    it('should expose derived stores', () => {
      expect(get(securityOrders)).toEqual([]);
      expect(get(securityOrderLoading)).toBe(false);
      expect(get(securityOrderPagination)).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });
  });

  describe('loadOrders', () => {
    it('should load orders successfully', async () => {
      const mockResponse = {
        items: [{ id: '1', status: 'pending', type: 'order' }],
        page: 1,
        limit: 20,
        total: 1,
        totalPages: 1
      };
      mockGetOrders.mockResolvedValue(mockResponse);

      await securityOrderStore.loadOrders();

      const state = get(securityOrderStore);
      expect(state.orders).toEqual(mockResponse.items);
      expect(state.loading).toBe(false);
      expect(state.pagination).toEqual({
        page: 1,
        limit: 20,
        total: 1,
        totalPages: 1
      });
    });

    it('should handle load orders error', async () => {
      mockGetOrders.mockRejectedValue(new Error('Network error'));

      await securityOrderStore.loadOrders();

      const state = get(securityOrderStore);
      expect(state.error).toBe('Network error');
      expect(state.loading).toBe(false);
    });

    it('should load orders with custom pagination', async () => {
      const mockResponse = {
        items: [{ id: '1' }],
        page: 2,
        limit: 10,
        total: 50,
        totalPages: 5
      };
      mockGetOrders.mockResolvedValue(mockResponse);

      await securityOrderStore.loadOrders(2, 10);

      expect(mockGetOrders).toHaveBeenCalledWith(2, 10);
      const state = get(securityOrderStore);
      expect(state.pagination.page).toBe(2);
      expect(state.pagination.limit).toBe(10);
    });
  });

  describe('loadMore', () => {
    it('should load next page when available', async () => {
      // Set initial state with pagination info
      const initialResponse = {
        items: [{ id: '1' }],
        page: 1,
        limit: 20,
        total: 50,
        totalPages: 3
      };
      mockGetOrders.mockResolvedValueOnce(initialResponse);
      await securityOrderStore.loadOrders();

      // Load more
      const moreResponse = {
        items: [{ id: '2' }],
        page: 2,
        limit: 20,
        total: 50,
        totalPages: 3
      };
      mockGetOrders.mockResolvedValueOnce(moreResponse);
      await securityOrderStore.loadMore();

      expect(mockGetOrders).toHaveBeenCalledTimes(2);
      const state = get(securityOrderStore);
      expect(state.pagination.page).toBe(2);
    });

    it('should not load more when on last page', async () => {
      const mockResponse = {
        items: [{ id: '1' }],
        page: 3,
        limit: 20,
        total: 50,
        totalPages: 3
      };
      mockGetOrders.mockResolvedValue(mockResponse);
      await securityOrderStore.loadOrders();

      await securityOrderStore.loadMore();

      // Should only be called once (during loadOrders)
      expect(mockGetOrders).toHaveBeenCalledTimes(1);
    });
  });

  describe('clearError and reset', () => {
    it('should clear error', async () => {
      mockGetOrders.mockRejectedValue(new Error('Error'));
      await securityOrderStore.loadOrders();
      
      securityOrderStore.clearError();
      expect(get(securityOrderStore).error).toBeNull();
    });

    it('should reset to initial state', async () => {
      mockGetOrders.mockResolvedValue({ items: [{ id: '1' }], page: 1, limit: 20, total: 1, totalPages: 1 });
      await securityOrderStore.loadOrders();
      
      securityOrderStore.reset();
      
      const state = get(securityOrderStore);
      expect(state.orders).toEqual([]);
      expect(state.pagination).toEqual({ page: 1, limit: 20, total: 0, totalPages: 0 });
    });
  });
});
