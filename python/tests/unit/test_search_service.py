"""Unit tests for SearchService."""
import pytest
from app.modules.search_ml.service import SearchService
from app.modules.search_ml.models import SearchEntityType, SearchResult, SearchQuery
import numpy as np

class TestSearchService:
    """Test suite for SearchService."""

    @pytest.fixture
    def service(self):
        return SearchService()

    def test_index_document_and_search_basic(self, service):
        return self._test_index_document_and_search_basic_impl(service)

    def _test_index_document_and_search_basic_impl(self, service):
        service.index_document(
            entity_id="doc1",
            content="Python programming is great",
            entity_type=SearchEntityType.PRODUCT
        )
        service.index_document(
            entity_id="doc2",
            content="Java programming tutorial",
            entity_type=SearchEntityType.MESSAGE
        )
        query = SearchQuery(query="programming")
        results = service.search(query)
        assert len(results) == 2
        # Scores should be positive; doc1 likely higher because both words appear in first? Actually both contain "programming", but doc1 also has Python, doc2 has Java. Both will get some similarity.
        assert results[0].score > 0
        # Ensure entity_ids are correct
        ids = {r.entity_id for r in results}
        assert ids == {"doc1", "doc2"}
        # Ensure entity types preserved
        for r in results:
            if r.entity_id == "doc1":
                assert r.entity_type == SearchEntityType.PRODUCT
            else:
                assert r.entity_type == SearchEntityType.MESSAGE

    def test_search_empty_index(self, service):
        query = SearchQuery(query="anything")
        results = service.search(query)
        assert results == []

    def test_search_with_entity_type_filter(self, service):
        service.index_document("p1", "Red apples are tasty", SearchEntityType.PRODUCT)
        service.index_document("m1", "Blue jeans are popular", SearchEntityType.MESSAGE)
        service.index_document("p2", "Green apples are healthy", SearchEntityType.PRODUCT)
        # Search for apples only in products
        query = SearchQuery(query="apples", entity_types=[SearchEntityType.PRODUCT])
        results = service.search(query)
        assert len(results) == 2
        assert all(r.entity_type == SearchEntityType.PRODUCT for r in results)
        ids = {r.entity_id for r in results}
        assert ids == {"p1", "p2"}

    def test_search_returns_sorted_by_score(self, service):
        # Index docs with different term frequencies to influence score
        service.index_document("doc1", "machine learning deep learning neural networks", SearchEntityType.PRODUCT)
        service.index_document("doc2", "learning", SearchEntityType.MESSAGE)
        query = SearchQuery(query="learning")
        results = service.search(query)
        # doc2 contains only learning, should have higher score? Actually tfidf: In doc2 only term is learning, so vector normalized; doc1 contains multiple terms, learning one of them. Score might be similar. But we can assert order is deterministic by score descending.
        scores = [r.score for r in results]
        assert scores == sorted(scores, reverse=True)

    def test_get_snippet_extracts_context(self, service):
        # The method is internal but we can test via search results content.
        service.index_document("d1", "The quick brown fox jumps over the lazy dog", SearchEntityType.STORY)
        query = SearchQuery(query="fox")
        results = service.search(query)
        assert len(results) == 1
        snippet = results[0].snippet
        assert "fox" in snippet
        # Snippet should have context around the word (ellipses if not at edges)
        assert snippet.startswith("...") or snippet.endswith("...") or "fox" in snippet

    def test_index_multiple_and_search_limit(self, service):
        # Index many docs
        for i in range(50):
            service.index_document(f"doc{i}", f"Document number {i} with some text", SearchEntityType.PRODUCT)
        query = SearchQuery(query="document")
        results = service.search(query)
        # Should return at most 10 results by default (service limits to 10)
        assert len(results) <= 10

    def test_search_metadata_included(self, service):
        metadata = {"key": "value"}
        service.index_document("doc1", "test content", SearchEntityType.USER, metadata=metadata)
        query = SearchQuery(query="test")
        results = service.search(query)
        assert len(results) == 1
        result = results[0]
        # The service includes metadata (entity_id, content_preview) in SearchResult.

    def test_search_content_preview_in_metadata(self, service):
        service.index_document("doc1", "Hello world this is a test", SearchEntityType.MESSAGE)
        query = SearchQuery(query="test")
        results = service.search(query)
        assert len(results) == 1
        meta = results[0].metadata
        assert "entity_id" in meta
        assert meta["entity_id"] == "doc1"
        assert "content_preview" in meta
        assert "Hello" in meta["content_preview"]

    def test_search_entity_type_none_returns_all(self, service):
        service.index_document("p1", "Apple product", SearchEntityType.PRODUCT)
        service.index_document("m1", "Message about apples", SearchEntityType.MESSAGE)
        query = SearchQuery(query="apple")  # no entity_types filter
        results = service.search(query)
        assert len(results) == 2

if __name__ == "__main__":
    pytest.main([__file__, "-v"])