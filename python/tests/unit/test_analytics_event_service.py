"""Unit tests for AnalyticsEventService."""
import pytest
from datetime import datetime, timedelta
from unittest.mock import Mock, MagicMock
from uuid import UUID, uuid4

from internal.service.analytics_event_service import AnalyticsEventService


@pytest.fixture
def mock_repo():
    """Create a mock AnalyticsEventRepository."""
    repo = Mock()
    repo.create = Mock()
    repo.get_by_id = Mock()
    repo.get_by_user = Mock()
    repo.get_by_type = Mock()
    repo.get_by_user_and_type = Mock()
    repo.count_by_user = Mock(return_value=0)
    repo.count_by_type = Mock(return_value=0)
    repo.delete_old_events = Mock(return_value=0)
    return repo


def test_create_event_success(mock_repo):
    """Test creating an event successfully."""
    service = AnalyticsEventService(mock_repo)
    event_type = "page_view"
    user_id = uuid4()
    props = {"page": "/home"}

    mock_event = Mock()
    mock_event.id = uuid4()
    mock_repo.create.return_value = mock_event

    result = service.create_event(user_id, event_type, props)

    assert result is mock_event
    mock_repo.create.assert_called_once()
    # Verify the passed AnalyticsEvent has correct fields
    created_event = mock_repo.create.call_args[0][0]
    assert created_event.event_type == event_type
    assert created_event.user_id == user_id
    assert created_event.properties == props


def test_create_event_missing_type(mock_repo):
    """Test that missing event_type raises ValueError."""
    service = AnalyticsEventService(mock_repo)
    with pytest.raises(ValueError, match="event_type is required"):
        service.create_event(None, "")


def test_get_event(mock_repo):
    """Test getting an event by ID."""
    service = AnalyticsEventService(mock_repo)
    event_id = uuid4()
    mock_event = Mock()
    mock_repo.get_by_id.return_value = mock_event

    result = service.get_event(event_id)

    assert result is mock_event
    mock_repo.get_by_id.assert_called_once_with(event_id)


def test_get_user_events(mock_repo):
    """Test getting events for a user."""
    service = AnalyticsEventService(mock_repo)
    user_id = uuid4()
    mock_events = [Mock(), Mock()]
    mock_repo.get_by_user.return_value = mock_events

    result = service.get_user_events(user_id, limit=50)

    assert result == mock_events
    mock_repo.get_by_user.assert_called_once_with(user_id, 50)


def test_count_by_user(mock_repo):
    """Test counting events for a user."""
    service = AnalyticsEventService(mock_repo)
    user_id = uuid4()
    mock_repo.count_by_user.return_value = 5

    count = service.count_by_user(user_id)

    assert count == 5
    mock_repo.count_by_user.assert_called_once_with(user_id)


def test_delete_old_events(mock_repo):
    """Test deleting old events returns count."""
    service = AnalyticsEventService(mock_repo)
    before = datetime.utcnow() - timedelta(days=30)
    mock_repo.delete_old_events.return_value = 10

    deleted = service.delete_old_events(before)

    assert deleted == 10
    mock_repo.delete_old_events.assert_called_once_with(before)


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
