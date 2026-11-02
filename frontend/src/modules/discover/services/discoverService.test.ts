import { describe, it, expect, vi, beforeEach } from 'vitest';
import { DiscoverService } from './discoverService';

global.fetch = vi.fn();

describe('DiscoverService', () => {
  let service: DiscoverService;

  beforeEach(() => {
    service = new DiscoverService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getItems', () => {
    it('should fetch discover items', async () => {
      const items = [{ id: 'd1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(items));
      const result = await service.getItems();
      expect(result).toEqual(items);
      expect(fetch).toHaveBeenCalledWith('/api/discover');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getItems()).rejects.toThrow('Failed to fetch items');
    });
  });
});
