from pydantic import BaseModel
from typing import List, Dict, Optional
from datetime import datetime

class UserProfile(BaseModel):
    user_id: str
    preferences: Dict[str, float]
    history: List[str]
    demographics: Optional[Dict] = None
    interests: List[str] = []
    engagement_scores: Dict[str, float] = {}

class Item(BaseModel):
    id: str
    type: str
    categories: List[str]
    attributes: Dict = {}
    popularity_score: float = 0.0
    recency_score: float = 0.0
    vector: Optional[List[float]] = None

class RecommendationRequest(BaseModel):
    user_id: str
    item_type: str
    count: int = 10
    context: Optional[Dict] = None
    exclude_items: List[str] = []

class RecommendationResponse(BaseModel):
    user_id: str
    recommendations: List['RecommendedItem']
    model_used: str
    timestamp: datetime = datetime.utcnow()
class RecommendedItem(BaseModel):
    item_id: str
    item_type: str
    score: float
    reason: str
    metadata: Optional[Dict] = None

class CollaborativeFilterModel(BaseModel):
    user_item_matrix: Dict[str, Dict[str, float]]
    user_similarity: Dict[str, Dict[str, float]]
    item_similarity: Dict[str, Dict[str, float]]

class ContentBasedModel(BaseModel):
    item_vectors: Dict[str, List[float]]
    user_profile_vector: Optional[List[float]] = None

class HybridWeights(BaseModel):
    collaborative_weight: float = 0.3
    content_weight: float = 0.3
    popularity_weight: float = 0.2
    recency_weight: float = 0.2

class ABTestConfig(BaseModel):
    test_id: str
    variants: List[str]
    traffic_split: Dict[str, float]
    start_date: datetime
    end_date: datetime
    metrics: List[str]
