import { describe, it, expect, vi, beforeEach } from 'vitest';
import { DealsService } from './dealsService';

// Mock fetch globally
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('DealsService', () => {
  let service: DealsService;

  beforeEach(() => {
    service = new DealsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => {
    const response: any = { ok };
    response.json = async () => data;
    return response;
  };

  describe('getDeals', () => {
    it('should fetch deals successfully', async () => {
      const deals = [{ id: 'd1', title: 'Hot deal' }];
      mockFetch.mockResolvedValue(mockResponse(deals));

      const result = await service.getDeals();

      expect(result).toEqual(deals);
      expect(mockFetch).toHaveBeenCalledWith('/api/deals');
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.getDeals()).rejects.toThrow('Failed to fetch deals');
    });
  });
});
