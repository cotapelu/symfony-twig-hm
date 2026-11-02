import { describe, it, expect, vi, beforeEach } from 'vitest';
import { HouseholdService } from './householdService';

const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('HouseholdService', () => {
  let service: HouseholdService;

  beforeEach(() => {
    service = new HouseholdService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => {
    const response: any = { ok };
    response.json = async () => data;
    return response;
  };

  describe('getHouseholds', () => {
    it('should fetch households successfully', async () => {
      const households = [{ id: 'h1', name: 'My Home' }];
      mockFetch.mockResolvedValue(mockResponse(households));

      const result = await service.getHouseholds();

      expect(result).toEqual(households);
      expect(mockFetch).toHaveBeenCalledWith('/api/household');
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.getHouseholds()).rejects.toThrow('Failed to fetch households');
    });
  });
});
