"""Unit tests for AnalyticsAggregateService."""
import pytest
from datetime import datetime
from unittest.mock import Mock
from uuid import UUID, uuid4

from internal.service.analytics_aggregate_service import AnalyticsAggregateService


@pytest.fixture
def mock_repo():
    """Mock AnalyticsAggregateRepository."""
    repo = Mock()
    repo.create = Mock()
    repo.get_by_id = Mock()
    repo.get_by_metric_type = Mock()
    repo.get_by_date_range = Mock()
    repo.get_latest_by_metric = Mock()
    repo.upsert = Mock()
    return repo


def test_create_aggregate_success(mock_repo):
    """Test creating an aggregate."""
    service = AnalyticsAggregateService(mock_repo)
    metric_type = "user_count"
    dims = {"region": "us"}
    value = 100
    date = datetime.utcnow()
    mock_agg = Mock()
    mock_repo.create.return_value = mock_agg

    result = service.create_aggregate(metric_type, dims, value, date)

    assert result is mock_agg
    mock_repo.create.assert_called_once()
    agg = mock_repo.create.call_args[0][0]
    assert agg.metric_type == metric_type
    assert agg.value == value


def test_create_aggregate_missing_metric_type(mock_repo):
    """Test missing metric_type raises ValueError."""
    service = AnalyticsAggregateService(mock_repo)
    with pytest.raises(ValueError, match="metric_type is required"):
        service.create_aggregate("", {}, 0, datetime.utcnow())


def test_get_latest(mock_repo):
    """Test getting latest aggregate."""
    service = AnalyticsAggregateService(mock_repo)
    mock_agg = Mock()
    mock_repo.get_latest_by_metric.return_value = mock_agg

    result = service.get_latest("page_views")

    assert result is mock_agg
    mock_repo.get_latest_by_metric.assert_called_once_with("page_views")


def test_upsert_aggregate(mock_repo):
    """Test upserting an aggregate."""
    service = AnalyticsAggregateService(mock_repo)
    metric_type = "sales"
    dims = {"product": "widget"}
    value = 500
    date = datetime.utcnow()
    mock_agg = Mock()
    mock_repo.upsert.return_value = mock_agg

    result = service.upsert_aggregate(metric_type, dims, value, date)

    assert result is mock_agg
    mock_repo.upsert.assert_called_once_with(metric_type, dims, value, date)


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
