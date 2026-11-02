"""Recommendation Service.

Provides business logic for managing user recommendations.
"""

from typing import Optional, List, Dict
from uuid import UUID, uuid4
from datetime import datetime

from models.optimized_schema import Recommendation
from internal.database.repositories.repositories import RecommendationRepository


class RecommendationService:
    """Service for managing recommendations."""

    def __init__(self, repo: RecommendationRepository):
        self.repo = repo

    def create_recommendation(self, user_id: UUID, model_type: str, items: List[str],
                              score: Optional[float] = None) -> Recommendation:
        """Create a new recommendation."""
        if not user_id:
            raise ValueError("user_id is required")
        if not model_type:
            raise ValueError("model_type is required")
        if not items:
            raise ValueError("items cannot be empty")
        rec = Recommendation(
            user_id=user_id,
            model_type=model_type,
            items=items,
            score=score
        )
        return self.repo.create(rec)

    def get_recommendation(self, rec_id: UUID) -> Optional[Recommendation]:
        """Get a recommendation by ID."""
        return self.repo.get_by_id(rec_id)

    def get_by_user(self, user_id: UUID, model_type: Optional[str] = None, limit: int = 100) -> List[Recommendation]:
        """Get recommendations for a user."""
        return self.repo.get_by_user(user_id, model_type, limit)

    def get_latest(self, user_id: UUID, model_type: Optional[str] = None) -> Optional[Recommendation]:
        """Get the latest recommendation for a user (and optionally model type)."""
        return self.repo.get_latest_by_user(user_id, model_type)

    def upsert_recommendation(self, user_id: UUID, model_type: str, items: List[str],
                              score: Optional[float] = None) -> Recommendation:
        """Create or update recommendation. For simplicity, replace existing recommendations for same user+model."""
        old = self.repo.get_by_user(user_id, model_type, limit=100)
        for r in old:
            self.repo.delete(r.id)
        return self.create_recommendation(user_id, model_type, items, score)

    def delete_recommendation(self, rec_id: UUID) -> bool:
        """Delete a recommendation."""
        self.repo.delete(rec_id)
        return True
