import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ElderlyServiceModule } from './elderlyService';

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('ElderlyServiceModule', () => {
  let service: ElderlyServiceModule;

  beforeEach(() => {
    service = new ElderlyServiceModule();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => {
    const response: any = { ok };
    response.json = async () => data;
    return response;
  };

  describe('getServices', () => {
    it('should fetch services successfully', async () => {
      const services = [{ id: 's1', name: 'Home Care' }];
      mockFetch.mockResolvedValue(mockResponse(services));

      const result = await service.getServices();

      expect(result).toEqual(services);
      expect(mockFetch).toHaveBeenCalledWith('/api/elderly/services');
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.getServices()).rejects.toThrow('Failed to fetch services');
    });
  });
});
