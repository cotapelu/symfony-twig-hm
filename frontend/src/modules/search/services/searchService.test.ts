import { describe, it, expect, vi, beforeEach } from 'vitest';
import { SearchService } from './searchService';

// Mock fetch
global.fetch = vi.fn();

describe('SearchService', () => {
  let service: SearchService;

  beforeEach(() => {
    service = new SearchService();
    vi.clearAllMocks();
  });

  const mockResponse = (data: any, ok = true) => ({
    ok,
    json: vi.fn().mockResolvedValue(data)
  });

  describe('search', () => {
    it('should perform search with query and optional filters', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse({ results: [], total: 0 }));
      const request = { query: 'test', filters: { category: 'news', dateRange: '2024-01-01,2024-12-31', sortBy: 'date', type: 'article' }, page: 1, limit: 10 };
      
      const result = await service.search(request);

      expect(result).toEqual({ results: [], total: 0 });
      const url = new URL((global.fetch as any).mock.calls[0][0], 'http://localhost');
      expect(url.pathname).toBe('/api/search');
      expect(url.searchParams.get('q')).toBe('test');
      expect(url.searchParams.get('category')).toBe('news');
      expect(url.searchParams.get('dateRange')).toBe('2024-01-01,2024-12-31');
      expect(url.searchParams.get('sortBy')).toBe('date');
      expect(url.searchParams.get('type')).toBe('article');
      expect(url.searchParams.get('page')).toBe('1');
      expect(url.searchParams.get('limit')).toBe('10');
    });

    it('should handle search without filters', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse({ results: [], total: 0 }));
      await service.search({ query: 'simple' });

      const url = new URL((global.fetch as any).mock.calls[0][0], 'http://localhost');
      expect(url.searchParams.toString()).toContain('q=simple');
    });

    it('should throw when response not ok', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));

      await expect(service.search({ query: 'test' })).rejects.toThrow('Search failed');
    });
  });

  describe('getSuggestions', () => {
    it('should fetch suggestions for query', async () => {
      const suggestions = ['suggestion1', 'suggestion2'];
      (global.fetch as any).mockResolvedValue(mockResponse(suggestions));
      const encoded = encodeURIComponent('test query');

      const result = await service.getSuggestions('test query');

      expect(result).toEqual(suggestions);
      expect(global.fetch).toHaveBeenCalledWith(`/api/search/suggestions?q=${encoded}`);
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getSuggestions('q')).rejects.toThrow('Failed to fetch suggestions');
    });
  });

  describe('getPopularSearches', () => {
    it('should fetch popular searches', async () => {
      const popular = ['weather', 'news'];
      (global.fetch as any).mockResolvedValue(mockResponse(popular));

      const result = await service.getPopularSearches();

      expect(result).toEqual(popular);
      expect(global.fetch).toHaveBeenCalledWith('/api/search/popular');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getPopularSearches()).rejects.toThrow('Failed to fetch popular searches');
    });
  });

  describe('getSearchHistory', () => {
    it('should fetch search history', async () => {
      const history = ['query1', 'query2'];
      (global.fetch as any).mockResolvedValue(mockResponse(history));

      const result = await service.getSearchHistory();

      expect(result).toEqual(history);
      expect(global.fetch).toHaveBeenCalledWith('/api/search/history');
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.getSearchHistory()).rejects.toThrow('Failed to fetch search history');
    });
  });

  describe('clearSearchHistory', () => {
    it('should clear search history', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });

      await service.clearSearchHistory();

      expect(global.fetch).toHaveBeenCalledWith('/api/search/history', { method: 'DELETE' });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.clearSearchHistory()).rejects.toThrow('Failed to clear search history');
    });
  });

  describe('indexContent', () => {
    it('should index provided content', async () => {
      (global.fetch as any).mockResolvedValue({ ok: true });
      const content = { type: 'article', title: 'Test', body: 'Hello' };

      await service.indexContent(content);

      expect(global.fetch).toHaveBeenCalledWith('/api/search/index', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(content)
      });
    });

    it('should throw on failure', async () => {
      (global.fetch as any).mockResolvedValue(mockResponse(undefined, false));
      await expect(service.indexContent({})).rejects.toThrow('Failed to index content');
    });
  });
});
