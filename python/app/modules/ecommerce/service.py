from typing import List, Dict
from datetime import datetime
from .models import Product, ProductCategory, ProductRecommendation, UserPreference
from .recommender import ProductRecommender

class EcommerceService:
    def __init__(self):
        self.recommender = ProductRecommender()

    def get_products(self, filters: dict = None) -> List[Product]:
        limit = 10
        if filters:
            limit = int(filters.get('limit', 10))
        products = self.recommender.products[:limit]
        return products

    def get_product(self, product_id: str) -> Product | None:
        return next((p for p in self.recommender.products if p.id == product_id), None)

    def create_product(self, product: Product) -> Product:
        self.recommender.add_product(product)
        return product

    def get_product_recommendations(self, user_id: str, limit: int = 10) -> List[ProductRecommendation]:
        recommended_ids = self.recommender.get_recommendations(user_id, limit)
        
        recommendations = []
        for i, product_id in enumerate(recommended_ids):
            product = next((p for p in self.recommender.products if p.id == product_id), None)
            if product:
                score = 1.0 - (i / len(recommended_ids))
                recommendations.append(
                    ProductRecommendation(
                        product_id=product_id,
                        score=score,
                        reason=f"Recommended based on your preferences"
                    )
                )
        
        return recommendations
        
    def get_trending_products(self, limit: int = 10) -> List[Product]:
        trending_ids = self.recommender.get_trending_products(limit)
        return [p for p in self.recommender.products if p.id in trending_ids]
        
    def update_user_preferences(self, user_id: str, preferences: dict):
        product_prefs = [ProductCategory(cat) for cat in preferences.get("categories", [])]
        price_range = tuple(preferences.get("price_range", [0, 100000]))
        favorite_brands = preferences.get("favorite_brands", [])
        last_viewed = preferences.get("last_viewed_products", [])
        
        pref = UserPreference(
            user_id=user_id,
            category_preferences=product_prefs,
            price_range=price_range,
            favorite_brands=favorite_brands,
            last_viewed_products=last_viewed
        )
        
        self.recommender.update_user_preference(user_id, pref)
