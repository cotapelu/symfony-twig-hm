from typing import Optional, Dict, Any
from datetime import datetime
from pydantic import BaseModel, Field
from uuid import UUID, uuid4


class AnalyticsEvent(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid4()))
    user_id: str
    event_type: str
    event_name: str
    properties: Dict[str, Any] = Field(default_factory=dict)
    timestamp: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        from_attributes = True
        json_schema_extra = {
            "example": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "user_id": "user123",
                "event_type": "page_view",
                "event_name": "home_page",
                "properties": {"page": "/home", "referrer": "/login"},
                "timestamp": "2024-01-01T00:00:00Z"
            }
        }


class AnalyticsAggregate(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid4()))
    aggregate_type: str
    metric_name: str
    metric_value: float
    dimensions: Dict[str, Any] = Field(default_factory=dict)
    timestamp: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        from_attributes = True
        json_schema_extra = {
            "example": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "aggregate_type": "daily",
                "metric_name": "page_views",
                "metric_value": 1000.0,
                "dimensions": {"page": "/home"},
                "timestamp": "2024-01-01T00:00:00Z"
            }
        }
