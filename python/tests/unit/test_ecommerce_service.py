"""Unit tests for EcommerceService."""
import pytest
from decimal import Decimal
from app.modules.ecommerce.service import EcommerceService
from app.modules.ecommerce.models import Product, ProductCategory, ProductRecommendation, UserPreference

class TestEcommerceService:
    """Test suite for EcommerceService."""

    @pytest.fixture
    def service(self):
        return EcommerceService()

    def test_get_products_returns_limit(self, service):
        # Add many products
        for i in range(5):
            p = Product(id=f"p{i}", name=f"Product {i}", category=ProductCategory.OTHER, price=Decimal("10.00"))
            service.create_product(p)
        products = service.get_products(limit=3)
        assert len(products) == 3

    def test_get_product_existing(self, service):
        p = Product(id="p1", name="Test", category=ProductCategory.FOOD, price=Decimal("5.00"))
        service.create_product(p)
        retrieved = service.get_product("p1")
        assert retrieved.id == "p1"
        assert retrieved.name == "Test"

    def test_get_product_nonexistent_returns_none(self, service):
        assert service.get_product("nonexistent") is None

    def test_create_product_adds_to_recommender(self, service):
        p = Product(id="p100", name="Item", category=ProductCategory.ELECTRONICS, price=Decimal("99.99"))
        result = service.create_product(p)
        assert result == p
        # Product should be in recommender's list
        assert any(prod.id == "p100" for prod in service.recommender.products)

    def test_get_product_recommendations_returns_recommendations(self, service):
        return self._test_get_product_recommendations_returns_recommendations_impl(service)

    def _test_get_product_recommendations_returns_recommendations_impl(self, service):
        # Setup products
        p1 = Product(id="p1", name="Red apple", category=ProductCategory.FOOD, price=Decimal("1.00"))
        p2 = Product(id="p2", name="Green apple", category=ProductCategory.FOOD, price=Decimal("1.20"))
        p3 = Product(id="p3", name="Orange juice", category=ProductCategory.DRINK, price=Decimal("2.00"))
        for p in [p1, p2, p3]:
            service.create_product(p)
        # User preferences: likes FOOD category, price range up to 5
        pref = UserPreference(
            user_id="u1",
            category_preferences=[ProductCategory.FOOD],
            price_range=(0, Decimal("5.00")),
            favorite_brands=[],
            last_viewed_products=[]
        )
        service.update_user_preferences("u1", {"categories": ["food"], "price_range": [0, 5.0]})
        recs = service.get_product_recommendations("u1", limit=10)
        # Should return at most 2 food products (p1, p2) within price range; p3 is drink, not in category preference.
        assert len(recs) >= 1
        ids = [r.product_id for r in recs]
        assert "p1" in ids or "p2" in ids
        # Ensure scores are descending and within 0-1
        scores = [r.score for r in recs]
        assert all(0 <= s <= 1 for s in scores)
        assert scores == sorted(scores, reverse=True)

    def test_get_product_recommendations_empty_when_no_preferences(self, service):
        p = Product(id="px", name="X", category=ProductCategory.OTHER, price=Decimal("10.00"))
        service.create_product(p)
        recs = service.get_product_recommendations("u_without_prefs", limit=5)
        # Should return empty because no preferences set
        assert recs == []

    def test_get_trending_products(self, service):
        return self._test_get_trending_products_impl(service)

    def _test_get_trending_products_impl(self, service):
        # Create several products with varying review_count and rating
        products = []
        for i in range(5):
            rating = Decimal("4.0") + Decimal("0.2")*i
            review_count = i*10
            p = Product(
                id=f"t{i}",
                name=f"Trending {i}",
                category=ProductCategory.OTHER,
                price=Decimal("10.00"),
                rating=rating,
                review_count=review_count
            )
            products.append(p)
            service.create_product(p)
        trending = service.get_trending_products(limit=3)
        assert len(trending) == 3
        # The trending order: sorted by (review_count, rating) descending.
        # p4 should have highest (40, 4.8); p3 next (30, 4.6); p2 next (20, 4.4). So list should be [p4, p3, p2] or based on sorting stability? But any order that respects descending review_count mostly.
        # We'll just check that they are among top
        ids = {p.id for p in trending}
        expected_top = {"t4", "t3", "t2"}
        assert ids == expected_top

    def test_update_user_preferences(self, service):
        # Should not raise any exception
        pref_data = {
            "categories": ["electronics"],
            "price_range": [50, 500],
            "favorite_brands": ["BrandX"],
            "last_viewed_products": ["p1", "p2"]
        }
        service.update_user_preferences("u1", pref_data)
        # Verify preference is stored in recommender
        stored = service.recommender.user_preferences.get("u1")
        assert stored is not None
        assert ProductCategory.ELECTRONICS in stored.category_preferences
        assert stored.price_range == (50, 500)

if __name__ == "__main__":
    pytest.main([__file__, "-v"])