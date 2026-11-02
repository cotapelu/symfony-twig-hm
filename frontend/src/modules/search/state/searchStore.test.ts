import { describe, it, expect, vi, beforeEach } from 'vitest';
import { searchStore } from './searchStore';
import { SearchService } from '../services/searchService';
import type { SearchResponse, SearchSuggestion, SearchFilters } from '../contracts/searchTypes';

function getCurrentState() {
  let current: any;
  const unsub = searchStore.subscribe(s => { current = s; });
  unsub();
  return current;
}

const mockSearchResponse = (overrides: Partial<SearchResponse> = {}): SearchResponse => ({
  results: [{ id: '1', title: 'Result', snippet: '...' }],
  total: 1,
  page: 1,
  totalPages: 1,
  ...overrides
});

const mockSuggestions = (count: number): SearchSuggestion[] =>
  Array.from({ length: count }, (_, i) => ({
    query: `suggestion ${i}`,
    type: 'popular' as const,
    count: i + 1
  }));

describe('SearchStore', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    const localStorageMock = {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn()
    };
    vi.stubGlobal('localStorage', localStorageMock);
    // Reset store state
    searchStore.reset();
  });

  describe('initial state', () => {
    it('has defaults', () => {
      const state = getCurrentState();
      expect(state.results).toEqual([]);
      expect(state.suggestions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.query).toBe('');
      expect(state.filters).toEqual({});
      expect(state.total).toBe(0);
      expect(state.page).toBe(1);
      expect(state.recentSearches).toEqual([]);
    });
  });

  describe('search', () => {
    it('performs search and updates state and recentSearches', async () => {
      const response = mockSearchResponse({ results: [{ id: 'r1' }], total: 1, page: 1 });
      vi.spyOn(SearchService.prototype, 'search').mockResolvedValue(response);
      await searchStore.search('test');
      let state = getCurrentState();
      expect(state.results).toEqual([{ id: 'r1' }]);
      expect(state.total).toBe(1);
      expect(state.page).toBe(1);
      expect(state.query).toBe('test');
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.recentSearches).toContain('test');
    });

    it('deduplicates and limits recentSearches to 10', async () => {
      const response = mockSearchResponse();
      vi.spyOn(SearchService.prototype, 'search').mockResolvedValue(response);
      for (let i = 0; i < 15; i++) {
        await searchStore.search(`query${i}`);
      }
      const state = getCurrentState();
      expect(state.recentSearches.length).toBeLessThanOrEqual(10);
      expect(state.recentSearches[0]).toBe('query14');
    });

    it('handles error', async () => {
      vi.spyOn(SearchService.prototype, 'search').mockRejectedValue(new Error('Search error'));
      await searchStore.search('test');
      const state = getCurrentState();
      expect(state.error).toBe('Search error');
      expect(state.loading).toBe(false);
    });
  });

  describe('getSuggestions', () => {
    it('loads suggestions', async () => {
      const sugg = mockSuggestions(3);
      vi.spyOn(SearchService.prototype, 'getSuggestions').mockResolvedValue(sugg);
      await searchStore.getSuggestions('test');
      const state = getCurrentState();
      expect(state.suggestions).toEqual(sugg);
    });

    it('clears suggestions for empty query', async () => {
      await searchStore.getSuggestions('');
      const state = getCurrentState();
      expect(state.suggestions).toEqual([]);
    });

    it('silently fails on error', async () => {
      vi.spyOn(SearchService.prototype, 'getSuggestions').mockRejectedValue(new Error('fail'));
      await searchStore.getSuggestions('test');
      const state = getCurrentState();
      expect(state.suggestions).toEqual([]);
    });
  });

  describe('loadMore', () => {
    it('loads more results and appends', async () => {
      const firstResponse = mockSearchResponse({ results: [{ id: 'r1' }], page: 1 });
      const secondResponse = mockSearchResponse({ results: [{ id: 'r2' }], page: 2 });
      vi.spyOn(SearchService.prototype, 'search')
        .mockResolvedValueOnce(firstResponse)
        .mockResolvedValueOnce(secondResponse);
      await searchStore.search('test');
      let state = getCurrentState();
      expect(state.results).toHaveLength(1);
      await searchStore.loadMore();
      state = getCurrentState();
      expect(state.results).toHaveLength(2);
      expect(state.page).toBe(2);
    });

    it('handles error', async () => {
      vi.spyOn(SearchService.prototype, 'search').mockResolvedValueOnce(mockSearchResponse());
      vi.spyOn(SearchService.prototype, 'search').mockRejectedValueOnce(new Error('Load more failed'));
      await searchStore.search('test');
      await searchStore.loadMore();
      const state = getCurrentState();
      expect(state.error).toBe('Load more failed');
    });
  });

  describe('setSearchQuery', () => {
    it('sets query', () => {
      searchStore.setSearchQuery('hello');
      let state = getCurrentState();
      expect(state.query).toBe('hello');
    });
  });

  describe('setFilters', () => {
    it('sets filters', () => {
      const filters: SearchFilters = { category: 'news' };
      searchStore.setFilters(filters);
      let state = getCurrentState();
      expect(state.filters).toEqual(filters);
    });
  });

  describe('clearSearchResults', () => {
    it('clears results and related fields', async () => {
      const response = mockSearchResponse();
      vi.spyOn(SearchService.prototype, 'search').mockResolvedValue(response);
      await searchStore.search('test');
      let state = getCurrentState();
      expect(state.results.length).toBeGreaterThan(0);
      searchStore.clearSearchResults();
      state = getCurrentState();
      expect(state.results).toEqual([]);
      expect(state.total).toBe(0);
      expect(state.page).toBe(1);
      expect(state.query).toBe('');
    });
  });

  describe('clearRecentSearches', () => {
    it('clears recentSearches and localStorage', async () => {
      // Populate recentSearches via search
      const response = mockSearchResponse();
      vi.spyOn(SearchService.prototype, 'search').mockResolvedValue(response);
      await searchStore.search('a');
      await searchStore.search('b');
      let state = getCurrentState();
      expect(state.recentSearches).toHaveLength(2);
      // Now clear
      searchStore.clearRecentSearches();
      state = getCurrentState();
      expect(state.recentSearches).toEqual([]);
      expect(localStorage.removeItem).toHaveBeenCalledWith('recentSearches');
    });
  });

  describe('removeRecentSearch', () => {
    it('removes a term from recentSearches and updates localStorage', async () => {
      const response = mockSearchResponse();
      vi.spyOn(SearchService.prototype, 'search').mockResolvedValue(response);
      await searchStore.search('a');
      await searchStore.search('b');
      await searchStore.search('c'); // order: c, b, a
      let state = getCurrentState();
      expect(state.recentSearches).toEqual(['c', 'b', 'a']);
      searchStore.removeRecentSearch('b');
      state = getCurrentState();
      expect(state.recentSearches).toEqual(['c', 'a']);
      expect(localStorage.setItem).toHaveBeenCalledWith('recentSearches', JSON.stringify(['c', 'a']));
    });
  });

  describe('clearError', () => {
    it('clears error', async () => {
      vi.spyOn(SearchService.prototype, 'search').mockRejectedValue(new Error('test'));
      await searchStore.search('test');
      let state = getCurrentState();
      expect(state.error).toBe('test');
      searchStore.clearError();
      state = getCurrentState();
      expect(state.error).toBeNull();
    });
  });

  describe('reset', () => {
    it('restores initial state', async () => {
      const response = mockSearchResponse();
      vi.spyOn(SearchService.prototype, 'search').mockResolvedValue(response);
      await searchStore.search('test');
      searchStore.setFilters({ category: 'news' });
      let state = getCurrentState();
      expect(state.results.length).toBeGreaterThan(0);
      expect(state.filters).toEqual({ category: 'news' });
      searchStore.reset();
      state = getCurrentState();
      expect(state.results).toEqual([]);
      expect(state.suggestions).toEqual([]);
      expect(state.loading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.query).toBe('');
      expect(state.filters).toEqual({});
      expect(state.total).toBe(0);
      expect(state.page).toBe(1);
      expect(state.recentSearches).toEqual([]);
    });
  });
});
