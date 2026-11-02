"""Analytics service wrapper using unified database.

This module provides backward-compatible wrapper around the new unified service.
"""
from typing import Dict, List, Any, Optional
from datetime import datetime, timedelta
import asyncio

from app.shared.database import db
from app.modules.analytics.service import AnalyticsService as _AnalyticsService


class AnalyticsService:
    """
    Wrapper around unified AnalyticsService with backward compatibility.
    Supports in-memory mode for tests.
    """

    def __init__(self, repository=None, use_in_memory: Optional[bool] = None):
        # repository param ignored (for backward compatibility)
        self._use_in_memory = use_in_memory if use_in_memory is not None else not db._initialized
        self._service = _AnalyticsService()
        if self._use_in_memory:
            # In-memory storage for tests
            self.user_activity: Dict[str, Dict] = {}
            self._events: List[Dict] = []

    # In-memory helpers
    def _add_in_memory_event(self, user_id: str, event_type: str, properties: Dict = None, metadata: Dict = None) -> str:
        event = {
            "id": f"evt_{len(self._events)}",
            "user_id": user_id,
            "event_type": event_type,
            "properties": properties or {},
            "metadata": metadata or {},
            "timestamp": datetime.utcnow(),
        }
        self._events.append(event)
        return event["id"]

    def _get_in_memory_user_activity(self, user_id: str, days: int = 30) -> Dict[str, Any]:
        return self.user_activity.get(user_id, {
            "total_events": 0,
            "event_types": {},
            "days_active": 0,
            "average_daily_events": 0.0
        })

    def _get_in_memory_engagement_metrics(self, date_range: int = 30) -> Dict[str, Any]:
        total_sessions = sum(u.get("session_count", 0) for u in self.user_activity.values())
        total_duration = sum(u.get("duration_seconds", 0) for u in self.user_activity.values())
        active_users = len(self.user_activity)
        return {
            "total_sessions": total_sessions,
            "total_duration_seconds": total_duration,
            "active_users": active_users,
            "date_range_days": date_range,
        }

    def _get_in_memory_funnel(self, funnel_steps: List[str]) -> Dict[str, Any]:
        return {
            "funnel_name": "custom",
            "steps": funnel_steps,
            "funnel_steps": {s: 0 for s in funnel_steps},
            "conversion_rates": {}
        }

    def _get_in_memory_cohort(self) -> Dict[str, Any]:
        return {"cohorts": [], "cohort_retention": {}, "periods": 8, "cohort_period": "week"}

    def _get_in_memory_churn(self, user_id: str) -> Dict[str, Any]:
        return {"user_id": user_id, "churn_probability": 0.0, "churn_risk": "low", "features": {}}

    def _get_in_memory_top_features(self, event_type: str) -> List[Dict[str, Any]]:
        return []

    # Public API - match old behavior
    def track_event(self, user_id: str, event_type: str, properties: Optional[Dict] = None, metadata: Optional[Dict] = None) -> str:
        if not user_id:
            raise ValueError("user_id is required")
        if self._use_in_memory:
            return self._add_in_memory_event(user_id, event_type, properties, metadata)
        else:
            return asyncio.run(self._service.track_event(user_id, event_type, properties or {}))

    def get_user_activity(self, user_id: str, days: int = 30) -> Dict[str, Any]:
        if days < 1:
            days = 30
        if self._use_in_memory:
            return self._get_in_memory_user_activity(user_id, days)
        else:
            return asyncio.run(self._service.get_user_activity(user_id, days))

    def get_engagement_metrics(self, days: int = 30) -> Dict[str, Any]:
        if self._use_in_memory:
            return self._get_in_memory_engagement_metrics(days)
        else:
            return asyncio.run(self._service.get_engagement_metrics(days))

    def analyze_funnel(self, funnel_steps: List[str], date_range: int = 30) -> Dict[str, Any]:
        if self._use_in_memory:
            return self._get_in_memory_funnel(funnel_steps)
        else:
            return asyncio.run(self._service.analyze_funnel("custom", funnel_steps, date_range))

    def get_cohort_analysis(self, cohort_period: str = "week", periods: int = 8) -> Dict[str, Any]:
        if self._use_in_memory:
            return self._get_in_memory_cohort()
        else:
            return asyncio.run(self._service.get_cohort_analysis(cohort_period, periods))

    def predict_churn(self, user_id: str) -> Dict[str, Any]:
        if self._use_in_memory:
            return self._get_in_memory_churn(user_id)
        else:
            return asyncio.run(self._service.predict_churn(user_id))

    def get_top_features(self, event_type: str, limit: int = 10) -> List[Dict[str, Any]]:
        if self._use_in_memory:
            return self._get_in_memory_top_features(event_type)
        else:
            return asyncio.run(self._service.get_top_features(event_type, limit))
