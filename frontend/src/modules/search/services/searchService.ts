import type { SearchRequest, SearchResponse, SearchSuggestion, SearchFilters } from '../contracts/searchTypes';

export class SearchService {
  private baseUrl = '/api/search';

  async search(request: SearchRequest): Promise<SearchResponse> {
    const params = new URLSearchParams();
    params.append('q', request.query);
    if (request.filters?.category) params.append('category', request.filters.category);
    if (request.filters?.dateRange) params.append('dateRange', request.filters.dateRange);
    if (request.filters?.sortBy) params.append('sortBy', request.filters.sortBy);
    if (request.filters?.type) params.append('type', request.filters.type);
    if (request.page) params.append('page', request.page.toString());
    if (request.limit) params.append('limit', request.limit.toString());

    const response = await fetch(`${this.baseUrl}?${params}`);
    if (!response.ok) throw new Error('Search failed');
    return response.json();
  }

  async getSuggestions(query: string): Promise<SearchSuggestion[]> {
    const response = await fetch(`${this.baseUrl}/suggestions?q=${encodeURIComponent(query)}`);
    if (!response.ok) throw new Error('Failed to fetch suggestions');
    return response.json();
  }

  async getPopularSearches(): Promise<string[]> {
    const response = await fetch(`${this.baseUrl}/popular`);
    if (!response.ok) throw new Error('Failed to fetch popular searches');
    return response.json();
  }

  async getSearchHistory(): Promise<string[]> {
    const response = await fetch(`${this.baseUrl}/history`);
    if (!response.ok) throw new Error('Failed to fetch search history');
    return response.json();
  }

  async clearSearchHistory(): Promise<void> {
    const response = await fetch(`${this.baseUrl}/history`, { method: 'DELETE' });
    if (!response.ok) throw new Error('Failed to clear search history');
  }

  async indexContent(content: any): Promise<void> {
    const response = await fetch(`${this.baseUrl}/index`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(content)
    });
    if (!response.ok) throw new Error('Failed to index content');
  }
}
