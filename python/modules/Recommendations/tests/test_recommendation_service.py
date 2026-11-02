import pytest
import os
import tempfile
from datetime import datetime, timezone
from modules.Recommendations.service.recommendation_service import (
    RecommendationService, RecommendationItem, UserPreference,
    RecommendationType, AlgorithmType
)

@pytest.fixture
def temp_storage():
    with tempfile.NamedTemporaryFile(mode='w', suffix='.json', delete=False) as f:
        yield f.name
    os.unlink(f.name)

@pytest.fixture
def service(temp_storage):
    return RecommendationService(storage_path=temp_storage)

def test_init(service):
    assert service is not None
    assert service.recommendations == {}
    assert service.user_preferences == {}

def test_add_item(service):
    service.add_item(
        "item123", "product", "electronics", ["tech", "gadget"]
    )
    
    assert "item123" in service.item_scores
    assert service.item_scores["item123"]["type"] == "product"

def test_add_item_invalid_type(service):
    with pytest.raises(ValueError):
        service.add_item("item123", "invalid", "electronics", [])

def test_remove_item(service):
    service.add_item("item123", "product", "electronics", [])
    result = service.remove_item("item123")
    assert result is True
    assert "item123" not in service.item_scores

def test_remove_item_not_found(service):
    result = service.remove_item("nonexistent")
    assert result is True  # Returns True regardless

def test_update_user_preference_new_user(service):
    service.update_user_preference("user123", category="electronics", tags=["tech"])
    
    prefs = service.user_preferences.get("user123")
    assert prefs is not None
    assert "electronics" in prefs.preferred_categories
    assert "tech" in prefs.preferred_tags

def test_update_user_preference_existing_user(service):
    service.update_user_preference("user123", category="electronics")
    service.update_user_preference("user123", category="books")
    
    prefs = service.user_preferences.get("user123")
    assert "electronics" in prefs.preferred_categories
    assert "books" in prefs.preferred_categories

def test_record_interaction(service):
    service.add_item("item123", "product", "electronics", [])
    service.record_interaction("user123", "item123", "product")
    
    assert service.interaction_counts["item123"] == 1
    assert "user123" in service.user_preferences

def test_get_recommendations_invalid_type(service):
    with pytest.raises(ValueError):
        service.get_recommendations("user123", "invalid_type")

def test_get_recommendations_trending(service):
    service.add_item("item1", "product", "electronics", [])
    service.record_interaction("user1", "item1", "product")
    
    recommendations = service.get_recommendations(
        "user123", RecommendationType.TRENDING.value
    )
    
    assert len(recommendations) > 0

def test_get_recommendations_personalized(service):
    service.add_item("item123", "product", "electronics", ["tech"])
    service.update_user_preference("user123", category="electronics")
    
    recommendations = service.get_recommendations(
        "user123", RecommendationType.PERSONALIZED.value
    )
    
    assert isinstance(recommendations, list)

def test_get_user_preferences(service):
    service.update_user_preference("user123", category="electronics")
    
    prefs = service.get_user_preferences("user123")
    assert prefs is not None
    assert prefs.user_id == "user123"

def test_get_user_preferences_not_found(service):
    prefs = service.get_user_preferences("nonexistent")
    assert prefs is None

def test_get_statistics(service):
    service.add_item("item1", "product", "electronics", [])
    service.record_interaction("user1", "item1", "product")
    
    stats = service.get_recommendation_statistics()
    
    assert stats["total_items"] == 1
    assert stats["total_users"] == 1
    assert stats["total_interactions"] == 1

def test_recommendation_item():
    item = RecommendationItem(
        item_id="item123",
        item_type="product",
        score=0.95,
        reason="test reason"
    )
    assert item.item_id == "item123"
    assert item.score == 0.95

def test_user_preference():
    prefs = UserPreference(
        user_id="user123",
        preferred_categories=["electronics", "books"],
        preferred_tags=["tech"]
    )
    assert prefs.user_id == "user123"
    assert "electronics" in prefs.preferred_categories

def test_recommendation_type_enum():
    assert RecommendationType.PERSONALIZED.value == "personalized"
    assert RecommendationType.TRENDING.value == "trending"
    assert RecommendationType.SIMILAR.value == "similar"

def test_algorithm_type_enum():
    assert AlgorithmType.COLLABORATIVE.value == "collaborative"
    assert AlgorithmType.CONTENT_BASED.value == "content_based"

def test_get_similar_recommendations(service):
    service.add_item("item1", "product", "electronics", ["tech", "gadget"])
    service.add_item("item2", "product", "electronics", ["tech", "device"])
    service.update_user_preference("user123", item_id="item1")
    
    recommendations = service.get_recommendations(
        "user123", RecommendationType.SIMILAR.value
    )
    
    assert isinstance(recommendations, list)

def test_get_collaborative_recommendations(service):
    service.add_item("item1", "product", "electronics", ["tech"])
    service.update_user_preference("user1", category="electronics")
    service.update_user_preference("user2", category="electronics")
    
    recommendations = service.get_recommendations(
        "user2", RecommendationType.COLLABORATIVE.value
    )
    
    assert isinstance(recommendations, list)
import builtins

def test_ensure_storage_directory_creates(tmp_path):
    storage_path = tmp_path / "new_dir" / "data.json"
    assert not storage_path.parent.exists()
    service = RecommendationService(storage_path=str(storage_path))
    assert storage_path.parent.exists()

