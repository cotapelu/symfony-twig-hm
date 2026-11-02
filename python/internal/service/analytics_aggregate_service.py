"""Analytics Aggregate Service.

Provides business logic for pre-aggregated analytics.
"""

from datetime import datetime
from typing import Optional, List
from uuid import UUID

from models.optimized_schema import AnalyticsAggregate
from internal.database.repositories.repositories import AnalyticsAggregateRepository


class AnalyticsAggregateService:
    """Service for managing analytics aggregates."""

    def __init__(self, repo: AnalyticsAggregateRepository):
        self.repo = repo

    def create_aggregate(self, metric_type: str, dimensions: dict,
                         value: int, date: datetime) -> AnalyticsAggregate:
        """Create a new aggregate."""
        if not metric_type:
            raise ValueError("metric_type is required")
        aggregate = AnalyticsAggregate(
            metric_type=metric_type,
            dimensions=dimensions or {},
            value=value,
            date=date
        )
        return self.repo.create(aggregate)

    def get_aggregate(self, aggregate_id: UUID) -> Optional[AnalyticsAggregate]:
        """Get an aggregate by ID."""
        return self.repo.get_by_id(aggregate_id)

    def get_by_metric(self, metric_type: str, limit: int = 100) -> List[AnalyticsAggregate]:
        """Get aggregates for a metric type."""
        return self.repo.get_by_metric_type(metric_type, limit)

    def get_in_date_range(self, metric_type: str, start: datetime, end: datetime) -> List[AnalyticsAggregate]:
        """Get aggregates for a metric type within a date range."""
        return self.repo.get_by_date_range(metric_type, start, end)

    def get_latest(self, metric_type: str) -> Optional[AnalyticsAggregate]:
        """Get the latest aggregate for a metric type."""
        return self.repo.get_latest_by_metric(metric_type)

    def upsert_aggregate(self, metric_type: str, dimensions: dict,
                         value: int, date: datetime) -> AnalyticsAggregate:
        """Create or update an aggregate."""
        return self.repo.upsert(metric_type, dimensions, value, date)
