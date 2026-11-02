from typing import List, Dict, Any

class SearchService:
    """Universal search service"""
    
    def __init__(self):
        self._indexed_documents: List[Dict[str, Any]] = []
    
    def search(self, *args, **kwargs):
        return self._search_impl(*args, **kwargs)
    def _search_impl(self, query: str, filters: Dict[str, Any] = None, limit: int = 20, offset: int = 0) -> List[Dict[str, Any]]:
        """Search indexed documents"""
        filters = filters or {}
        query_lower = query.lower()
        
        results = []
        for doc in self._indexed_documents:
            # Simple text search (in production, use Elasticsearch/Meilisearch)
            if query_lower in doc.get("title", "").lower() or query_lower in doc.get("content", "").lower():
                # Apply filters
                match = True
                for key, value in filters.items():
                    if doc.get(key) != value:
                        match = False
                        break
                
                if match:
                    results.append(doc)
        
        # Pagination
        return results[offset:offset + limit]
    
    def get_suggestions(self, query: str, limit: int = 5) -> List[str]:
        """Get search suggestions"""
        query_lower = query.lower()
        suggestions = set()
        
        for doc in self._indexed_documents:
            title = doc.get("title", "")
            if query_lower in title.lower():
                suggestions.add(title)
        
        return list(suggestions)[:limit]
    
    def index_document(self, content: Dict[str, Any]) -> str:
        """Index a document for search"""
        doc_id = f"doc_{len(self._indexed_documents)}"
        document = {
            "id": doc_id,
            **content
        }
        self._indexed_documents.append(document)
        return doc_id
    
    def remove_document(self, document_id: str) -> bool:
        """Remove document from index"""
        for i, doc in enumerate(self._indexed_documents):
            if doc.get("id") == document_id:
                self._indexed_documents.pop(i)
                return True
        return False
