import { writable, get } from 'svelte/store';
import type { SearchResult, SearchSuggestion, SearchFilters, SearchRequest, SearchState } from '../contracts/searchTypes';
import { SearchService } from '../services/searchService';

const initialState: SearchState = {
  results: [],
  suggestions: [],
  loading: false,
  error: null,
  query: '',
  filters: {},
  total: 0,
  page: 1,
  recentSearches: []
};

function createSearchStore() {
  const { subscribe, set, update } = writable<SearchState>(initialState);
  const service = new SearchService();

  // Load recent searches from localStorage
  if (typeof localStorage !== 'undefined') {
    const saved = localStorage.getItem('recentSearches');
    if (saved) {
      try {
        initialState.recentSearches = JSON.parse(saved);
      } catch { /* ignore */ }
    }
  }

  return {
    subscribe,

    async search(query: string, filters?: SearchFilters) {
      update(state => ({ ...state, loading: true, error: null, query }));
      try {
        const response = await service.search({ query, filters });
        update(state => ({
          ...state,
          results: response.results,
          total: response.total,
          page: response.page,
          loading: false
        }));
        
        // Add to recent searches
        if (query.trim()) {
          update(state => {
            const newRecent = [query, ...state.recentSearches.filter(s => s !== query)].slice(0, 10);
            if (typeof localStorage !== 'undefined') {
              localStorage.setItem('recentSearches', JSON.stringify(newRecent));
            }
            return { ...state, recentSearches: newRecent };
          });
        }
      } catch (error) {
        update(state => ({
          ...state,
          error: error instanceof Error ? error.message : 'Search failed',
          loading: false
        }));
      }
    },

    async getSuggestions(query: string) {
      if (!query.trim()) {
        update(state => ({ ...state, suggestions: [] }));
        return;
      }

      try {
        const suggestions = await service.getSuggestions(query);
        update(state => ({ ...state, suggestions }));
      } catch (error) {
        // Silent fail for suggestions
        update(state => ({ ...state, suggestions: [] }));
      }
    },

    async loadMore() {
      const state = get({ subscribe });
      update(s => ({ ...s, loading: true }));
      try {
        const response = await service.search({
          query: state.query,
          filters: state.filters,
          page: state.page + 1
        });
        update(s => ({
          ...s,
          results: [...s.results, ...response.results],
          page: response.page,
          loading: false
        }));
      } catch (error) {
        update(s => ({
          ...s,
          error: error instanceof Error ? error.message : 'Failed to load more results',
          loading: false
        }));
      }
    },

    setSearchQuery(query: string) {
      update(state => ({ ...state, query }));
    },

    setFilters(filters: SearchFilters) {
      update(state => ({ ...state, filters }));
    },

    clearSearchResults() {
      update(state => ({ ...state, results: [], total: 0, page: 1, query: '' }));
    },

    clearRecentSearches() {
      update(state => {
        if (typeof localStorage !== 'undefined') {
          localStorage.removeItem('recentSearches');
        }
        return { ...state, recentSearches: [] };
      });
    },

    removeRecentSearch(term: string) {
      update(state => {
        const newRecent = state.recentSearches.filter(s => s !== term);
        if (typeof localStorage !== 'undefined') {
          localStorage.setItem('recentSearches', JSON.stringify(newRecent));
        }
        return { ...state, recentSearches: newRecent };
      });
    },

    clearError() {
      update(state => ({ ...state, error: null }));
    },
    reset() {
      set(initialState);
    }
  };
}

export const searchStore = createSearchStore();
export const { search, setSearchQuery, clearSearchResults, clearError } = searchStore;
