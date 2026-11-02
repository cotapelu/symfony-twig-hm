import { describe, it, expect, vi, beforeEach } from 'vitest';
import { CitizenServicesService } from './citizenServicesService';
import { apiClient } from '../../../appcore/api-client';

vi.mock('../../../appcore/api-client', () => ({
  apiClient: { get: vi.fn(), post: vi.fn() }
}));

describe('CitizenServicesService', () => {
  let service: CitizenServicesService;

  beforeEach(() => {
    service = new CitizenServicesService();
    vi.clearAllMocks();
  });

  describe('getServices', () => {
    it('should fetch citizen services', async () => {
      const services = [{ id: 's1' }];
      (apiClient.get as any).mockResolvedValue(services);
      const result = await service.getServices();
      expect(result).toEqual(services);
      expect(apiClient.get).toHaveBeenCalledWith('/api/citizen-services/services');
    });

    it('should propagate errors', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('API error'));
      await expect(service.getServices()).rejects.toThrow('API error');
    });
  });

  describe('getRequests', () => {
    it('should fetch citizen service requests', async () => {
      const requests = [{ id: 'r1' }];
      (apiClient.get as any).mockResolvedValue(requests);
      const result = await service.getRequests();
      expect(result).toEqual(requests);
      expect(apiClient.get).toHaveBeenCalledWith('/api/citizen-services/requests');
    });

    it('should propagate errors', async () => {
      (apiClient.get as any).mockRejectedValue(new Error('Fail'));
      await expect(service.getRequests()).rejects.toThrow('Fail');
    });
  });

  describe('submitRequest', () => {
    it('should submit a service request', async () => {
      const submitted = { id: 'new' };
      (apiClient.post as any).mockResolvedValue(submitted);
      const result = await service.submitRequest('svc1', ['doc1', 'doc2']);
      expect(result).toEqual(submitted);
      expect(apiClient.post).toHaveBeenCalledWith('/api/citizen-services/requests', {
        serviceId: 'svc1',
        documents: ['doc1', 'doc2']
      });
    });

    it('should propagate errors', async () => {
      (apiClient.post as any).mockRejectedValue(new Error('Invalid'));
      await expect(service.submitRequest('x', [])).rejects.toThrow('Invalid');
    });
  });
});
