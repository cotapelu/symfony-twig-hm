"""Unit tests for EcommerceService database integration."""
import pytest
import os

os.environ.setdefault("JWT_SECRET_KEY", "test-secret")

from app.modules.ecommerce.service import EcommerceService


class TestEcommerceServiceDB:
    """Test ecommerce service with in-memory mode."""

    @pytest.fixture
    def service(self):
        return EcommerceService()

    def test_get_products_returns_list(self, service):
        """get_products should return list of products (from recommender)"""
        products = service.get_products({'limit': 5})
        assert isinstance(products, list)

    def test_get_product_by_id(self, service):
        """get_product should return product or None"""
        product = service.get_product("non-existent-id")
        assert product is None

    def test_recommendations_returns_list(self, service):
        """get_product_recommendations should return list"""
        recs = service.get_product_recommendations("user-123", limit=5)
        assert isinstance(recs, list)

    def test_trending_products(self, service):
        """get_trending_products should return list"""
        trending = service.get_trending_products(limit=5)
        assert isinstance(trending, list)

    def test_update_user_preferences(self, service):
        """update_user_preferences should not raise"""
        prefs = {
            "categories": ["electronics"],
            "price_range": [100, 1000],
            "favorite_brands": ["Apple"],
            "last_viewed_products": []
        }
        # Should not raise
        service.update_user_preferences("user-123", prefs)


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
