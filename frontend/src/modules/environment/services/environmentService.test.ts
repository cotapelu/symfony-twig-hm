import { describe, it, expect, vi, beforeEach } from 'vitest';
import { EnvironmentService } from './environmentService';

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('EnvironmentService', () => {
  let service: EnvironmentService;

  beforeEach(() => {
    service = new EnvironmentService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => {
    const response: any = { ok };
    response.json = async () => data;
    return response;
  };

  describe('getReports', () => {
    it('should fetch reports successfully', async () => {
      const reports = [{ id: 'r1', type: 'air' }];
      mockFetch.mockResolvedValue(mockResponse(reports));

      const result = await service.getReports();

      expect(result).toEqual(reports);
      expect(mockFetch).toHaveBeenCalledWith('/api/environment/reports');
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.getReports()).rejects.toThrow('Failed to fetch reports');
    });
  });
});
