"""Image Recognition Result Service.

Provides business logic for managing image recognition outputs.
"""

from typing import Optional, List
from uuid import UUID, uuid4
from datetime import datetime

from models.optimized_schema import ImageRecognitionResult
from internal.database.repositories.repositories import ImageRecognitionResultRepository


class ImageRecognitionResultService:
    """Service for managing image recognition results."""

    def __init__(self, repo: ImageRecognitionResultRepository):
        self.repo = repo

    def create_result(self, user_id: Optional[UUID], image_url: str,
                      model_type: str, labels: List[str],
                      confidence: Optional[float] = None,
                      processing_time_ms: Optional[int] = None,
                      metadata: Optional[dict] = None) -> ImageRecognitionResult:
        """Create a new image recognition result."""
        if not image_url:
            raise ValueError("image_url is required")
        if not model_type:
            raise ValueError("model_type is required")
        if not labels:
            raise ValueError("labels cannot be empty")
        result = ImageRecognitionResult(
            user_id=user_id,
            image_url=image_url,
            model_type=model_type,
            labels=labels,
            confidence=confidence,
            processing_time_ms=processing_time_ms,
            meta=metadata or {}
        )
        return self.repo.create(result)

    def get_result(self, result_id: UUID) -> Optional[ImageRecognitionResult]:
        """Get a result by ID."""
        return self.repo.get_by_id(result_id)

    def get_by_user(self, user_id: UUID, limit: int = 100) -> List[ImageRecognitionResult]:
        """Get results for a user."""
        return self.repo.get_by_user(user_id, limit)

    def get_by_model_type(self, model_type: str, limit: int = 100) -> List[ImageRecognitionResult]:
        """Get results by model type."""
        return self.repo.get_by_model_type(model_type, limit)

    def get_by_image_url(self, image_url: str) -> List[ImageRecognitionResult]:
        """Get results for an image URL."""
        return self.repo.get_by_image_url(image_url)

    def get_average_confidence(self, model_type: str) -> Optional[float]:
        """Get average confidence for a model type."""
        return self.repo.get_average_confidence_by_model(model_type)

    def get_average_processing_time(self, model_type: str) -> Optional[float]:
        """Get average processing time for a model type."""
        return self.repo.get_average_processing_time_by_model(model_type)

    def count_by_user(self, user_id: UUID) -> int:
        """Count results for a user."""
        return self.repo.count_by_user(user_id)
