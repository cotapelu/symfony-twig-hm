import { describe, it, expect, vi, beforeEach } from 'vitest';
import { CultureSportsService } from './cultureSportsService';

const mockFetch = vi.fn();
global.fetch = mockFetch;

describe('CultureSportsService', () => {
  let service: CultureSportsService;

  beforeEach(() => {
    service = new CultureSportsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => {
    const response: any = { ok };
    response.json = async () => data;
    return response;
  };

  describe('getEvents', () => {
    it('should fetch events successfully', async () => {
      const events = [{ id: 'e1', name: 'Concert' }];
      mockFetch.mockResolvedValue(mockResponse(events));

      const result = await service.getEvents();

      expect(result).toEqual(events);
      expect(mockFetch).toHaveBeenCalledWith('/api/culture-sports/events');
    });

    it('should throw on failure', async () => {
      mockFetch.mockResolvedValue(mockResponse(undefined, false));

      await expect(service.getEvents()).rejects.toThrow('Failed to fetch events');
    });
  });
});
