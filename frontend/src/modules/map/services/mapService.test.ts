import { describe, it, expect, vi, beforeEach } from 'vitest';

global.fetch = vi.fn();

import { MapService } from './mapService';
import { __testing__ } from './mapService';

describe('MapService', () => {
  let service: MapService;

  beforeEach(() => {
    __testing__.clearCache();
    vi.clearAllMocks();
    service = new MapService();
  });

  describe('getLocations', () => {
    it('should fetch map locations', async () => {
      const locations = [{ id: 'l1', name: 'Park' }];
      (global.fetch as any).mockResolvedValue({ ok: true, json: vi.fn().mockResolvedValue(locations) });

      const result = await service.getLocations();

      expect(result).toEqual(locations);
      expect(global.fetch).toHaveBeenCalledWith('/api/map/locations');
    });

    it('should throw on API error', async () => {
      (global.fetch as any).mockResolvedValue({ ok: false, status: 500 });

      await expect(service.getLocations()).rejects.toThrow('Failed to fetch locations');
    });
  });
});
