from typing import Optional, Dict, Any
from datetime import datetime
from pydantic import BaseModel, Field
from uuid import UUID, uuid4


class Recommendation(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid4()))
    user_id: str
    item_id: str
    item_type: str
    score: float
    reason: str
    metadata: Dict[str, Any] = Field(default_factory=dict)
    created_at: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        from_attributes = True
        json_schema_extra = {
            "example": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "user_id": "user123",
                "item_id": "product456",
                "item_type": "product",
                "score": 0.95,
                "reason": "Based on your browsing history",
                "metadata": {"category": "electronics", "brand": "Apple"},
                "created_at": "2024-01-01T00:00:00Z"
            }
        }
