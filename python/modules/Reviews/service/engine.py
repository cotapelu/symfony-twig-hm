from typing import Dict, List, Optional
import uuid
from datetime import datetime
from models.schemas import Review, ReviewReply, ReviewHelpful, ReviewSummary

class ReviewsEngine:
    def __init__(self):
        self.reviews: Dict[str, Review] = {}
        self.replies: Dict[str, List[ReviewReply]] = {}
        self.helpful_marks: Dict[str, List[str]] = {}

    def add_review(self, review: Review) -> Review:
        if not review.id:
            review.id = str(uuid.uuid4())
        review.created_at = datetime.now()
        self.reviews[review.id] = review
        return review

    def get_review(self, review_id: str) -> Optional[Review]:
        return self.reviews.get(review_id)

    def get_reviews_for_item(self, item_id: str, item_type: str) -> List[Review]:
        return [r for r in self.reviews.values() 
                if r.target_id == item_id and r.target_type == item_type]

    def add_reply(self, review_id: str, reply: ReviewReply) -> Optional[ReviewReply]:
        if review_id not in self.reviews:
            return None
        if not reply.id:
            reply.id = str(uuid.uuid4())
        # Ensure foreign key link
        reply.review_id = review_id
        # Set default user_type if not provided
        if not reply.user_type:
            reply.user_type = "admin"
        reply.created_at = datetime.now()
        if review_id not in self.replies:
            self.replies[review_id] = []
        self.replies[review_id].append(reply)
        return reply

    def mark_helpful(self, review_id: str, user_id: str) -> bool:
        if review_id not in self.reviews:
            return False
        if review_id not in self.helpful_marks:
            self.helpful_marks[review_id] = []
        if user_id not in self.helpful_marks[review_id]:
            self.helpful_marks[review_id].append(user_id)
            return True
        return False

    def get_helpful_users(self, review_id: str) -> List[str]:
        return self.helpful_marks.get(review_id, [])

# Alias for compatibility
ReviewsService = ReviewsEngine
