import { describe, it, expect, vi, beforeEach } from 'vitest';
import { EducationGovServiceModule } from './educationGovService';

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('EducationGovServiceModule', () => {
  let service: EducationGovServiceModule;

  beforeEach(() => {
    service = new EducationGovServiceModule();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => {
    const response: any = { ok };
    response.json = async () => data;
    return response;
  };

  describe('getServices', () => {
    it('should fetch services successfully', async () => {
      const services = [{ id: 's1', name: 'School Meals' }];
      mockFetch.mockResolvedValue(mockResponse(services));

      const result = await service.getServices();

      expect(result).toEqual(services);
      expect(mockFetch).toHaveBeenCalledWith('/api/education-gov/services');
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.getServices()).rejects.toThrow('Failed to fetch services');
    });
  });
});
