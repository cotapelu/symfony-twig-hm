from typing import List, Dict
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from datetime import datetime
from .models import Product, ProductCategory, UserPreference

class ProductRecommender:
    def __init__(self):
        self.products: List[Product] = []
        self.user_preferences: Dict[str, UserPreference] = {}
        self.vectorizer = TfidfVectorizer()
        self.product_matrix = None
        
    def add_product(self, product: Product):
        self.products.append(product)
        
    def update_user_preference(self, user_id: str, preference: UserPreference):
        self.user_preferences[user_id] = preference
        
    def get_recommendations(self, user_id: str, limit: int = 10) -> List[str]:
        if user_id not in self.user_preferences:
            return []
            
        preference = self.user_preferences[user_id]
        product_indices = self._get_candidate_products(preference)
        scores = self._calculate_scores(preference, product_indices)
        
        sorted_indices = np.argsort(scores)[::-1]
        recommended_ids = [self.products[i].id for i in sorted_indices[:limit]]
        
        return recommended_ids
        
    def _get_candidate_products(self, preference: UserPreference) -> List[int]:
        indices = []
        for i, product in enumerate(self.products):
            if product.category in preference.category_preferences:
                if preference.price_range[0] <= product.price <= preference.price_range[1]:
                    indices.append(i)
        return indices
        
    def _calculate_scores(self, preference: UserPreference, indices: List[int]) -> np.ndarray:
        scores = np.zeros(len(indices))
        
        for i, idx in enumerate(indices):
            product = self.products[idx]
            
            if product.id in preference.last_viewed_products:
                scores[i] += 0.3
                
            if product.category in preference.category_preferences:
                scores[i] += 0.5
                
            normalized_price = product.price / preference.price_range[1]
            scores[i] += (1.0 - abs(normalized_price - 0.5)) * 0.2
            
            scores[i] += product.rating * 0.1
            
        return scores
        
    def get_trending_products(self, limit: int = 10) -> List[str]:
        sorted_products = sorted(self.products, key=lambda p: (p.review_count, p.rating), reverse=True)
        return [p.id for p in sorted_products[:limit]]
