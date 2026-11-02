import { describe, it, expect, vi, beforeEach } from 'vitest';
import { MyApplicationsService } from './myApplicationsService';

global.fetch = vi.fn();

describe('MyApplicationsService', () => {
  let service: MyApplicationsService;

  beforeEach(() => {
    service = new MyApplicationsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getApplications', () => {
    it('should fetch my applications', async () => {
      const apps = [{ id: 'app1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(apps));
      const result = await service.getApplications();
      expect(result).toEqual(apps);
      expect(fetch).toHaveBeenCalledWith('/api/my-applications');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getApplications()).rejects.toThrow('Failed to fetch applications');
    });
  });
});
