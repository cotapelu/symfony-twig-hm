import { describe, it, expect, vi, beforeEach } from 'vitest';
import { NewsService } from './newsService';

// Mock fetch
global.fetch = vi.fn();

describe('NewsService', () => {
  let service: NewsService;

  beforeEach(() => {
    service = new NewsService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('getArticles', () => {
    it('should fetch all articles', async () => {
      const articles = [{ id: '1', title: 'News 1' }];
      (global.fetch as any).mockResolvedValue(mockResponse(articles));

      const result = await service.getArticles();

      expect(result).toEqual(articles);
      expect(fetch).toHaveBeenCalledWith('/api/news/articles');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getArticles()).rejects.toThrow('Failed to fetch articles');
    });
  });

  describe('getArticle', () => {
    it('should fetch article by id', async () => {
      const article = { id: '1', title: 'News 1' };
      (global.fetch as any).mockResolvedValue(mockResponse(article));

      const result = await service.getArticle('1');

      expect(result).toEqual(article);
      expect(fetch).toHaveBeenCalledWith('/api/news/articles/1');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getArticle('1')).rejects.toThrow('Failed to fetch article');
    });
  });

  describe('getArticlesByCategory', () => {
    it('should fetch articles by category', async () => {
      const articles = [{ id: '2', category: 'sports' }];
      (global.fetch as any).mockResolvedValue(mockResponse(articles));

      const result = await service.getArticlesByCategory('sports');

      expect(result).toEqual(articles);
      const [url] = (global.fetch as any).mock.calls[0];
      expect(url).toBe('/api/news/articles?category=sports');
    });

    it('should encode category parameter', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse([]));
      await service.getArticlesByCategory('tech news');
      const [url] = (global.fetch as any).mock.calls[0];
      expect(url).toContain('category=tech%20news');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getArticlesByCategory('sports')).rejects.toThrow('Failed to fetch articles by category');
    });
  });

  describe('createArticle', () => {
    it('should create article', async () => {
      const created = { id: 'new', title: 'New Article' };
      (global.fetch as any).mockResolvedValue(mockResponse(created));
      const request = { title: 'New Article', content: 'Hello' };

      const result = await service.createArticle(request);

      expect(result).toEqual(created);
      expect(fetch).toHaveBeenCalledWith('/api/news/articles', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.createArticle({ title: 'X', content: 'Y' })).rejects.toThrow('Failed to create article');
    });
  });

  describe('updateArticle', () => {
    it('should update article', async () => {
      const updated = { id: '1', title: 'Updated' };
      (global.fetch as any).mockResolvedValue(mockResponse(updated));
      const request = { title: 'Updated' };

      const result = await service.updateArticle('1', request);

      expect(result).toEqual(updated);
      expect(fetch).toHaveBeenCalledWith('/api/news/articles/1', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(request)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.updateArticle('1', {})).rejects.toThrow('Failed to update article');
    });
  });

  describe('deleteArticle', () => {
    it('should delete article', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });

      await service.deleteArticle('1');

      expect(fetch).toHaveBeenCalledWith('/api/news/articles/1', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.deleteArticle('1')).rejects.toThrow('Failed to delete article');
    });
  });

  // Additional methods if any (like getComments, etc.) would be tested similarly.
});
