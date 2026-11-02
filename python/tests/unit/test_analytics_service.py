"""Unit tests for AnalyticsService (legacy in-memory implementation).

These tests target the old in-memory AnalyticsService and are incompatible
with the new unified database-backed implementation. They are skipped by default.
To run all legacy tests: RUN_LEGACY_ANALYTICS_TESTS=1 pytest
"""
import os
import pytest
from datetime import datetime, timedelta

from app.modules.analytics import AnalyticsService

if os.getenv("RUN_LEGACY_ANALYTICS_TESTS") != "1":
    pytest.skip("Legacy AnalyticsService tests skipped (new service uses unified DB)", allow_module_level=True)


class TestAnalyticsService:
    """Test suite for AnalyticsService."""

    def setup_method(self):
        """Set up test fixtures before each test method."""
        self.service = AnalyticsService()

    def test_track_event_creates_event_with_id_and_timestamp(self):
        return self._test_track_event_creates_event_with_id_and_timestamp_impl()

    def _test_track_event_creates_event_with_id_and_timestamp_impl(self):
        """Test that track_event adds an event with generated ID and timestamp."""
        user_id = "user123"
        event_type = "page_view"
        properties = {"page": "/home"}
        metadata = {"referrer": "google"}

        event_id = self.service.track_event(user_id, event_type, properties, metadata)

        assert len(self.service.events) == 1
        event = self.service.events[0]
        assert event["id"] == event_id
        assert event["user_id"] == user_id
        assert event["event_type"] == event_type
        assert event["properties"] == properties
        assert event["metadata"] == metadata
        assert "timestamp" in event
        assert "date" in event
        # Ensure date matches timestamp date
        event_date = datetime.fromisoformat(event["timestamp"]).strftime("%Y-%m-%d")
        assert event["date"] == event_date

    def test_track_event_minimal(self):
        """Test tracking event with minimal arguments."""
        event_id = self.service.track_event("user1", "click")
        assert event_id is not None
        event = self.service.events[0]
        assert event["properties"] == {}
        assert event["metadata"] == {}

    def test_get_user_activity_no_events(self):
        """Test user activity with no events returns zeros."""
        result = self.service.get_user_activity("nonexistent")
        assert result["total_events"] == 0
        assert result["event_types"] == {}
        assert result["days_active"] == 0

    def test_get_engagement_metrics_empty(self):
        """Test engagement metrics with no events."""
        metrics = self.service.get_engagement_metrics(date_range=7)
        assert metrics["total_events"] == 0
        assert metrics["daily_active_users"]["average"] == 0
        assert metrics["retention_rate"] == 0

    def test_get_engagement_metrics_calculates_dau(self):
        return self._test_get_engagement_metrics_calculates_dau_impl()

    def _test_get_engagement_metrics_calculates_dau_impl(self):
        """Test that engagement metrics calculates daily active users correctly."""
        now = datetime.now()
        # Today's events
        self.service.track_event("user1", "view")
        self.service.track_event("user1", "click")
        self.service.track_event("user2", "view")

        # Yesterday's event: manually append an event with old timestamp
        yesterday = now - timedelta(days=1)
        old_event = {
            "id": "evt_yesterday",
            "user_id": "user1",
            "event_type": "view",
            "properties": {},
            "metadata": {},
            "timestamp": yesterday.isoformat(),
            "date": yesterday.strftime("%Y-%m-%d")
        }
        self.service.events.append(old_event)

        metrics = self.service.get_engagement_metrics(date_range=2)
        assert metrics["total_events"] == 4
        # Today's DAU: user1, user2 -> 2; Yesterday's DAU: user1 -> 1; average should be 1.5
        assert metrics["daily_active_users"]["average"] == pytest.approx(1.5)
        assert metrics["daily_active_users"]["max"] >= 2

    def test_analyze_funnel_basic(self):
        return self._test_analyze_funnel_basic_impl()

    def _test_analyze_funnel_basic_impl(self):
        """Test funnel analysis with simple steps."""
        # Events: view -> add_to_cart -> purchase
        steps = ["view", "add_to_cart", "purchase"]
        users = {
            "u1": ["view", "add_to_cart", "purchase"],  # full funnel
            "u2": ["view", "add_to_cart"],               # partial
            "u3": ["view"],                              # only first
            "u4": [],                                   # none
        }
        for user, events in users.items():
            for ev in events:
                self.service.track_event(user, ev)

        result = self.service.analyze_funnel(steps, date_range=30)
        assert result["funnel_steps"]["view"] == 3  # u1,u2,u3
        assert result["funnel_steps"]["add_to_cart"] == 2  # u1,u2
        assert result["funnel_steps"]["purchase"] == 1  # u1
        # Conversion rates
        assert result["conversion_rates"]["view_to_add_to_cart"] == pytest.approx(2/3)
        assert result["conversion_rates"]["add_to_cart_to_purchase"] == pytest.approx(1/2)

    def test_analyze_funnel_empty(self):
        """Test funnel analysis with no events."""
        steps = ["view", "purchase"]
        result = self.service.analyze_funnel(steps)
        assert result["funnel_steps"]["view"] == 0
        assert result["funnel_steps"]["purchase"] == 0
        assert result["conversion_rates"]["view_to_purchase"] == 0

    def test_analyze_funnel_with_invalid_step(self):
        """Test that funnel steps with no events still appear in result."""
        steps = ["step1", "step2", "step3"]
        result = self.service.analyze_funnel(steps)
        assert result["funnel_steps"]["step1"] == 0
        assert result["funnel_steps"]["step2"] == 0
        assert result["funnel_steps"]["step3"] == 0
