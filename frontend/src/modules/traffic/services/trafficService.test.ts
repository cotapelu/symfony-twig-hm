import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

global.fetch = vi.fn();

import { TrafficService, setRetryWithBackoff, resetRetryWithBackoff } from './trafficService';

describe('TrafficService', () => {
  let service: TrafficService;

  beforeEach(() => {
    // Mock the retry function to execute immediately without delays
    setRetryWithBackoff(vi.fn(async (operation: () => Promise<any>) => {
      return operation();
    }));

    vi.clearAllMocks();
    service = new TrafficService();
  });

  afterEach(() => {
    resetRetryWithBackoff();
    vi.restoreAllMocks();
  });

  describe('getAlerts', () => {
    it('should fetch alerts with default pagination', async () => {
      const result = { items: [{ id: 'a1', location: 'Downtown' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getAlerts();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/traffic/alerts?page=1&limit=20');
    });

    it('should fetch with custom pagination', async () => {
      const result = { items: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getAlerts(2, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/traffic/alerts?page=2&limit=50');
    });

    it('should throw on invalid page params', async () => {
      await expect(service.getAlerts(0, 10)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getAlerts(1, 0)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getAlerts(1, 150)).rejects.toThrow('Invalid pagination parameters');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getAlerts()).rejects.toThrow('Failed to fetch alerts');
    });
  });

  describe('getAlert', () => {
    it('should fetch alert by id', async () => {
      const alert = { id: 'a1', location: 'Downtown' };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(alert) });

      const result = await service.getAlert('a1');

      expect(result).toEqual(alert);
      expect(global.fetch).toHaveBeenCalledWith('/api/traffic/alerts/a1');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 404 });

      await expect(service.getAlert('a1')).rejects.toThrow('Failed to fetch alert');
    });
  });
});