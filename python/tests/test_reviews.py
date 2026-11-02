import pytest
from modules.Reviews.service.engine import ReviewsEngine
from modules.Reviews.models.schemas import Review, ReviewReply, ReviewHelpful

class TestReviewsEngine:
    def setup_method(self):
        self.engine = ReviewsEngine()
    
    def test_add_review(self):
        review = Review(
            user_id="user_1",
            target_type="product",
            target_id="prod_123",
            rating=5,
            title="Great product",
            content="This product is amazing!"
        )
        result = self.engine.add_review(review)
        
        assert result.id is not None
        assert result.created_at is not None
        assert result.rating == 5
    
    def test_get_review(self):
        review = Review(
            user_id="user_1",
            target_type="product",
            target_id="prod_123",
            rating=4,
            content="Good product"
        )
        added = self.engine.add_review(review)
        
        retrieved = self.engine.get_review(added.id)
        
        assert retrieved is not None
        assert retrieved.id == added.id
        assert retrieved.rating == 4
    
    def test_get_review_not_found(self):
        result = self.engine.get_review("nonexistent")
        
        assert result is None
    
    def test_get_reviews_for_item(self):
        return self._test_get_reviews_for_item_impl()

    def _test_get_reviews_for_item_impl(self):
        self.engine.add_review(Review(
            user_id="user_1",
            target_type="product",
            target_id="prod_123",
            rating=5,
            content="Great!"
        ))
        self.engine.add_review(Review(
            user_id="user_2",
            target_type="product",
            target_id="prod_123",
            rating=4,
            content="Good"
        ))
        self.engine.add_review(Review(
            user_id="user_3",
            target_type="product",
            target_id="prod_456",
            rating=3,
            content="Different item"
        ))
        
        reviews = self.engine.get_reviews_for_item("prod_123", "product")
        
        assert len(reviews) == 2
    
    def test_add_reply(self):
        return self._test_add_reply_impl()

    def _test_add_reply_impl(self):
        review = Review(
            user_id="user_1",
            target_type="product",
            target_id="prod_123",
            rating=5,
            content="Great!"
        )
        added = self.engine.add_review(review)
        
        reply = ReviewReply(
            review_id=added.id,
            user_id="seller_1",
            user_type="seller",
            content="Thank you for your feedback!"
        )
        result = self.engine.add_reply(added.id, reply)
        
        assert result is not None
        assert result.id is not None
        assert result.content == "Thank you for your feedback!"
    
    def test_add_reply_nonexistent_review(self):
        reply = ReviewReply(
            review_id="nonexistent",
            user_id="seller_1",
            user_type="seller",
            content="Reply"
        )
        result = self.engine.add_reply("nonexistent", reply)
        
        assert result is None
    
    def test_mark_helpful(self):
        review = Review(
            user_id="user_1",
            target_type="product",
            target_id="prod_123",
            rating=5,
            content="Great!"
        )
        added = self.engine.add_review(review)
        
        result = self.engine.mark_helpful(added.id, "user_2")
        
        assert result is True
    
    def test_mark_helpful_already_marked(self):
        review = Review(
            user_id="user_1",
            target_type="product",
            target_id="prod_123",
            rating=5,
            content="Great!"
        )
        added = self.engine.add_review(review)
        
        self.engine.mark_helpful(added.id, "user_2")
        result = self.engine.mark_helpful(added.id, "user_2")
        
        assert result is False
    
    def test_mark_helpful_nonexistent_review(self):
        result = self.engine.mark_helpful("nonexistent", "user_1")
        
        assert result is False
    
    def test_get_helpful_users(self):
        review = Review(
            user_id="user_1",
            target_type="product",
            target_id="prod_123",
            rating=5,
            content="Great!"
        )
        added = self.engine.add_review(review)
        
        self.engine.mark_helpful(added.id, "user_2")
        self.engine.mark_helpful(added.id, "user_3")
        
        users = self.engine.get_helpful_users(added.id)
        
        assert len(users) == 2
        assert "user_2" in users
        assert "user_3" in users
    
    def test_get_helpful_users_nonexistent(self):
        users = self.engine.get_helpful_users("nonexistent")
        
        assert users == []


class TestReviewSchemas:
    def test_review_creation(self):
        review = Review(
            user_id="user_1",
            target_type="product",
            target_id="prod_123",
            rating=5,
            title="Great!",
            content="Amazing product"
        )
        
        assert review.user_id == "user_1"
        assert review.rating == 5
        assert review.helpful_count == 0
        assert review.verified_purchase is False
    
    def test_review_with_photos(self):
        review = Review(
            user_id="user_1",
            target_type="product",
            target_id="prod_123",
            rating=4,
            content="Good",
            photos=["photo1.jpg", "photo2.jpg"]
        )
        
        assert len(review.photos) == 2
    
    def test_review_reply(self):
        reply = ReviewReply(
            review_id="review_1",
            user_id="seller_1",
            user_type="seller",
            content="Thank you!"
        )
        
        assert reply.review_id == "review_1"
        assert reply.user_type == "seller"
    
    def test_review_helpful(self):
        helpful = ReviewHelpful(
            review_id="review_1",
            user_id="user_2"
        )
        
        assert helpful.review_id == "review_1"
        assert helpful.user_id == "user_2"
