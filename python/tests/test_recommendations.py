"""Tests for Recommendations Service"""
import pytest
from modules.Recommendations.recommendation_engine import RecommendationEngine
from modules.Recommendations.models.schemas import UserProfile, Item


class TestRecommendationEngine:
    def setup_method(self):
        return self._setup_method_impl()

    def _setup_method_impl(self):
        self.engine = RecommendationEngine()
        # Add some test items
        self.engine.add_item(Item(
            id="item1",
            type="product",
            categories=["electronics", "phone"],
            popularity_score=0.9,
            recency_score=0.8
        ))
        self.engine.add_item(Item(
            id="item2", 
            type="product",
            categories=["fashion", "clothing"],
            popularity_score=0.7,
            recency_score=0.6
        ))
        self.engine.add_item(Item(
            id="item3",
            type="product",
            categories=["electronics", "laptop"],
            popularity_score=0.85,
            recency_score=0.75
        ))
    
    def test_add_item(self):
        """Test adding items"""
        assert len(self.engine.item_index) >= 3
    
    def test_update_user_profile(self):
        """Test updating user profile"""
        profile = UserProfile(
            user_id="test_user",
            history=["item1"],
            preferences={"electronics": 0.5}
        )
        self.engine.update_user_profile("test_user", profile)
        assert "test_user" in self.engine.user_profiles
    
    def test_get_popular_items(self):
        """Test getting popular items"""
        items = self.engine.get_popular_items("product", 10)
        assert isinstance(items, list)
    
    def test_get_recency_items(self):
        """Test getting recent items"""
        items = self.engine.get_recency_items("product", 10)
        assert isinstance(items, list)
    
    def test_collaborative_filter(self):
        """Test collaborative filtering"""
        profile = UserProfile(
            user_id="user1",
            history=["item1"],
            preferences={"electronics": 0.8}
        )
        self.engine.update_user_profile("user1", profile)
        results = self.engine.collaborative_filter("user1", "product", 5)
        assert isinstance(results, list)
    
    def test_content_filter(self):
        """Test content-based filtering"""
        profile = UserProfile(
            user_id="user1",
            history=[],
            preferences={},
            interests=["electronics"]
        )
        self.engine.update_user_profile("user1", profile)
        results = self.engine.content_filter("user1", "product", 5)
        assert isinstance(results, list)
    
    def test_hybrid_recommend(self):
        """Test hybrid recommendations"""
        profile = UserProfile(
            user_id="user1",
            history=[],
            preferences={"electronics": 0.8}
        )
        self.engine.update_user_profile("user1", profile)
        results = self.engine.hybrid_recommend("user1", "product", 5)
        assert isinstance(results, list)
    
    def test_similar_items(self):
        """Test finding similar items"""
        results = self.engine.similar_items("item1", 5)
        assert isinstance(results, list)
    
    def test_trending_now(self):
        """Test trending items"""
        results = self.engine.trending_now("product", 10)
        assert isinstance(results, list)


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
