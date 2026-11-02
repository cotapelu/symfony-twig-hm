import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

global.fetch = vi.fn();

import { SecurityOrderService, setRetryWithBackoff, resetRetryWithBackoff } from './securityOrderService';

describe('SecurityOrderService', () => {
  let service: SecurityOrderService;

  beforeEach(() => {
    // Mock the retry function to execute immediately without delays
    setRetryWithBackoff(vi.fn(async (operation: () => Promise<any>) => {
      return operation();
    }));

    vi.clearAllMocks();
    service = new SecurityOrderService();
  });

  afterEach(() => {
    resetRetryWithBackoff();
    vi.restoreAllMocks();
  });

  describe('getOrders', () => {
    it('should fetch orders with default pagination', async () => {
      const result = { items: [{ id: 'o1', type: 'security' }], total: 1 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      const data = await service.getOrders();

      expect(data).toEqual(result);
      expect(global.fetch).toHaveBeenCalledWith('/api/security-order?page=1&limit=20');
    });

    it('should fetch with custom pagination', async () => {
      const result = { items: [], total: 0 };
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(result) });

      await service.getOrders(2, 50);

      expect(global.fetch).toHaveBeenCalledWith('/api/security-order?page=2&limit=50');
    });

    it('should throw on invalid page params', async () => {
      await expect(service.getOrders(0, 10)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getOrders(1, 0)).rejects.toThrow('Invalid pagination parameters');
      await expect(service.getOrders(1, 150)).rejects.toThrow('Invalid pagination parameters');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getOrders()).rejects.toThrow('Failed to fetch orders');
    });
  });
});