import { describe, it, expect, vi, beforeEach } from 'vitest';
import { PopulationService } from './populationService';

global.fetch = vi.fn();

describe('PopulationService', () => {
  let service: PopulationService;

  beforeEach(() => {
    service = new PopulationService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getRecords', () => {
    it('should fetch population records', async () => {
      const records = [{ id: 'pop1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(records));
      const result = await service.getRecords();
      expect(result).toEqual(records);
      expect(fetch).toHaveBeenCalledWith('/api/population');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getRecords()).rejects.toThrow('Failed to fetch records');
    });
  });
});
