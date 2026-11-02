"""ML Inference Service.

Provides business logic for recording and querying ML inferences.
"""

from typing import Optional, List
from uuid import UUID, uuid4
from datetime import datetime

from models.optimized_schema import MLInference
from internal.database.repositories.repositories import MLInferenceRepository


class MLInferenceService:
    """Service for managing ML inference records."""

    def __init__(self, repo: MLInferenceRepository):
        self.repo = repo

    def record_inference(self, model_id: UUID, user_id: Optional[UUID],
                         input_data: dict, output_data: dict,
                         latency_ms: Optional[int] = None) -> MLInference:
        """Record a new inference."""
        if not model_id:
            raise ValueError("model_id is required")
        inference = MLInference(
            model_id=model_id,
            user_id=user_id,
            input=input_data,
            output=output_data,
            latency_ms=latency_ms
        )
        return self.repo.create(inference)

    def get_inference(self, inference_id: UUID) -> Optional[MLInference]:
        """Get an inference by ID."""
        return self.repo.get_by_id(inference_id)

    def get_by_model(self, model_id: UUID, limit: int = 100) -> List[MLInference]:
        """Get inferences for a model."""
        return self.repo.get_by_model(model_id, limit)

    def get_by_user(self, user_id: UUID, limit: int = 100) -> List[MLInference]:
        """Get inferences for a user."""
        return self.repo.get_by_user(user_id, limit)

    def get_average_latency(self, model_id: UUID) -> Optional[float]:
        """Get average latency for a model."""
        return self.repo.get_average_latency_for_model(model_id)
