import { describe, it, expect, vi, beforeEach } from 'vitest';
import { EmergencyService } from './emergencyService';

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('EmergencyService', () => {
  let service: EmergencyService;

  beforeEach(() => {
    service = new EmergencyService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => {
    const response: any = { ok };
    response.json = async () => data;
    return response;
  };

  describe('getAlerts', () => {
    it('should fetch alerts successfully', async () => {
      const alerts = [{ id: 'a1', type: 'weather' }];
      mockFetch.mockResolvedValue(mockResponse(alerts));

      const result = await service.getAlerts();

      expect(result).toEqual(alerts);
      expect(mockFetch).toHaveBeenCalledWith('/api/emergency/alerts');
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.getAlerts()).rejects.toThrow('Failed to fetch alerts');
    });
  });
});
