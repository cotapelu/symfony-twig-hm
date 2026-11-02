export interface SearchResult {
  id: string;
  type: 'user' | 'message' | 'file' | 'product' | 'post' | 'group' | 'event' | 'place' | 'other';
  title: string;
  description: string;
  image?: string;
  url?: string;
  author?: string;
  date?: string;
  category?: string;
  metadata?: Record<string, any>;
  relevanceScore?: number;
}

export interface SearchSuggestion {
  id: string;
  text: string;
  type: string;
  highlight?: string;
}

export interface SearchFilters {
  category?: string;
  dateRange?: 'all' | 'today' | 'week' | 'month' | 'year';
  sortBy?: 'relevance' | 'date' | 'popularity';
  type?: 'all' | 'image' | 'video' | 'document';
  author?: string;
  tags?: string[];
}

export interface SearchRequest {
  query: string;
  filters?: SearchFilters;
  page?: number;
  limit?: number;
}

export interface SearchResponse {
  results: SearchResult[];
  total: number;
  page: number;
  totalPages: number;
  suggestions?: SearchSuggestion[];
  facets?: Record<string, { value: string; count: number }[]>;
}

export interface SearchState {
  results: SearchResult[];
  suggestions: SearchSuggestion[];
  loading: boolean;
  error: string | null;
  query: string;
  filters: SearchFilters;
  total: number;
  page: number;
  recentSearches: string[];
}
