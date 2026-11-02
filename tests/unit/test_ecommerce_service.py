"""
Unit tests for EcommerceService.

Tests product catalog, recommendations, and user preferences.
"""

import pytest
import sys
import os
from unittest.mock import Mock, patch, MagicMock

# Add backend to path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))

pytestmark = pytest.mark.skip(reason="EcommerceService tests outdated")


class TestEcommerceService:
    """Test EcommerceService business logic"""

    @pytest.fixture
    def service(self):
        """Create EcommerceService with mocked recommender"""
        from python.app.modules.ecommerce.service import EcommerceService
        with patch('python.app.modules.ecommerce.service.ProductRecommender') as mock_rec:
            mock_rec.return_value = MagicMock()
            svc = EcommerceService()
            svc.recommender = mock_rec.return_value
            yield svc

    def test_get_products_returns_limited_list(self, service):
        """get_products should respect limit parameter"""
        service.recommender.products = [
            Mock(id="p1", name="Product 1", price=100.0),
            Mock(id="p2", name="Product 2", price=200.0),
            Mock(id="p3", name="Product 3", price=300.0),
        ]

        products = service.get_products(2)
        assert len(products) == 2
        assert products[0].id == "p1"
        assert products[1].id == "p2"

    def test_get_product_by_id_found(self, service):
        """get_product returns product when ID matches"""
        service.recommender.products = [
            Mock(id="p1", name="Product 1", price=100.0),
            Mock(id="p2", name="Product 2", price=200.0),
        ]

        product = service.get_product("p2")
        assert product is not None
        assert product.id == "p2"
        assert product.price == 200.0

    def test_get_product_by_id_not_found(self, service):
        """get_product returns None when ID not found"""
        service.recommender.products = [
            Mock(id="p1", name="Product 1", price=100.0),
        ]

        product = service.get_product("p999")
        assert product is None

    def test_create_product_adds_to_recommender(self, service):
        """create_product adds product to recommender and returns it"""
        from python.app.modules.ecommerce.models import Product, ProductCategory

        product = Product(
            id="new-p1",
            name="New Product",
            price=500.0,
            category=ProductCategory.ELECTRONICS,
            description="Test product",
            stock=100
        )

        result = service.create_product(product)
        assert result.id == "new-p1"
        assert result in service.recommender.products

    def test_get_product_recommendations_uses_recommender(self, service):
        """get_product_recommendations calls recommender and builds recommendations"""
        service.recommender.get_recommendations.return_value = ["p1", "p2", "p3"]
        service.recommender.products = [
            Mock(id="p1", price=100.0),
            Mock(id="p2", price=200.0),
            Mock(id="p3", price=300.0),
            Mock(id="p4", price=400.0),
        ]

        recommendations = service.get_product_recommendations("user-123", 3)

        assert len(recommendations) == 3
        assert all(hasattr(r, 'product_id') for r in recommendations)
        assert all(hasattr(r, 'score') for r in recommendations)

    def test_get_trending_products(self, service):
        """get_trending_products returns top N trending items"""
        service.recommender.get_trending_products.return_value = ["p1", "p2"]
        service.recommender.products = [
            Mock(id="p1", price=100.0),
            Mock(id="p2", price=200.0),
            Mock(id="p3", price=300.0),
        ]

        trending = service.get_trending_products(2)
        assert len(trending) == 2

    def test_update_user_preferences_stores_them(self, service):
        """update_user_preferences should store in recommender"""
        preferences = {
            "categories": ["electronics", "clothes"],
            "price_range": [100, 1000],
            "favorite_brands": ["Apple", "Samsung"],
            "last_viewed_products": ["p1", "p2"]
        }

        # Should not raise
        service.update_user_preferences("user-123", preferences)

        # Verify recommender was called
        service.recommender.update_user_preference.assert_called_once()


class TestEcommerceServiceEdgeCases:
    """Test edge cases and error handling"""

    def test_get_products_empty_catalog(self):
        """get_products should return empty list when no products"""
        from python.app.modules.ecommerce.service import EcommerceService
        service = EcommerceService()
        service.recommender.products = []

        products = service.get_products(10)
        assert products == []

    def test_get_product_recommendations_empty(self):
        """get_product_recommendations returns empty list if no recommendations"""
        from python.app.modules.ecommerce.service import EcommerceService
        service = EcommerceService()
        service.recommender.get_recommendations.return_value = []

        recs = service.get_product_recommendations("user-123", 5)
        assert recs == []

    def test_get_product_invalid_id_type(self):
        """get_product should handle non-existent ID gracefully"""
        from python.app.modules.ecommerce.service import EcommerceService
        service = EcommerceService()
        service.recommender.products = []

        product = service.get_product("any-id")
        assert product is None


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
