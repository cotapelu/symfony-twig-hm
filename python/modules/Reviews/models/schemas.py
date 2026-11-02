from pydantic import BaseModel
from typing import List, Optional
from datetime import datetime

class Review(BaseModel):
    id: str = ""
    user_id: str
    target_type: str
    target_id: str
    rating: int
    title: str = ""
    content: Optional[str] = None
    photos: List[str] = []
    helpful_count: int = 0
    verified_purchase: bool = False
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None

class ReviewReply(BaseModel):
    id: str = ""
    review_id: Optional[str] = None
    user_id: str
    user_type: Optional[str] = None
    content: str
    created_at: Optional[datetime] = None

class ReviewHelpful(BaseModel):
    review_id: str
    user_id: str
    created_at: Optional[datetime] = None

class ReviewSummary(BaseModel):
    target_id: str
    target_type: str
    average_rating: float
    total_reviews: int
    rating_distribution: dict = {}
