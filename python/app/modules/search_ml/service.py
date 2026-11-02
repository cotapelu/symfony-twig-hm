from typing import List, Dict
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import numpy as np
from .models import SearchResult, SearchEntityType, SearchQuery

class SearchService:
    def __init__(self):
        self.index: Dict[str, str] = {}
        self.entity_types: Dict[str, SearchEntityType] = {}
        self.vectorizer = TfidfVectorizer()
        self.documents = []
        self.document_ids = []
        self.tfidf_matrix = None
        
    def index_document(self, entity_id: str, content: str, entity_type: SearchEntityType, metadata: Dict = None):
        if metadata is None:
            metadata = {}
            
        self.index[entity_id] = content
        self.entity_types[entity_id] = entity_type
        self.documents.append(content)
        self.document_ids.append(entity_id)
        self._rebuild_index()
        
    def _rebuild_index(self):
        if self.documents:
            self.tfidf_matrix = self.vectorizer.fit_transform(self.documents)
            
    def search(self, *args, **kwargs):
        return self._search_impl(*args, **kwargs)
    def _search_impl(self, query: SearchQuery) -> List[SearchResult]:
        if not self.documents:
            return []
            
        query_vector = self.vectorizer.transform([query.query])
        similarities = cosine_similarity(query_vector, self.tfidf_matrix).flatten()
        
        results = []
        for idx, score in enumerate(similarities):
            entity_id = self.document_ids[idx]
            content = self.index[entity_id]
            entity_type = self.entity_types[entity_id]
            
            if query.entity_types and entity_type not in query.entity_types:
                continue
                
            results.append(
                SearchResult(
                    entity_id=entity_id,
                    entity_type=entity_type,
                    score=float(score),
                    snippet=self._get_snippet(content, query.query),
                    metadata={"entity_id": entity_id, "content_preview": content[:100]}
                )
            )
            
        results.sort(key=lambda r: r.score, reverse=True)
        return results[:10]
        
    def _get_snippet(self, content: str, query: str, max_length: int = 100) -> str:
        content_lower = content.lower()
        query_lower = query.lower()
        
        if query_lower in content_lower:
            idx = content_lower.index(query_lower)
            start = max(0, idx - 20)
            end = min(len(content), idx + len(query) + 20)
            return "..." + content[start:end] + "..."
            
        return content[:max_length]