def test_load_from_storage_missing_file(tmp_path):
    storage_path = tmp_path / "missing.json"
    if storage_path.exists():
        storage_path.unlink()
    service = RecommendationService(storage_path=str(storage_path))
    assert service.user_preferences == {}
    assert service.item_scores == {}
    assert service.interaction_counts == {}
    assert service.recommendations == {}

def test_save_and_load_persistence(tmp_path):
    storage_path = str(tmp_path / "persist.json")
    service1 = RecommendationService(storage_path=storage_path)
    service1.add_item("item1", "product", "electronics", ["tech"])
    service1.update_user_preference("user1", category="electronics", tags=["tech"], item_id="item1")
    service1.record_interaction("user1", "item1", "product")
    recs = service1.get_recommendations("user1", RecommendationType.PERSONALIZED.value)
    assert len(recs) >= 1
    service2 = RecommendationService(storage_path=storage_path)
    assert "user1" in service2.user_preferences
    prefs = service2.user_preferences["user1"]
    assert "electronics" in prefs.preferred_categories
    assert "tech" in prefs.preferred_tags
    assert any(h['item_id'] == "item1" for h in prefs.interaction_history)
    assert "item1" in service2.item_scores
    assert service2.item_scores["item1"]["category"] == "electronics"
    assert "tech" in service2.item_scores["item1"]["tags"]
    assert service2.interaction_counts.get("item1") == 1
    cache_key = f"user1:{RecommendationType.PERSONALIZED.value}"
    assert cache_key in service2.recommendations
    assert isinstance(service2.recommendations[cache_key], list)

def test_get_recommendations_popularity(service):
    service.add_item("item1", "product", "electronics", [])
    service.record_interaction("user1", "item1", "product")
    recommendations = service.get_recommendations("user123", RecommendationType.POPULARITY.value)
    assert isinstance(recommendations, list)
    assert len(recommendations) >= 1

def test_personalized_no_preferences(service):
    service.add_item("item1", "product", "electronics", [])
    service.record_interaction("user1", "item1", "product")
    recommendations = service.get_recommendations("user123", RecommendationType.PERSONALIZED.value)
    assert isinstance(recommendations, list)

def test_personalized_with_category_filter(service):
    service.add_item("item1", "product", "electronics", [])
    service.add_item("item2", "product", "books", [])
    service.record_interaction("user1", "item1", "product")
    service.record_interaction("user2", "item2", "product")
    service.update_user_preference("user123", category="electronics")
    recommendations = service.get_recommendations("user123", RecommendationType.PERSONALIZED.value, category="electronics")
    for rec in recommendations:
        assert rec.item_id == "item1"

def test_personalized_with_tags(service):
    service.add_item("item1", "product", "electronics", ["tech", "gadget"])
    service.add_item("item2", "product", "electronics", ["home", "kitchen"])
    # Set both category and tags to ensure both items get some score
    service.update_user_preference("user123", category="electronics", tags=["tech"])
    recommendations = service.get_recommendations("user123", RecommendationType.PERSONALIZED.value)
    rec1 = next((r for r in recommendations if r.item_id == "item1"), None)
    rec2 = next((r for r in recommendations if r.item_id == "item2"), None)
    assert rec1 is not None
    assert rec2 is not None
    assert rec1.score > rec2.score

def test_trending_with_category_filter(service):
    service.add_item("item1", "product", "electronics", [])
    service.add_item("item2", "product", "books", [])
    service.record_interaction("user1", "item1", "product")
    service.record_interaction("user2", "item2", "product")
    recommendations = service.get_recommendations("user123", RecommendationType.TRENDING.value, category="electronics")
    assert len(recommendations) == 1
    assert recommendations[0].item_id == "item1"

def test_similar_no_interaction_history(service):
    service.add_item("item1", "product", "electronics", [])
    service.record_interaction("user1", "item1", "product")
    service.update_user_preference("user123", category="electronics")
    recommendations = service.get_recommendations("user123", RecommendationType.SIMILAR.value)
    assert any(rec.item_id == "item1" for rec in recommendations)

def test_collaborative_no_preferences(service):
    service.add_item("item1", "product", "electronics", [])
    service.record_interaction("user1", "item1", "product")
    recommendations = service.get_recommendations("user999", RecommendationType.COLLABORATIVE.value)
    assert any(rec.item_id == "item1" for rec in recommendations)

def test_collaborative_with_interaction_history(service):
    service.add_item("item1", "product", "electronics", ["tech"])
    service.add_item("item2", "product", "electronics", ["gadget"])
    service.update_user_preference("userA", category="electronics")
    service.record_interaction("userA", "item1", "product")
    service.update_user_preference("userB", category="electronics")
    recommendations = service.get_recommendations("userB", RecommendationType.COLLABORATIVE.value)
    assert any(rec.item_id == "item1" for rec in recommendations)

def test_remove_item_with_interaction_count(service):
    service.add_item("item1", "product", "electronics", [])
    service.record_interaction("user1", "item1", "product")
    assert "item1" in service.interaction_counts
    result = service.remove_item("item1")
    assert result is True
    assert "item1" not in service.item_scores
    assert "item1" not in service.interaction_counts

def test_save_to_storage_exception(service, monkeypatch):
    def mock_open(*args, **kwargs):
        raise IOError("disk error")
    monkeypatch.setattr(builtins, "open", mock_open)
    service.save_to_storage()
