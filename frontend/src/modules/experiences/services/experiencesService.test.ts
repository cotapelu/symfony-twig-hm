import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ExperiencesService } from './experiencesService';

// Mock fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('ExperiencesService', () => {
  let service: ExperiencesService;

  beforeEach(() => {
    service = new ExperiencesService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => {
    const response: any = { ok };
    response.json = async () => data;
    return response;
  };

  describe('getExperiences', () => {
    it('should fetch experiences successfully', async () => {
      const experiences = [{ id: 'e1', title: 'Trip to Paris' }];
      mockFetch.mockResolvedValue(mockResponse(experiences));

      const result = await service.getExperiences();

      expect(result).toEqual(experiences);
      expect(mockFetch).toHaveBeenCalledWith('/api/experiences');
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.getExperiences()).rejects.toThrow('Failed to fetch experiences');
    });
  });
});
