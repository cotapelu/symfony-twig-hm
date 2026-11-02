"""Analytics Event Service.

Provides business logic for analytics event operations.
"""

from datetime import datetime
from typing import Optional, List
from uuid import UUID, uuid4

from models.optimized_schema import AnalyticsEvent
from internal.database.repositories.repositories import AnalyticsEventRepository


class AnalyticsEventService:
    """Service for managing analytics events."""

    def __init__(self, repo: AnalyticsEventRepository):
        self.repo = repo

    def create_event(self, user_id: Optional[UUID], event_type: str,
                     properties: Optional[dict] = None,
                     timestamp: Optional[datetime] = None) -> AnalyticsEvent:
        """Create a new analytics event."""
        if not event_type:
            raise ValueError("event_type is required")
        event = AnalyticsEvent(
            user_id=user_id,
            event_type=event_type,
            properties=properties or {},
            timestamp=timestamp or datetime.utcnow()
        )
        return self.repo.create(event)

    def get_event(self, event_id: UUID) -> Optional[AnalyticsEvent]:
        """Get an event by ID."""
        return self.repo.get_by_id(event_id)

    def get_user_events(self, user_id: UUID, limit: int = 100) -> List[AnalyticsEvent]:
        """Get events for a specific user."""
        return self.repo.get_by_user(user_id, limit)

    def get_type_events(self, event_type: str, limit: int = 100) -> List[AnalyticsEvent]:
        """Get events by type."""
        return self.repo.get_by_type(event_type, limit)

    def get_user_type_events(self, user_id: UUID, event_type: str, limit: int = 100) -> List[AnalyticsEvent]:
        """Get events for a user and type."""
        return self.repo.get_by_user_and_type(user_id, event_type, limit)

    def count_by_user(self, user_id: UUID) -> int:
        """Count events for a user."""
        return self.repo.count_by_user(user_id)

    def count_by_type(self, event_type: str) -> int:
        """Count events by type."""
        return self.repo.count_by_type(event_type)

    def delete_old_events(self, before_date: datetime) -> int:
        """Delete events older than before_date. Returns number of deleted events."""
        return self.repo.delete_old_events(before_date)
