import { describe, it, expect, vi, beforeEach } from 'vitest';
import { citizenServicesStore, citizenServiceList, citizenServiceRequests, citizenServicesLoading } from './citizenServicesStore';
import { citizenServicesService } from '../services/citizenServicesService';
import type { CitizenService, CitizenServiceRequest } from '../contracts/citizenServicesTypes';

// Helper to get current store state
function getState() {
  let current: any;
  const unsub = citizenServicesStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

// Mock data factories
const mockService = (overrides: Partial<CitizenService> = {}): CitizenService => ({
  id: `svc-${Math.random().toString(36).substr(2, 5)}`,
  name: 'Test Service',
  description: 'A test service',
  category: 'general',
  isActive: true,
  ...overrides
});

const mockRequest = (overrides: Partial<CitizenServiceRequest> = {}): CitizenServiceRequest => ({
  id: `req-${Math.random().toString(36).substr(2, 5)}`,
  serviceId: 'svc-1',
  citizenId: 'user-1',
  status: 'pending',
  createdAt: new Date().toISOString(),
  ...overrides
});

describe('CitizenServicesStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    citizenServicesStore.reset();
  });

  describe('initial state', () => {
    it('has default values', () => {
      const state = getState();
      expect(state.services).toEqual([]);
      expect(state.requests).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('derived stores', () => {
      let services: CitizenService[] = [], requests: CitizenServiceRequest[] = [], loading = false;
      const unsubS = citizenServiceList.subscribe(s => { services = s; });
      const unsubR = citizenServiceRequests.subscribe(s => { requests = s; });
      const unsubL = citizenServicesLoading.subscribe(s => { loading = s; });
      expect(services).toEqual([]);
      expect(requests).toEqual([]);
      expect(loading).toBe(false);
      unsubS(); unsubR(); unsubL();
    });
  });

  describe('loadServices', () => {
    it('loads services successfully', async () => {
      const services = [mockService(), mockService()];
      vi.spyOn(citizenServicesService, 'getServices').mockResolvedValue(services);

      await citizenServicesStore.loadServices();

      expect(citizenServicesService.getServices).toHaveBeenCalledTimes(1);
      const state = getState();
      expect(state.services).toEqual(services);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(citizenServicesService, 'getServices').mockReturnValue(p as any);

      const loadPromise = citizenServicesStore.loadServices();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockService()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch services');
      vi.spyOn(citizenServicesService, 'getServices').mockRejectedValue(error);

      await citizenServicesStore.loadServices();

      const state = getState();
      expect(state.error).toBe('Failed to fetch services');
      expect(state.loading).toBe(false);
      expect(state.services).toEqual([]);
    });
  });

  describe('loadRequests', () => {
    it('loads requests successfully', async () => {
      const requests = [mockRequest(), mockRequest()];
      vi.spyOn(citizenServicesService, 'getRequests').mockResolvedValue(requests);

      await citizenServicesStore.loadRequests();

      expect(citizenServicesService.getRequests).toHaveBeenCalledTimes(1);
      const state = getState();
      expect(state.requests).toEqual(requests);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets loading true during fetch', async () => {
      let resolvePromise: (value) => void;
      const p = new Promise(resolve => { resolvePromise = resolve; });
      vi.spyOn(citizenServicesService, 'getRequests').mockReturnValue(p as any);

      const loadPromise = citizenServicesStore.loadRequests();

      await Promise.resolve();
      expect(getState().loading).toBe(true);

      resolvePromise([mockRequest()]);
      await loadPromise;

      expect(getState().loading).toBe(false);
    });

    it('handles error', async () => {
      const error = new Error('Failed to fetch requests');
      vi.spyOn(citizenServicesService, 'getRequests').mockRejectedValue(error);

      await citizenServicesStore.loadRequests();

      const state = getState();
      expect(state.error).toBe('Failed to fetch requests');
      expect(state.loading).toBe(false);
      expect(state.requests).toEqual([]);
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(citizenServicesService, 'getServices').mockRejectedValue(new Error('test error'));
      await citizenServicesStore.loadServices().catch(() => {});

      expect(getState().error).toBe('test error');

      citizenServicesStore.clearError();
      expect(getState().error).toBeNull();
    });
  });

  describe('reset', () => {
    it('resets all state to initial', async () => {
      // Load some services
      const services = [mockService(), mockService()];
      vi.spyOn(citizenServicesService, 'getServices').mockResolvedValue(services);
      await citizenServicesStore.loadServices();
      expect(getState().services).toHaveLength(2);

      // Load some requests
      const requests = [mockRequest()];
      vi.spyOn(citizenServicesService, 'getRequests').mockResolvedValue(requests);
      await citizenServicesStore.loadRequests();
      expect(getState().requests).toHaveLength(1);

      // Reset
      citizenServicesStore.reset();

      const state = getState();
      expect(state.services).toEqual([]);
      expect(state.requests).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
    });
  });
});
