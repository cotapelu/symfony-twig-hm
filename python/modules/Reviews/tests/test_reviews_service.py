import pytest
from datetime import datetime
from modules.Reviews.service.engine import ReviewsEngine
from modules.Reviews.models.schemas import Review, ReviewReply

@pytest.fixture
def engine():
    return ReviewsEngine()

def test_init(engine):
    assert engine is not None
    assert engine.reviews == {}
    assert engine.replies == {}

def test_add_review(engine):
    review = Review(
        id="",
        user_id="user123",
        target_id="item456",
        target_type="product",
        rating=5,
        comment="Great product!"
    )
    
    added = engine.add_review(review)
    
    assert added is not None
    assert added.rating == 5
    assert added.id in engine.reviews

def test_get_review(engine):
    review = Review(id="", user_id="user123", target_id="item456", target_type="product", rating=4)
    added = engine.add_review(review)
    
    result = engine.get_review(added.id)
    assert result is not None
    assert result.rating == 4

def test_get_review_not_found(engine):
    result = engine.get_review("nonexistent")
    assert result is None

def test_get_reviews_for_item(engine):
    review1 = Review(id="", user_id="user1", target_id="item1", target_type="product", rating=5)
    review2 = Review(id="", user_id="user2", target_id="item1", target_type="product", rating=4)
    review3 = Review(id="", user_id="user3", target_id="item2", target_type="product", rating=3)
    
    engine.add_review(review1)
    engine.add_review(review2)
    engine.add_review(review3)
    
    reviews = engine.get_reviews_for_item("item1", "product")
    assert len(reviews) == 2

def test_get_reviews_for_item_empty(engine):
    reviews = engine.get_reviews_for_item("nonexistent", "product")
    assert len(reviews) == 0

def test_add_reply(engine):
    review = Review(id="", user_id="user123", target_id="item456", target_type="product", rating=5)
    added = engine.add_review(review)
    
    reply = ReviewReply(id="", review_id=added.id, user_id="admin", content="Thank you!")
    replied = engine.add_reply(added.id, reply)
    
    assert replied is not None
    assert len(engine.replies.get(added.id, [])) == 1

def test_add_reply_review_not_found(engine):
    reply = ReviewReply(id="", review_id="nonexistent", user_id="admin", content="Thanks")
    result = engine.add_reply("nonexistent", reply)
    assert result is None

def test_mark_helpful(engine):
    review = Review(id="", user_id="user123", target_id="item456", target_type="product", rating=5)
    added = engine.add_review(review)
    
    result = engine.mark_helpful(added.id, "user456")
    assert result is True
    
    helpful = engine.get_helpful_users(added.id)
    assert "user456" in helpful

def test_mark_helpful_already_marked(engine):
    review = Review(id="", user_id="user123", target_id="item456", target_type="product", rating=5)
    added = engine.add_review(review)
    
    engine.mark_helpful(added.id, "user456")
    result = engine.mark_helpful(added.id, "user456")
    assert result is False

def test_mark_helpful_review_not_found(engine):
    result = engine.mark_helpful("nonexistent", "user456")
    assert result is False

def test_get_helpful_users(engine):
    review = Review(id="", user_id="user123", target_id="item456", target_type="product", rating=5)
    added = engine.add_review(review)
    
    engine.mark_helpful(added.id, "user1")
    engine.mark_helpful(added.id, "user2")
    
    users = engine.get_helpful_users(added.id)
    assert len(users) == 2

def test_get_helpful_users_empty(engine):
    users = engine.get_helpful_users("nonexistent")
    assert len(users) == 0
