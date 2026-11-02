"""Unit tests for ReviewsEngine."""
import pytest
from datetime import datetime
from modules.Reviews.service.engine import ReviewsEngine, ReviewsService
from modules.Reviews.models.schemas import Review, ReviewReply

class TestReviewsEngine:
    """Test suite for ReviewsEngine."""

    def test_add_review_generates_id_and_timestamp(self):
        engine = ReviewsEngine()
        review = Review(
            user_id="u1",
            target_type="product",
            target_id="p1",
            rating=5,
            title="Great!",
            content="Excellent product"
        )
        result = engine.add_review(review)
        assert result.id is not None
        assert isinstance(result.created_at, datetime)
        assert result.id in engine.reviews

    def test_get_review_existing(self):
        engine = ReviewsEngine()
        review = Review(
            user_id="u1",
            target_type="product",
            target_id="p1",
            rating=4
        )
        added = engine.add_review(review)
        fetched = engine.get_review(added.id)
        assert fetched == added

    def test_get_review_missing(self):
        engine = ReviewsEngine()
        fetched = engine.get_review("nonexistent")
        assert fetched is None

    def test_get_reviews_for_item(self):
        engine = ReviewsEngine()
        r1 = Review(user_id="u1", target_type="product", target_id="p1", rating=5)
        r2 = Review(user_id="u2", target_type="product", target_id="p1", rating=4)
        r3 = Review(user_id="u3", target_type="product", target_id="p2", rating=3)
        engine.add_review(r1)
        engine.add_review(r2)
        engine.add_review(r3)
        product1_reviews = engine.get_reviews_for_item("p1", "product")
        assert len(product1_reviews) == 2
        assert all(r.target_id == "p1" for r in product1_reviews)

    def test_add_reply_success(self):
        engine = ReviewsEngine()
        review = Review(user_id="u1", target_type="product", target_id="p1", rating=5, content="Good")
        review = engine.add_review(review)
        reply = ReviewReply(user_id="admin", content="Thank you!")
        result = engine.add_reply(review.id, reply)
        assert result is not None
        assert result.id is not None
        assert review.id in engine.replies
        assert result in engine.replies[review.id]

    def test_add_reply_review_not_found(self):
        engine = ReviewsEngine()
        reply = ReviewReply(user_id="admin", content="No review")
        result = engine.add_reply("fake_review_id", reply)
        assert result is None

    def test_mark_helpful(self):
        engine = ReviewsEngine()
        review = Review(user_id="u1", target_type="product", target_id="p1", rating=5)
        review = engine.add_review(review)
        marked = engine.mark_helpful(review.id, "user2")
        assert marked is True
        assert review.id in engine.helpful_marks
        assert "user2" in engine.helpful_marks[review.id]

    def test_mark_helpful_duplicate(self):
        engine = ReviewsEngine()
        review = Review(user_id="u1", target_type="product", target_id="p1", rating=5)
        review = engine.add_review(review)
        engine.mark_helpful(review.id, "user2")
        second = engine.mark_helpful(review.id, "user2")
        assert second is False
        assert engine.helpful_marks[review.id].count("user2") == 1

    def test_mark_helpful_invalid_review(self):
        engine = ReviewsEngine()
        marked = engine.mark_helpful("nonexistent", "user")
        assert marked is False

    def test_get_helpful_users(self):
        engine = ReviewsEngine()
        review = Review(user_id="u1", target_type="product", target_id="p1", rating=5)
        review = engine.add_review(review)
        engine.mark_helpful(review.id, "user1")
        engine.mark_helpful(review.id, "user2")
        engine.mark_helpful(review.id, "user3")
        users = engine.get_helpful_users(review.id)
        assert set(users) == {"user1", "user2", "user3"}

    def test_get_helpful_users_empty(self):
        engine = ReviewsEngine()
        review = Review(user_id="u1", target_type="product", target_id="p1", rating=5)
        review = engine.add_review(review)
        users = engine.get_helpful_users(review.id)
        assert users == []

if __name__ == "__main__":
    pytest.main([__file__, "-v"])