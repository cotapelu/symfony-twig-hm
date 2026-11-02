import { describe, it, expect, vi, beforeEach } from 'vitest';
import { HelpService } from './helpService';

global.fetch = vi.fn();

describe('HelpService', () => {
  let service: HelpService;

  beforeEach(() => {
    service = new HelpService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getArticles', () => {
    it('should fetch help articles', async () => {
      const articles = [{ id: 'a1', title: 'FAQ' }];
      (global.fetch as any).mockResolvedValue(mockResponse(articles));
      const result = await service.getArticles();
      expect(result).toEqual(articles);
      expect(fetch).toHaveBeenCalledWith('/api/help/articles');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getArticles()).rejects.toThrow('Failed to fetch articles');
    });
  });
});
