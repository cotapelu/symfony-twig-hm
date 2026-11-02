"""Unit tests for AnalyticsEventService (internal)."""
import pytest
from unittest.mock import MagicMock
from uuid import UUID, uuid4
from datetime import datetime

from internal.service.analytics_event_service import AnalyticsEventService
from models.optimized_schema import AnalyticsEvent

@pytest.fixture
def mock_repo():
    return MagicMock()

@pytest.fixture
def service(mock_repo):
    return AnalyticsEventService(repo=mock_repo)

def test_create_event_validation_missing_event_type(service):
    with pytest.raises(ValueError, match="event_type is required"):
        service.create_event(user_id=None, event_type="")

def test_create_event_success_with_all_fields(service, mock_repo):
    user_id = uuid4()
    event_type = "click"
    properties = {"page": "home"}
    timestamp = datetime.utcnow()
    event = AnalyticsEvent(
        id=uuid4(),
        user_id=user_id,
        event_type=event_type,
        properties=properties,
        timestamp=timestamp
    )
    mock_repo.create.return_value = event

    result = service.create_event(
        user_id=user_id,
        event_type=event_type,
        properties=properties,
        timestamp=timestamp
    )

    assert result == event
    mock_repo.create.assert_called_once()
    arg = mock_repo.create.call_args[0][0]
    assert arg.user_id == user_id
    assert arg.event_type == event_type
    assert arg.properties == properties
    assert arg.timestamp == timestamp

def test_create_event_success_defaults(service, mock_repo):
    user_id = uuid4()
    event_type = "view"
    event = AnalyticsEvent(
        id=uuid4(),
        user_id=user_id,
        event_type=event_type,
        properties={},
        timestamp=datetime.utcnow()  # approximate
    )
    mock_repo.create.return_value = event

    # Call without properties or timestamp
    result = service.create_event(user_id=user_id, event_type=event_type)

    assert result == event
    mock_repo.create.assert_called_once()
    arg = mock_repo.create.call_args[0][0]
    assert arg.user_id == user_id
    assert arg.event_type == event_type
    assert arg.properties == {}
    # timestamp should be a datetime (we can't compare exact)
    assert isinstance(arg.timestamp, datetime)

def test_get_event(service, mock_repo):
    event_id = uuid4()
    event = AnalyticsEvent(id=event_id, user_id=uuid4(), event_type="type", properties={})
    mock_repo.get_by_id.return_value = event

    result = service.get_event(event_id)
    assert result == event
    mock_repo.get_by_id.assert_called_once_with(event_id)

def test_get_event_not_found(service, mock_repo):
    mock_repo.get_by_id.return_value = None
    result = service.get_event(uuid4())
    assert result is None

def test_get_user_events(service, mock_repo):
    user_id = uuid4()
    events = [
        AnalyticsEvent(id=uuid4(), user_id=user_id, event_type="t1", properties={}),
        AnalyticsEvent(id=uuid4(), user_id=user_id, event_type="t2", properties={})
    ]
    mock_repo.get_by_user.return_value = events

    result = service.get_user_events(user_id, limit=50)
    assert result == events
    mock_repo.get_by_user.assert_called_once_with(user_id, 50)

def test_get_type_events(service, mock_repo):
    event_type = "click"
    events = [AnalyticsEvent(id=uuid4(), user_id=uuid4(), event_type=event_type, properties={})]
    mock_repo.get_by_type.return_value = events

    result = service.get_type_events(event_type, limit=30)
    assert result == events
    mock_repo.get_by_type.assert_called_once_with(event_type, 30)

def test_get_user_type_events(service, mock_repo):
    user_id = uuid4()
    event_type = "purchase"
    events = [AnalyticsEvent(id=uuid4(), user_id=user_id, event_type=event_type, properties={})]
    mock_repo.get_by_user_and_type.return_value = events

    result = service.get_user_type_events(user_id, event_type, limit=20)
    assert result == events
    mock_repo.get_by_user_and_type.assert_called_once_with(user_id, event_type, 20)

def test_count_by_user(service, mock_repo):
    user_id = uuid4()
    mock_repo.count_by_user.return_value = 42

    count = service.count_by_user(user_id)
    assert count == 42
    mock_repo.count_by_user.assert_called_once_with(user_id)

def test_count_by_type(service, mock_repo):
    event_type = "error"
    mock_repo.count_by_type.return_value = 7

    count = service.count_by_type(event_type)
    assert count == 7
    mock_repo.count_by_type.assert_called_once_with(event_type)

def test_delete_old_events(service, mock_repo):
    before_date = datetime.utcnow()
    mock_repo.delete_old_events.return_value = 5

    deleted = service.delete_old_events(before_date)
    assert deleted == 5
    mock_repo.delete_old_events.assert_called_once_with(before_date)