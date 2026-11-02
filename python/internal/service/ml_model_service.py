"""ML Model Service.

Provides business logic for managing machine learning models.
"""

from typing import Optional, List
from uuid import UUID, uuid4
from datetime import datetime

from models.optimized_schema import MLModel
from internal.database.repositories.repositories import MLModelRepository


class MLModelService:
    """Service for managing ML model metadata."""

    def __init__(self, repo: MLModelRepository):
        self.repo = repo

    def create_model(self, name: str, model_type: Optional[str] = None,
                     version: Optional[str] = None, accuracy: Optional[float] = None,
                     status: str = "training", config: Optional[dict] = None) -> MLModel:
        """Create a new ML model."""
        if not name:
            raise ValueError("name is required")
        model = MLModel(
            name=name,
            model_type=model_type,
            version=version,
            accuracy=accuracy,
            status=status,
            config=config or {}
        )
        return self.repo.create(model)

    def get_model(self, model_id: UUID) -> Optional[MLModel]:
        """Get a model by ID."""
        return self.repo.get_by_id(model_id)

    def get_models_by_type(self, model_type: str, limit: int = 100) -> List[MLModel]:
        """Get models by type."""
        return self.repo.get_by_type(model_type, limit)

    def get_active_models(self, model_type: Optional[str] = None, limit: int = 100) -> List[MLModel]:
        """Get models that are deployed (status='deployed')."""
        return self.repo.get_active_models(model_type, limit)

    def update_status(self, model_id: UUID, status: str) -> Optional[MLModel]:
        """Update model status."""
        return self.repo.update_status(model_id, status)

    def update_accuracy(self, model_id: UUID, accuracy: float) -> Optional[MLModel]:
        """Update model accuracy."""
        return self.repo.update_accuracy(model_id, accuracy)

    def delete_model(self, model_id: UUID) -> bool:
        """Delete a model. Returns True if deleted."""
        self.repo.delete(model_id)
        return True
