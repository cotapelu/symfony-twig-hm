// Package search provides full-text search functionality
package search

import (
	"sort"
	"strings"
	"sync"
)

// SearchResult represents a single search result
type SearchResult struct {
	ID      string                 `json:"id"`
	Type    string                 `json:"type"`
	Score   float64                `json:"score"`
	Content map[string]interface{} `json:"content"`
	Highlight map[string]string   `json:"highlight,omitempty"`
}

// SearchOptions configures search behavior
type SearchOptions struct {
	PageSize    int
	PageToken   string
	Highlight   bool
	Fields      []string
	SortByScore bool
}

// SearchService provides full-text search capabilities
type SearchService struct {
	mu    sync.RWMutex
	index map[string][]IndexedDocument
	docs  map[string]interface{}
}

// IndexedDocument represents a document in the search index
type IndexedDocument struct {
	ID       string
	Type     string
	Content  map[string]interface{}
	Keywords []string
}

// NewSearchService creates a new search service
func NewSearchService() *SearchService {
	return &SearchService{
		index: make(map[string][]IndexedDocument),
		docs:  make(map[string]interface{}),
	}
}

// IndexDocument adds a document to the search index
func (s *SearchService) IndexDocument(id, docType string, content map[string]interface{}) {
	s.mu.Lock()
	defer s.mu.Unlock()
	
	// Extract keywords from content
	keywords := s.extractKeywords(content)
	
	doc := IndexedDocument{
		ID:       id,
		Type:     docType,
		Content:  content,
		Keywords: keywords,
	}
	
	s.index[docType] = append(s.index[docType], doc)
	s.docs[id] = content
}

// extractKeywords extracts searchable keywords from content
func (s *SearchService) extractKeywords(content map[string]interface{}) []string {
	var keywords []string
	
	var extractValues func(v interface{})
	extractValues = func(v interface{}) {
		switch val := v.(type) {
		case string:
			keywords = append(keywords, strings.ToLower(val))
		case []interface{}:
			for _, item := range val {
				extractValues(item)
			}
		case []string:
			for _, item := range val {
				keywords = append(keywords, strings.ToLower(item))
			}
		case map[string]interface{}:
			for _, item := range val {
				extractValues(item)
			}
		}
	}
	
	for _, v := range content {
		extractValues(v)
	}
	
	return keywords
}

// Search performs a full-text search
func (s *SearchService) Search(query string, opts SearchOptions) ([]SearchResult, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	
	query = strings.ToLower(query)
	var results []SearchResult
	
	// Search in all document types if none specified
	searchTypes := opts.Fields
	if len(searchTypes) == 0 {
		for docType := range s.index {
			searchTypes = append(searchTypes, docType)
		}
	}
	
	for _, docType := range searchTypes {
		docs := s.index[docType]
		for _, doc := range docs {
			score := s.calculateScore(doc.Keywords, query)
			if score > 0 {
				result := SearchResult{
					ID:      doc.ID,
					Type:    doc.Type,
					Score:   score,
					Content: doc.Content,
				}
				
				if opts.Highlight {
					result.Highlight = s.highlightMatches(doc.Content, query)
				}
				
				results = append(results, result)
			}
		}
	}
	
	// Sort by score
	if opts.SortByScore {
		sort.Slice(results, func(i, j int) bool {
			return results[i].Score > results[j].Score
		})
	}
	
	// Pagination
	start := 0
	if opts.PageToken != "" {
		start = parseIntToken(opts.PageToken)
	}
	
	end := start + opts.PageSize
	if end > len(results) || opts.PageSize == 0 {
		end = len(results)
	}
	
	if start < len(results) {
		results = results[start:end]
	} else {
		results = []SearchResult{}
	}
	
	return results, nil
}

// calculateScore calculates relevance score based on keyword matches
func (s *SearchService) calculateScore(keywords []string, query string) float64 {
	score := 0.0
	
	for _, kw := range keywords {
		if strings.Contains(kw, query) {
			// Exact match gets higher score
			if kw == query {
				score += 2.0
			} else {
				score += 1.0
			}
		}
	}
	
	return score
}

// highlightMatches returns highlighted snippets of matching content
func (s *SearchService) highlightMatches(content map[string]interface{}, query string) map[string]string {
	highlights := make(map[string]string)
	
	for key, val := range content {
		if str, ok := val.(string); ok {
			if strings.Contains(strings.ToLower(str), query) {
				highlights[key] = s.applyHighlight(str, query)
			}
		}
	}
	
	return highlights
}

// applyHighlight wraps matching portions with highlight markers
func (s *SearchService) applyHighlight(text, query string) string {
	lowerText := strings.ToLower(text)
	idx := strings.Index(lowerText, query)
	if idx == -1 {
		return text
	}
	
	return text[:idx] + "<mark>" + text[idx:idx+len(query)] + "</mark>" + text[idx+len(query):]
}

// parseIntToken parses a page token (for simplicity, just returns the integer value)
func parseIntToken(token string) int {
	var result int
	for _, c := range token {
		if c >= '0' && c <= '9' {
			result = result*10 + int(c-'0')
		}
	}
	return result
}

// RemoveDocument removes a document from the index
func (s *SearchService) RemoveDocument(id string) {
	s.mu.Lock()
	defer s.mu.Unlock()
	
	delete(s.docs, id)
	// Note: full removal from index would require rebuilding
}