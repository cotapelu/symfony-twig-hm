"""Tests for Recommendation Engine"""
import pytest
from modules.Recommendations.service.engine import RecommendationEngine
from modules.Recommendations.models.schemas import UserProfile, Item


@pytest.fixture
def engine():
    """Create a RecommendationEngine instance"""
    return RecommendationEngine()


@pytest.fixture
def sample_items():
    return _sample_items_impl()

def _sample_items_impl():
    """Create sample items for testing"""
    return [
        Item(
            id="item_1",
            name="Product 1",
            type="product",
            categories=["electronics", "phones"],
            popularity_score=0.9,
            recency_score=0.8
        ),
        Item(
            id="item_2",
            name="Product 2",
            type="product",
            categories=["electronics", "laptops"],
            popularity_score=0.8,
            recency_score=0.7
        ),
        Item(
            id="item_3",
            name="Product 3",
            type="product",
            categories=["fashion", "clothing"],
            popularity_score=0.7,
            recency_score=0.9
        ),
        Item(
            id="item_4",
            name="Product 4",
            type="product",
            categories=["electronics", "phones"],
            popularity_score=0.6,
            recency_score=0.6
        ),
    ]


@pytest.fixture
def sample_user_profile():
    """Create a sample user profile"""
    return UserProfile(
        user_id="user_1",
        preferences={"electronics": 0.8, "phones": 0.7},
        interests=["technology", "gadgets"],
        history=["item_1"]
    )


def test_add_item(engine, sample_items):
    """Test adding items to the engine"""
    for item in sample_items:
        engine.add_item(item)
    
    assert len(engine.item_index) == 4
    assert "item_1" in engine.item_index


def test_update_user_profile(engine, sample_user_profile):
    """Test updating user profile"""
    engine.update_user_profile("user_1", sample_user_profile)
    
    assert "user_1" in engine.user_profiles
    assert engine.user_profiles["user_1"].user_id == "user_1"


def test_get_popular_items(engine, sample_items):
    """Test getting popular items"""
    for item in sample_items:
        engine.add_item(item)
    
    popular = engine.get_popular_items("product", 2)
    
    assert len(popular) == 2
    assert popular[0].id == "item_1"  # highest popularity


def test_get_popular_items_exclude(engine, sample_items):
    """Test getting popular items with exclusions"""
    for item in sample_items:
        engine.add_item(item)
    
    popular = engine.get_popular_items("product", 2, exclude=["item_1"])
    
    assert len(popular) == 2
    assert "item_1" not in [p.id for p in popular]


def test_get_recency_items(engine, sample_items):
    """Test getting recent items"""
    for item in sample_items:
        engine.add_item(item)
    
    recency = engine.get_recency_items("product", 2)
    
    assert len(recency) == 2
    assert recency[0].id == "item_3"  # highest recency


def test_collaborative_filter(engine, sample_items, sample_user_profile):
    """Test collaborative filtering"""
    for item in sample_items:
        engine.add_item(item)
    engine.update_user_profile("user_1", sample_user_profile)
    
    recommendations = engine.collaborative_filter("user_1", "product", 2)
    
    assert len(recommendations) <= 2
    # Should recommend items not in history
    assert "item_1" not in [r.id for r in recommendations]


def test_collaborative_filter_new_user(engine, sample_items):
    """Test collaborative filtering for new user (no profile)"""
    for item in sample_items:
        engine.add_item(item)
    
    recommendations = engine.collaborative_filter("new_user", "product", 2)
    
    # Should return popular items for new users
    assert len(recommendations) == 2
    assert recommendations[0].id == "item_1"


def test_content_filter(engine, sample_items, sample_user_profile):
    """Test content-based filtering"""
    for item in sample_items:
        engine.add_item(item)
    engine.update_user_profile("user_1", sample_user_profile)
    
    recommendations = engine.content_filter("user_1", "product", 2)
    
    assert len(recommendations) <= 2


def test_content_filter_no_preferences(engine, sample_items):
    """Test content filtering without user preferences"""
    for item in sample_items:
        engine.add_item(item)
    
    # User without preferences
    profile = UserProfile(user_id="user_2", preferences={}, interests=[], history=[])
    engine.update_user_profile("user_2", profile)
    
    recommendations = engine.content_filter("user_2", "product", 2)
    
    # Should return popular items
    assert len(recommendations) == 2


def test_hybrid_recommend(engine, sample_items, sample_user_profile):
    """Test hybrid recommendation"""
    for item in sample_items:
        engine.add_item(item)
    engine.update_user_profile("user_1", sample_user_profile)
    
    recommendations = engine.hybrid_recommend("user_1", "product", 2)
    
    assert len(recommendations) <= 2


def test_hybrid_recommend_with_context(engine, sample_items, sample_user_profile):
    """Test hybrid recommendation with context"""
    for item in sample_items:
        engine.add_item(item)
    engine.update_user_profile("user_1", sample_user_profile)
    
    recommendations = engine.hybrid_recommend(
        "user_1", "product", 2, 
        context={"time_of_day": "evening"}
    )
    
    assert len(recommendations) <= 2


def test_similar_items(engine, sample_items):
    """Test finding similar items"""
    for item in sample_items:
        engine.add_item(item)
    
    similar = engine.similar_items("item_1", 2)
    
    assert len(similar) <= 2
    # item_1 and item_4 share "phones" category
    assert "item_4" in [s.id for s in similar]


def test_similar_items_not_found(engine):
    """Test finding similar items for non-existent item"""
    similar = engine.similar_items("nonexistent", 5)
    
    assert len(similar) == 0


def test_trending_now(engine, sample_items):
    """Test getting trending items"""
    for item in sample_items:
        engine.add_item(item)
    
    trending = engine.trending_now("product", 3)
    
    assert len(trending) == 3
    # Should be sorted by popularity + recency
    assert trending[0].popularity_score >= trending[1].popularity_score


def test_trending_now_empty(engine):
    """Test trending with no items"""
    trending = engine.trending_now("product", 10)
    
    assert len(trending) == 0


def test_calculate_similarity(engine, sample_items):
    """Test similarity calculation"""
    for item in sample_items:
        engine.add_item(item)
    
    item1 = engine.item_index["item_1"]
    item2 = engine.item_index["item_4"]
    
    # Both have "phones" and "electronics" categories
    similarity = engine._calculate_similarity(item1, item2)
    
    # Common: 2, Total: 2, Similarity = 1.0
    assert similarity == 1.0


def test_calculate_similarity_no_overlap(engine, sample_items):
    """Test similarity with no category overlap"""
    for item in sample_items:
        engine.add_item(item)
    
    item1 = engine.item_index["item_1"]  # electronics, phones
    item3 = engine.item_index["item_3"]  # fashion, clothing
    
    similarity = engine._calculate_similarity(item1, item3)
    
    # No common categories
    assert similarity == 0.0


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
