import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { LandConstructionService } from './landConstructionService';
import { __testing__ } from './landConstructionService';

describe('LandConstructionService', () => {
  let service: LandConstructionService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new LandConstructionService();
  });

  describe('getPermits', () => {
    it('should fetch permits', async () => {
      const permits = [{ id: 'p1', type: 'Building' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(permits) });

      const result = await service.getPermits();

      expect(result).toEqual(permits);
      expect(global.fetch).toHaveBeenCalledWith('/api/land-construction/permits');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getPermits()).rejects.toThrow('Failed to fetch permits');
    });
  });
});
