from typing import Optional, Dict, Any
from datetime import datetime
from pydantic import BaseModel, Field
from uuid import UUID, uuid4


class MLModel(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid4()))
    name: str
    model_type: str
    version: str
    framework: str
    hyperparameters: Dict[str, Any] = Field(default_factory=dict)
    metrics: Dict[str, float] = Field(default_factory=dict)
    status: str = "training"
    model_path: Optional[str] = None
    training_progress: float = 0.0
    error_message: Optional[str] = None
    base_model_id: Optional[str] = None
    changes: Optional[Dict[str, Any]] = None
    training_data_config: Optional[Dict[str, Any]] = None
    created_at: datetime = Field(default_factory=datetime.utcnow)
    updated_at: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        from_attributes = True
        json_schema_extra = {
            "example": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "name": "image_classifier",
                "model_type": "classification",
                "version": "v1.0",
                "framework": "tensorflow",
                "hyperparameters": {"learning_rate": 0.001, "batch_size": 32},
                "metrics": {"accuracy": 0.95, "precision": 0.93},
                "status": "active",
                "model_path": "/models/image_classifier.h5",
                "training_progress": 100.0,
                "created_at": "2024-01-01T00:00:00Z",
                "updated_at": "2024-01-01T00:00:00Z"
            }
        }


class MLInference(BaseModel):
    id: str = Field(default_factory=lambda: str(uuid4()))
    model_id: str
    request_id: str
    input_data: Dict[str, Any]
    output_data: Optional[Dict[str, Any]] = None
    confidence: float
    latency_ms: int
    status: str = "pending"
    error_message: Optional[str] = None
    timestamp: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        from_attributes = True
        json_schema_extra = {
            "example": {
                "id": "550e8400-e29b-41d4-a716-446655440000",
                "model_id": "model123",
                "request_id": "req456",
                "input_data": {"image": "base64_encoded_image"},
                "output_data": {"class": "cat", "probability": 0.95},
                "confidence": 0.95,
                "latency_ms": 150,
                "status": "completed",
                "timestamp": "2024-01-01T00:00:00Z"
            }
        }
