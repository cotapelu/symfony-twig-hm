from typing import Optional, Dict, Any, List
from datetime import datetime
from pydantic import BaseModel, Field
from uuid import UUID, uuid4


class ImageRecognitionResult(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid4()))
    user_id: str
    image_url: str
    detected_objects: List[Dict[str, Any]] = Field(default_factory=list)
    confidence: float
    model_version: str
    metadata: Dict[str, Any] = Field(default_factory=dict)
    created_at: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        from_attributes = True
        json_schema_extra = {
            "example": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "user_id": "user123",
                "image_url": "https://example.com/image.jpg",
                "detected_objects": [
                    {"type": "person", "confidence": 0.95, "bbox": [10, 20, 100, 200]},
                    {"type": "car", "confidence": 0.88, "bbox": [150, 50, 300, 250]}
                ],
                "confidence": 0.95,
                "model_version": "v1.0",
                "metadata": {"processing_time_ms": 150},
                "created_at": "2024-01-01T00:00:00Z"
            }
        }
