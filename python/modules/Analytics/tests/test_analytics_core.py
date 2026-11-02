"""
Core tests for AnalyticsService (metrics, activities, persistence)
Target: Increase Python coverage towards >85%
"""

import pytest
import json
import tempfile
import os
from datetime import datetime, timezone, timedelta
from modules.Analytics.service.analytics_service import AnalyticsService
from modules.Analytics.models.schemas import Metric, UserActivity


@pytest.fixture
def temp_dir(tmp_path):
    return tmp_path


@pytest.fixture
def service(temp_dir):
    ets_file = temp_dir / "analytics.json"
    return AnalyticsService(ets_file=str(ets_file))


def test_record_and_get_metric(service):
    service.record_metric(name="cpu", value=0.5, tags={"host": "s1"})
    metrics = service.get_metrics(name="cpu")
    assert len(metrics) == 1
    assert metrics[0].value == 0.5
    assert metrics[0].tags["host"] == "s1"


def test_record_and_get_activity(service):
    act = service.record_activity(
        user_id="u1",
        event_type="click",
        event_data={"page": "home"}
    )
    assert act.user_id == "u1"
    activities = service.get_activities(user_id="u1")
    assert len(activities) == 1
    assert activities[0].event_type == "click"


def test_persist_and_restore(service, temp_dir):
    # Add some data
    service.record_metric(name="m1", value=1.0, tags={})
    service.record_activity(user_id="u1", event_type="ev", event_data={})
    service.persist_to_ets()

    # Create new service to restore
    ets_path = temp_dir / "analytics.json"
    new_service = AnalyticsService(ets_file=str(ets_path))
    # After init, restore_from_ets called automatically
    assert len(new_service.metrics) > 0
    assert len(new_service.activities) > 0
    assert any(m.name == "m1" for m in new_service.metrics.get("m1", []))


def test_get_activities_filter_by_time(service):
    now = datetime.now(timezone.utc)
    past = now - timedelta(days=2)
    # Directly insert activities with custom timestamps
    service.activities.append(
        UserActivity(user_id="u1", event_type="ev1", event_data={}, timestamp=past)
    )
    service.activities.append(
        UserActivity(user_id="u1", event_type="ev2", event_data={}, timestamp=now)
    )
    recent = service.get_activities(user_id="u1", start_time=now - timedelta(hours=1), end_time=now + timedelta(hours=1))
    assert len(recent) == 1
    assert recent[0].event_type == "ev2"

def test_generate_revenue_report(service):
    report = service._generate_revenue_report({})
    assert 'Revenue report requires integration with payment modules' in report.get('message', '')

def test_create_dashboard(service):
    widget_data = [
        {
            "id": "w1",
            "title": "Widget1",
            "widget_type": "chart",
            "config": {},
            "data_source": "ds1"
        }
    ]
    dashboard = service.create_dashboard(
        name="Main Dashboard",
        description="Test dashboard",
        widgets=widget_data
    )
    assert dashboard.name == "Main Dashboard"
    assert len(dashboard.widgets) == 1
    assert dashboard.widgets[0].title == "Widget1"

def test_generate_user_activity_report(service):
    now = datetime.now(timezone.utc)
    service.record_activity(user_id="u1", event_type="click", event_data={})
    service.record_activity(user_id="u2", event_type="view", event_data={})
    service.record_activity(user_id="u1", event_type="click", event_data={})
    report = service._generate_user_activity_report({})
    assert report['total_activities'] == 3
    assert report['unique_users'] == 2
    assert 'click' in report['event_breakdown']
    assert report['event_breakdown']['click'] == 2
    assert report['top_users'][0]['user_id'] == 'u1'
