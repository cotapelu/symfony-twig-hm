import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { CivilServiceModule } from './civilService';
import { __testing__ } from './civilService';

// Suppress console logs
vi.spyOn(console, 'info').mockImplementation(() => {});
vi.spyOn(console, 'warn').mockImplementation(() => {});
vi.spyOn(console, 'error').mockImplementation(() => {});

describe('CivilServiceModule', () => {
  let service: CivilServiceModule;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new CivilServiceModule();
  });

  describe('getServices', () => {
    it('should fetch civil services', async () => {
      const services = [{ id: 's1', name: 'Permit' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(services) });

      const result = await service.getServices();

      expect(result).toEqual(services);
      expect(global.fetch).toHaveBeenCalledWith('/api/civil/services');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getServices()).rejects.toThrow('Failed to fetch services');
    });
  });

  describe('getMyRequests', () => {
    it('should fetch my requests', async () => {
      const requests = [{ id: 'r1', serviceId: 's1' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(requests) });

      const result = await service.getMyRequests();

      expect(result).toEqual(requests);
      expect(global.fetch).toHaveBeenCalledWith('/api/civil/my-requests');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getMyRequests()).rejects.toThrow('Failed to fetch my requests');
    });
  });

  describe('getMyRequest', () => {
    it('should fetch request by id', async () => {
      const req = { id: 'r1', serviceId: 's1' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(req) });

      const result = await service.getMyRequest('r1');

      expect(result).toEqual(req);
      expect(global.fetch).toHaveBeenCalledWith('/api/civil/my-requests/r1');
    });

    it('should throw if id missing', async () => {
      await expect(service.getMyRequest('')).rejects.toThrow('Request ID is required');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getMyRequest('r1')).rejects.toThrow('Failed to fetch request');
    });
  });

  describe('submitRequest', () => {
    const serviceId = 's1';
    const documents = ['doc1', 'doc2'];

    it('should validate serviceId and documents', async () => {
      await expect(service.submitRequest('', documents)).rejects.toThrow('Service ID is required');
      await expect(service.submitRequest('x'.repeat(101), documents)).rejects.toThrow('Service ID is too long');
      await expect(service.submitRequest(serviceId, [])).rejects.toThrow('At least one document is required');
      await expect(service.submitRequest(serviceId, ['d'.repeat(501)])).rejects.toThrow('is too long');
    });

    it('should submit request successfully', async () => {
      const created = { id: 'r1', serviceId, documents };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(created) });

      const result = await service.submitRequest(serviceId, documents);

      expect(result).toEqual(created);
      expect(global.fetch).toHaveBeenCalledWith('/api/civil/requests', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('"serviceId":"s1"')
      }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 400 });

      await expect(service.submitRequest(serviceId, documents)).rejects.toThrow('Failed to submit request');
    });
  });

  describe('cancelRequest', () => {
    it('should cancel request', async () => {
      const cancelled = { id: 'r1', status: 'cancelled' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(cancelled) });

      const result = await service.cancelRequest('r1');

      expect(result).toEqual(cancelled);
      expect(global.fetch).toHaveBeenCalledWith('/api/civil/my-requests/r1/cancel', expect.objectContaining({ method: 'POST' }));
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.cancelRequest('r1')).rejects.toThrow('Failed to cancel request');
    });
  });
});
