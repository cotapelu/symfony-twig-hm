"""Tests for Analytics Service"""
import pytest
import os
import tempfile
from datetime import datetime, timezone, timedelta
from modules.Analytics.service.analytics_service import AnalyticsService


@pytest.fixture
def analytics_service():
    """Create a temporary analytics service for testing"""
    with tempfile.NamedTemporaryFile(delete=False, suffix='.json') as f:
        service = AnalyticsService(ets_file=f.name)
        yield service
        # Cleanup
        try:
            os.unlink(f.name)
        except:
            pass


def test_record_metric(analytics_service):
    """Test recording a metric"""
    metric = analytics_service.record_metric("test_metric", 100.0, {"tag1": "value1"})
    assert metric.name == "test_metric"
    assert metric.value == 100.0
    assert metric.tags["tag1"] == "value1"


def test_record_activity(analytics_service):
    """Test recording user activity"""
    activity = analytics_service.record_activity(
        user_id="user_123",
        event_type="page_view",
        event_data={"page": "/home"},
        session_id="session_456",
        device_type="mobile"
    )
    assert activity.user_id == "user_123"
    assert activity.event_type == "page_view"


def test_get_metrics(analytics_service):
    """Test retrieving metrics"""
    analytics_service.record_metric("cpu_usage", 50.0)
    analytics_service.record_metric("cpu_usage", 60.0)
    analytics_service.record_metric("cpu_usage", 70.0)

    metrics = analytics_service.get_metrics("cpu_usage")
    assert len(metrics) == 3


def test_get_metrics_with_time_range(analytics_service):
    """Test retrieving metrics with time range"""
    now = datetime.now(timezone.utc)
    
    analytics_service.record_metric("requests", 10.0)
    
    # Get metrics after "now" - should be empty
    metrics = analytics_service.get_metrics("requests", start_time=now)
    assert len(metrics) == 0


def test_get_time_series(analytics_service):
    """Test getting time series data"""
    now = datetime.now(timezone.utc)
    
    analytics_service.record_metric("temperature", 25.0)
    analytics_service.record_metric("temperature", 26.0)

    ts = analytics_service.get_time_series(
        "temperature",
        "hour",
        now - timedelta(hours=1),
        now
    )
    assert ts.metric_name == "temperature"
    assert ts.granularity == "hour"


def test_get_activities(analytics_service):
    """Test retrieving activities"""
    analytics_service.record_activity("user_1", "login", {})
    analytics_service.record_activity("user_2", "login", {})
    analytics_service.record_activity("user_1", "page_view", {})

    activities = analytics_service.get_activities()
    assert len(activities) == 3


def test_get_activities_by_user(analytics_service):
    """Test filtering activities by user"""
    analytics_service.record_activity("user_1", "login", {})
    analytics_service.record_activity("user_2", "login", {})
    analytics_service.record_activity("user_1", "page_view", {})

    activities = analytics_service.get_activities(user_id="user_1")
    assert len(activities) == 2


def test_get_activities_by_event_type(analytics_service):
    """Test filtering activities by event type"""
    analytics_service.record_activity("user_1", "login", {})
    analytics_service.record_activity("user_2", "login", {})
    analytics_service.record_activity("user_1", "page_view", {})

    activities = analytics_service.get_activities(event_type="login")
    assert len(activities) == 2


def test_create_dashboard(analytics_service):
    """Test creating a dashboard"""
    from modules.Analytics.models.schemas import DashboardWidget
    
    widgets = [
        {
            "id": "widget_1",
            "title": "CPU Usage",
            "widget_type": "chart",
            "config": {},
            "data_source": "cpu_usage"
        }
    ]
    
    dashboard = analytics_service.create_dashboard("System Dashboard", "CPU and Memory", widgets)
    assert dashboard.name == "System Dashboard"
    assert len(dashboard.widgets) == 1


def test_get_dashboard(analytics_service):
    """Test retrieving a dashboard"""
    widgets = [{"id": "w1", "title": "Test", "widget_type": "chart", "config": {}, "data_source": "test"}]
    created = analytics_service.create_dashboard("Test", "Test", widgets)
    
    retrieved = analytics_service.get_dashboard(created.id)
    assert retrieved is not None
    assert retrieved.id == created.id


def test_list_dashboards(analytics_service):
    """Test listing all dashboards"""
    widgets = [{"id": "w1", "title": "Test", "widget_type": "chart", "config": {}, "data_source": "test"}]
    analytics_service.create_dashboard("Dashboard 1", "Desc 1", widgets)
    analytics_service.create_dashboard("Dashboard 2", "Desc 2", widgets)
    
    dashboards = analytics_service.list_dashboards()
    assert len(dashboards) == 2


def test_generate_report(analytics_service):
    """Test generating a report"""
    report = analytics_service.generate_report(
        "User Activity Report",
        "user_activity",
        {"start_time": "2024-01-01", "end_time": "2024-12-31"}
    )
    assert report.name == "User Activity Report"
    assert report.report_type == "user_activity"


def test_generate_engagement_report(analytics_service):
    """Test generating engagement report"""
    # Add some activities
    analytics_service.record_activity("user_1", "login", {})
    analytics_service.record_activity("user_2", "login", {})
    
    report = analytics_service.generate_report("Engagement", "engagement", {})
    assert "daily_active_users" in report.data


def test_execute_query_sum(analytics_service):
    """Test executing aggregation query with sum"""
    from modules.Analytics.models.schemas import AnalyticsQuery, TimeRange
    
    analytics_service.record_metric("orders", 100.0)
    analytics_service.record_metric("orders", 200.0)
    analytics_service.record_metric("orders", 300.0)
    
    # Get all metrics without time filter
    metrics = analytics_service.get_metrics("orders")
    values = [m.value for m in metrics]
    result = sum(values)
    
    assert result == 600.0


def test_execute_query_avg(analytics_service):
    """Test executing aggregation query with average"""
    analytics_service.record_metric("temperature", 20.0)
    analytics_service.record_metric("temperature", 30.0)
    analytics_service.record_metric("temperature", 40.0)
    
    metrics = analytics_service.get_metrics("temperature")
    values = [m.value for m in metrics]
    result = sum(values) / len(values)
    
    assert result == 30.0


def test_execute_query_count(analytics_service):
    """Test executing aggregation query with count"""
    analytics_service.record_metric("visits", 1.0)
    analytics_service.record_metric("visits", 1.0)
    analytics_service.record_metric("visits", 1.0)
    
    metrics = analytics_service.get_metrics("visits")
    
    assert len(metrics) == 3


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
