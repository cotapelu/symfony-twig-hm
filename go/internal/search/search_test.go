package search

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestNewSearchService(t *testing.T) {
	svc := NewSearchService()
	assert.NotNil(t, svc)
}

func TestIndexDocument(t *testing.T) {
	svc := NewSearchService()
	
	content := map[string]interface{}{
		"title":   "Test Product",
		"body":    "This is a test product description",
		"tags":    []string{"test", "product"},
	}
	
	svc.IndexDocument("doc1", "product", content)
	
	// Verify document was indexed
	assert.Len(t, svc.index["product"], 1)
	assert.NotNil(t, svc.docs["doc1"])
}

func TestSearch(t *testing.T) {
	svc := NewSearchService()
	
	// Index some documents
	svc.IndexDocument("doc1", "product", map[string]interface{}{
		"title": "iPhone 15 Pro",
		"description": "Latest iPhone model",
	})
	svc.IndexDocument("doc2", "product", map[string]interface{}{
		"title": "Samsung Galaxy S24",
		"description": "Android smartphone",
	})
	svc.IndexDocument("doc3", "article", map[string]interface{}{
		"title": "iPhone review",
		"content": "Our iPhone review article",
	})
	
	tests := []struct {
		name      string
		query     string
		opts      SearchOptions
		wantCount int
	}{
		{
			name:      "search for iphone",
			query:     "iphone",
			opts:      SearchOptions{PageSize: 10},
			wantCount: 2,
		},
		{
			name:      "search for galaxy",
			query:     "galaxy",
			opts:      SearchOptions{PageSize: 10},
			wantCount: 1,
		},
		{
			name:      "search with type filter",
			query:     "phone",
			opts:      SearchOptions{PageSize: 10, Fields: []string{"product"}},
			wantCount: 2,
		},
		{
			name:      "no results",
			query:     "nonexistent",
			opts:      SearchOptions{PageSize: 10},
			wantCount: 0,
		},
	}
	
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			results, err := svc.Search(tt.query, tt.opts)
			assert.NoError(t, err)
			assert.Len(t, results, tt.wantCount)
		})
	}
}

func TestSearchWithHighlight(t *testing.T) {
	svc := NewSearchService()
	
	svc.IndexDocument("doc1", "product", map[string]interface{}{
		"title": "iPhone 15 Pro",
		"description": "Latest iPhone model",
	})
	
	opts := SearchOptions{
		PageSize:  10,
		Highlight: true,
	}
	
	results, err := svc.Search("iPhone", opts)
	assert.NoError(t, err)
	assert.Len(t, results, 1)
	assert.NotNil(t, results[0].Highlight)
	assert.Len(t, results[0].Highlight, 2)
}

func TestCalculateScore(t *testing.T) {
	svc := NewSearchService()
	
	tests := []struct {
		name    string
		keywords  []string
		query   string
		wantMin float64
	}{
		{
			name:     "exact match",
			keywords: []string{"iphone", "smartphone"},
			query:    "iphone",
			wantMin:  2.0,
		},
		{
			name:     "partial match",
			keywords: []string{"iphone", "apple"},
			query:    "phone",
			wantMin:  1.0,
		},
		{
			name:     "no match",
			keywords: []string{"android", "google"},
			query:    "apple",
			wantMin:  0.0,
		},
	}
	
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			score := svc.calculateScore(tt.keywords, tt.query)
			assert.GreaterOrEqual(t, score, tt.wantMin)
		})
	}
}

func TestExtractKeywords(t *testing.T) {
	svc := NewSearchService()
	
	content := map[string]interface{}{
		"title":  "Test",
		"tags":   []string{"tag1", "tag2"},
		"nested": map[string]interface{}{"key": "value"},
	}
	
	keywords := svc.extractKeywords(content)
	assert.Contains(t, keywords, "test")
	assert.Contains(t, keywords, "tag1")
	assert.Contains(t, keywords, "tag2")
	assert.Contains(t, keywords, "value")
}

func TestApplyHighlight(t *testing.T) {
	svc := NewSearchService()
	
	text := "This is an iPhone test"
	highlighted := svc.applyHighlight(text, "iphone")
	
	assert.Contains(t, highlighted, "<mark>")
	assert.Contains(t, highlighted, "iPhone")
}

func TestRemoveDocument(t *testing.T) {
	svc := NewSearchService()
	
	svc.IndexDocument("doc1", "product", map[string]interface{}{
		"title": "Test",
	})
	
	svc.RemoveDocument("doc1")
	assert.Nil(t, svc.docs["doc1"])
}

func TestSearchPagination(t *testing.T) {
	svc := NewSearchService()
	
	// Index multiple documents
	for i := 0; i < 5; i++ {
		svc.IndexDocument(string(rune('a'+i)), "product", map[string]interface{}{
			"title": "Product test item",
		})
	}
	
	// Get first page
	page1, _ := svc.Search("test", SearchOptions{PageSize: 2})
	assert.LessOrEqual(t, len(page1), 2)
	
	// Get second page
	page2, _ := svc.Search("test", SearchOptions{PageSize: 2, PageToken: "2"})
	assert.LessOrEqual(t, len(page2), 2)
}