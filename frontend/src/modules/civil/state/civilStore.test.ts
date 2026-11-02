import { describe, it, expect, vi, beforeEach } from 'vitest';
import { get } from 'svelte/store';

// Mock service before importing store
const mockGetServices = vi.fn();
const mockGetMyRequests = vi.fn();
const mockGetMyRequest = vi.fn();
const mockSubmitRequest = vi.fn();
const mockCancelRequest = vi.fn();

vi.mock('../services/civilService', () => ({
  civilServiceModule: {
    getServices: mockGetServices,
    getMyRequests: mockGetMyRequests,
    getMyRequest: mockGetMyRequest,
    submitRequest: mockSubmitRequest,
    cancelRequest: mockCancelRequest
  }
}));

// Import store after mocking
const { civilStore, civilServices, civilRequests, civilLoading } = await import('./civilStore');

describe('civilStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    civilStore.reset();
  });

  describe('initial state', () => {
    it('should have correct initial state', () => {
      const state = get(civilStore);
      expect(state.services).toEqual([]);
      expect(state.requests).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.currentUserId).toBeNull();
      expect(state.operationInProgress).toBe(false);
    });

    it('should expose derived stores', () => {
      expect(get(civilServices)).toEqual([]);
      expect(get(civilRequests)).toEqual([]);
      expect(get(civilLoading)).toBe(false);
    });
  });

  describe('setCurrentUser', () => {
    it('should set current user id', () => {
      civilStore.setCurrentUser('user123');
      const state = get(civilStore);
      expect(state.currentUserId).toBe('user123');
    });
  });

  describe('loadServices', () => {
    it('should load services successfully', async () => {
      const mockServices = [{ id: '1', name: 'Birth Certificate' }];
      mockGetServices.mockResolvedValue(mockServices);

      await civilStore.loadServices();

      const state = get(civilStore);
      expect(state.services).toEqual(mockServices);
      expect(state.loading).toBe(false);
      expect(state.operationInProgress).toBe(false);
    });

    it('should handle load services error', async () => {
      mockGetServices.mockRejectedValue(new Error('Network error'));

      await civilStore.loadServices();

      const state = get(civilStore);
      expect(state.error).toBe('Network error');
      expect(state.operationInProgress).toBe(false);
    });
  });

  describe('loadRequests', () => {
    it('should load requests successfully', async () => {
      const mockRequests = [{ id: '1', serviceId: 's1', status: 'pending' }];
      mockGetMyRequests.mockResolvedValue(mockRequests);

      await civilStore.loadRequests();

      const state = get(civilStore);
      expect(state.requests).toEqual(mockRequests);
      expect(state.loading).toBe(false);
    });

    it('should handle load requests error', async () => {
      mockGetMyRequests.mockRejectedValue(new Error('Failed'));

      await civilStore.loadRequests();

      const state = get(civilStore);
      expect(state.error).toBe('Failed');
    });
  });

  describe('loadRequest', () => {
    it('should load single request', async () => {
      const mockRequest = { id: '1', userId: 'user1', serviceId: 's1' };
      mockGetMyRequest.mockResolvedValue(mockRequest);

      const result = await civilStore.loadRequest('1');

      expect(result).toEqual(mockRequest);
      const state = get(civilStore);
      expect(state.requests).toContainEqual(mockRequest);
    });

    it('should throw error if unauthorized', async () => {
      civilStore.setCurrentUser('user1');
      const mockRequest = { id: '1', userId: 'user2', serviceId: 's1' };
      mockGetMyRequest.mockResolvedValue(mockRequest);

      await expect(civilStore.loadRequest('1')).rejects.toThrow('Unauthorized');
    });
  });

  describe('submitRequest', () => {
    it('should submit request successfully', async () => {
      const mockRequest = { id: '1', serviceId: 's1', status: 'submitted' };
      mockSubmitRequest.mockResolvedValue(mockRequest);

      const result = await civilStore.submitRequest('s1', ['doc1.pdf']);

      expect(result).toEqual(mockRequest);
      const state = get(civilStore);
      expect(state.requests).toContainEqual(mockRequest);
    });

    it('should handle submit error', async () => {
      mockSubmitRequest.mockRejectedValue(new Error('Submit failed'));

      await expect(civilStore.submitRequest('s1', [])).rejects.toThrow('Submit failed');
    });
  });

  describe('cancelRequest', () => {
    it('should cancel request successfully', async () => {
      const initial = { id: '1', serviceId: 's1', status: 'pending' };
      const cancelled = { id: '1', serviceId: 's1', status: 'cancelled' };
      
      // Pre-load the request
      mockGetMyRequest.mockResolvedValueOnce(initial);
      await civilStore.loadRequest('1');
      mockGetMyRequest.mockReset();
      
      mockCancelRequest.mockResolvedValue(cancelled);

      await civilStore.cancelRequest('1');

      const state = get(civilStore);
      expect(state.requests[0].status).toBe('cancelled');
    });
  });

  describe('operationInProgress', () => {
    it('should block concurrent operations', async () => {
      mockGetServices.mockImplementation(() => new Promise(() => {})); // Never resolves
      
      const loadPromise = civilStore.loadServices();
      
      // Try another operation while first is in progress
      await civilStore.loadRequests();
      
      const state = get(civilStore);
      expect(state.error).toBe('Another operation is in progress');
    });
  });

  describe('clearError and reset', () => {
    it('should clear error', async () => {
      mockGetServices.mockRejectedValue(new Error('Error'));
      await civilStore.loadServices();
      
      civilStore.clearError();
      expect(get(civilStore).error).toBeNull();
    });

    it('should reset to initial state', async () => {
      civilStore.setCurrentUser('user1');
      mockGetServices.mockResolvedValue([{ id: '1' }]);
      await civilStore.loadServices();
      
      civilStore.reset();
      
      const state = get(civilStore);
      expect(state.currentUserId).toBeNull();
      expect(state.services).toEqual([]);
    });
  });
});
