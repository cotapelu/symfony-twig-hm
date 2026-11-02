import { describe, it, expect, vi, beforeEach } from 'vitest';
import { SmartcityService } from './smartcityService';

global.fetch = vi.fn();

describe('SmartcityService', () => {
  let service: SmartcityService;

  beforeEach(() => {
    service = new SmartcityService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  const mockError = (msg = 'Failed') => ({
    ok: false,
    json: vi.fn().mockResolvedValue({ error: msg })
  });

  describe('getServices', () => {
    it('should fetch smartcity services', async () => {
      const services = [{ id: 's1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(services));
      const result = await service.getServices();
      expect(result).toEqual(services);
      expect(fetch).toHaveBeenCalledWith('/api/smartcity/services');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockError());
      await expect(service.getServices()).rejects.toThrow('Failed to fetch services');
    });
  });

  describe('getService', () => {
    it('should fetch service by id', async () => {
      const s = { id: 's1' };
      (global.fetch as any).mockResolvedValue(mockResponse(s));
      const result = await service.getService('s1');
      expect(result).toEqual(s);
      expect(fetch).toHaveBeenCalledWith('/api/smartcity/services/s1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockError());
      await expect(service.getService('s1')).rejects.toThrow('Failed to fetch service');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockError());
      await expect(service.getService('s1')).rejects.toThrow('Failed to fetch service');
    });
  });

  describe('getServicesByCategory', () => {
    it('should fetch services by category', async () => {
      const services = [{ id: 's2' }];
      (global.fetch as any).mockResolvedValue(mockResponse(services));
      const result = await service.getServicesByCategory('transport');
      expect(result).toEqual(services);
      expect(fetch).toHaveBeenCalledWith('/api/smartcity/services?category=transport');
    });

    it('should encode category', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse([]));
      await service.getServicesByCategory('health care');
      expect(fetch).toHaveBeenCalledWith('/api/smartcity/services?category=health%20care');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockError());
      await expect(service.getServicesByCategory('x')).rejects.toThrow('Failed to fetch services by category');
    });
  });

  describe('updateService', () => {
    it('should update a service', async () => {
      const request = { name: 'New Name' };
      const updated = { id: 's1', ...request };
      (global.fetch as any).mockResolvedValue(mockResponse(updated));
      const result = await service.updateService('s1', request);
      expect(result).toEqual(updated);
      expect(fetch).toHaveBeenCalledWith('/api/smartcity/services/s1', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockError());
      await expect(service.updateService('s1', {})).rejects.toThrow('Failed to update service');
    });
  });

  describe('getAlerts', () => {
    it('should fetch alerts', async () => {
      const alerts = [{ id: 'a1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(alerts));
      const result = await service.getAlerts();
      expect(result).toEqual(alerts);
      expect(fetch).toHaveBeenCalledWith('/api/smartcity/alerts');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockError());
      await expect(service.getAlerts()).rejects.toThrow('Failed to fetch alerts');
    });
  });

  describe('acknowledgeAlert', () => {
    it('should acknowledge an alert', async () => {
      const alert = { id: 'a1', acknowledged: true };
      (global.fetch as any).mockResolvedValue(mockResponse(alert));
      const result = await service.acknowledgeAlert('a1');
      expect(result).toEqual(alert);
      expect(fetch).toHaveBeenCalledWith('/api/smartcity/alerts/a1/acknowledge', { method: 'POST' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockError());
      await expect(service.acknowledgeAlert('a1')).rejects.toThrow('Failed to acknowledge alert');
    });
  });

  describe('getServiceData', () => {
    it('should fetch service data', async () => {
      const data = { metric: 123 };
      (global.fetch as any).mockResolvedValue(mockResponse(data));
      const result = await service.getServiceData('s1');
      expect(result).toEqual(data);
      expect(fetch).toHaveBeenCalledWith('/api/smartcity/services/s1/data');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockError());
      await expect(service.getServiceData('s1')).rejects.toThrow('Failed to fetch service data');
    });
  });
});
