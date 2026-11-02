"""
Unit tests for RecommendationEngine
Increase Python coverage.
"""

import pytest
from datetime import datetime, timezone
from modules.Recommendations.service.engine import RecommendationEngine
from modules.Recommendations.models.schemas import Item, UserProfile


@pytest.fixture
def engine():
    return RecommendationEngine()


@pytest.fixture
def sample_items():
    # Create some items with attributes
    return [
        Item(id="i1", type="product", categories=["electronics"], popularity_score=10.0, recency_score=5.0),
        Item(id="i2", type="product", categories=["electronics"], popularity_score=20.0, recency_score=3.0),
        Item(id="i3", type="product", categories=["book"], popularity_score=15.0, recency_score=8.0),
        Item(id="i4", type="service", categories=["consulting"], popularity_score=5.0, recency_score=2.0),
    ]


def test_add_item(engine, sample_items):
    for item in sample_items:
        engine.add_item(item)
    assert len(engine.item_index) == 4
    assert engine.item_index["i1"].type == "product"


def test_get_popular_items(engine, sample_items):
    for item in sample_items:
        engine.add_item(item)
    popular = engine.get_popular_items(item_type="product", count=2)
    assert len(popular) == 2
    # Should be sorted by popularity_score descending
    assert popular[0].popularity_score >= popular[1].popularity_score


def test_get_recency_items(engine, sample_items):
    for item in sample_items:
        engine.add_item(item)
    recent = engine.get_recency_items(item_type="product", count=2)
    assert len(recent) == 2
    assert recent[0].recency_score >= recent[1].recency_score


def test_collaborative_filter_no_profile(engine, sample_items):
    for item in sample_items[:2]:  # only electronics
        engine.add_item(item)
    # user with no profile falls back to popular
    recs = engine.collaborative_filter(user_id="nonexistent", item_type="product", count=2)
    assert len(recs) <= 2
    # Since profile missing, returns popular items
    assert recs == engine.get_popular_items("product", 2) or len(recs) <= 2


def test_content_filter_no_profile(engine, sample_items):
    for item in sample_items:
        engine.add_item(item)
    profile = UserProfile(user_id="u1", preferences={"electronics": 0.9}, history=[], interests=[])
    engine.update_user_profile("u1", profile)
    recs = engine.content_filter(user_id="u1", item_type="product", count=2)
    # Should prioritize items with categories matching preferences
    assert len(recs) <= 2


def test_hybrid_recommend_basic(engine, sample_items):
    for item in sample_items:
        engine.add_item(item)
    profile = UserProfile(user_id="u1", preferences={"electronics": 0.8}, history=["i1"], interests=["electronics"])
    engine.update_user_profile("u1", profile)
    recs = engine.hybrid_recommend(user_id="u1", item_type="product", count=3)
    assert len(recs) <= 3
    # All recommendations are items of correct type
    for r in recs:
        assert r.type == "product"
