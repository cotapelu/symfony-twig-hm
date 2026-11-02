"""Unit tests for AnalyticsAggregateService (internal)."""
import pytest
from unittest.mock import MagicMock
from uuid import UUID, uuid4
from datetime import datetime

from internal.service.analytics_aggregate_service import AnalyticsAggregateService
from models.optimized_schema import AnalyticsAggregate

@pytest.fixture
def mock_repo():
    return MagicMock()

@pytest.fixture
def service(mock_repo):
    return AnalyticsAggregateService(repo=mock_repo)

def test_create_aggregate_validation_missing_metric_type(service):
    with pytest.raises(ValueError, match="metric_type is required"):
        service.create_aggregate(metric_type="", dimensions={}, value=0, date=datetime.utcnow())

def test_create_aggregate_success(service, mock_repo):
    metric_type = "page_views"
    dimensions = {"page": "home"}
    value = 123
    date = datetime.utcnow()
    agg = AnalyticsAggregate(
        id=uuid4(),
        metric_type=metric_type,
        dimensions=dimensions,
        value=value,
        date=date
    )
    mock_repo.create.return_value = agg

    result = service.create_aggregate(
        metric_type=metric_type,
        dimensions=dimensions,
        value=value,
        date=date
    )

    assert result == agg
    mock_repo.create.assert_called_once()
    arg = mock_repo.create.call_args[0][0]
    assert arg.metric_type == metric_type
    assert arg.dimensions == dimensions
    assert arg.value == value
    assert arg.date == date

def test_create_aggregate_dimensions_default_to_empty_dict(service, mock_repo):
    metric_type = "clicks"
    value = 10
    date = datetime.utcnow()
    agg = AnalyticsAggregate(
        id=uuid4(),
        metric_type=metric_type,
        dimensions={},
        value=value,
        date=date
    )
    mock_repo.create.return_value = agg

    result = service.create_aggregate(
        metric_type=metric_type,
        dimensions=None,
        value=value,
        date=date
    )

    assert result == agg
    arg = mock_repo.create.call_args[0][0]
    assert arg.dimensions == {}

def test_get_aggregate(service, mock_repo):
    agg_id = uuid4()
    agg = AnalyticsAggregate(
        id=agg_id,
        metric_type="test",
        dimensions={},
        value=0,
        date=datetime.utcnow()
    )
    mock_repo.get_by_id.return_value = agg

    result = service.get_aggregate(agg_id)
    assert result == agg
    mock_repo.get_by_id.assert_called_once_with(agg_id)

def test_get_aggregate_not_found(service, mock_repo):
    mock_repo.get_by_id.return_value = None
    result = service.get_aggregate(uuid4())
    assert result is None

def test_get_by_metric(service, mock_repo):
    metric_type = "purchases"
    aggs = [
        AnalyticsAggregate(id=uuid4(), metric_type=metric_type, dimensions={}, value=1, date=datetime.utcnow()),
        AnalyticsAggregate(id=uuid4(), metric_type=metric_type, dimensions={}, value=2, date=datetime.utcnow())
    ]
    mock_repo.get_by_metric_type.return_value = aggs

    result = service.get_by_metric(metric_type, limit=50)
    assert result == aggs
    mock_repo.get_by_metric_type.assert_called_once_with(metric_type, 50)

def test_get_in_date_range(service, mock_repo):
    metric_type = "visits"
    start = datetime.utcnow()
    end = datetime.utcnow()
    aggs = [AnalyticsAggregate(id=uuid4(), metric_type=metric_type, dimensions={}, value=5, date=datetime.utcnow())]
    mock_repo.get_by_date_range.return_value = aggs

    result = service.get_in_date_range(metric_type, start, end)
    assert result == aggs
    mock_repo.get_by_date_range.assert_called_once_with(metric_type, start, end)

def test_get_latest(service, mock_repo):
    metric_type = "revenue"
    agg = AnalyticsAggregate(id=uuid4(), metric_type=metric_type, dimensions={}, value=100, date=datetime.utcnow())
    mock_repo.get_latest_by_metric.return_value = agg

    result = service.get_latest(metric_type)
    assert result == agg
    mock_repo.get_latest_by_metric.assert_called_once_with(metric_type)

def test_upsert_aggregate(service, mock_repo):
    metric_type = "signups"
    dimensions = {"source": "ads"}
    value = 50
    date = datetime.utcnow()
    agg = AnalyticsAggregate(
        id=uuid4(),
        metric_type=metric_type,
        dimensions=dimensions,
        value=value,
        date=date
    )
    mock_repo.upsert.return_value = agg

    result = service.upsert_aggregate(metric_type, dimensions, value, date)

    assert result == agg
    mock_repo.upsert.assert_called_once_with(metric_type, dimensions, value, date)