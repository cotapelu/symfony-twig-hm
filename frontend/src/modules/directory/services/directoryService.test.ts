import { describe, it, expect, vi, beforeEach } from 'vitest';
import { DirectoryService } from './directoryService';

global.fetch = vi.fn();

describe('DirectoryService', () => {
  let service: DirectoryService;

  beforeEach(() => {
    service = new DirectoryService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getEntries', () => {
    it('should fetch directory entries', async () => {
      const entries = [{ id: '1', name: 'Alice' }];
      (global.fetch as any).mockResolvedValue(mockResponse(entries));
      const result = await service.getEntries();
      expect(result).toEqual(entries);
      expect(fetch).toHaveBeenCalledWith('/api/directory');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getEntries()).rejects.toThrow('Failed to fetch directory');
    });
  });
});
