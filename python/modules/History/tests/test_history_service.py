import pytest
import sys
import types
from unittest.mock import AsyncMock, MagicMock, patch
from datetime import datetime, timedelta
from modules.History.service.history_service import HistoryService
from modules.History.models.schemas import ActivityCreate, ActivityType, DeviceType, SessionSummary, HistoryStats

class MockAsyncCursor:
    def __init__(self, items):
        self.items = items
        self.index = 0
    def sort(self, *args, **kwargs):
        return self
    def skip(self, *args, **kwargs):
        return self
    def limit(self, *args, **kwargs):
        return self
    def __aiter__(self):
        return self
    async def __anext__(self):
        if self.index >= len(self.items):
            raise StopAsyncIteration
        item = self.items[self.index]
        self.index += 1
        return item

@pytest.fixture
def service():
    return HistoryService()

@pytest.fixture
def mock_db():
    with patch('modules.History.service.history_service.get_db_connection', new_callable=AsyncMock) as mock:
        conn = AsyncMock()
        mock.return_value = conn
        yield conn

@pytest.fixture
def mock_redis():
    with patch('modules.History.service.history_service.get_redis_cache', new_callable=AsyncMock) as mock:
        redis = AsyncMock()
        mock.return_value = redis
        yield redis

@pytest.mark.asyncio
async def test_log_activity(service, mock_db, mock_redis):
    mock_db.__getitem__ = MagicMock(return_value=AsyncMock())
    mock_db.__getitem__().insert_one = AsyncMock()
    
    activity = ActivityCreate(
        user_id="user123",
        activity_type=ActivityType.PAGE_VIEW,
        description="Viewed product",
        metadata={"product_id": "123"}
    )
    
    result = await service.log_activity(activity)
    
    assert result is not None
    assert result.user_id == "user123"
    assert result.activity_type == ActivityType.PAGE_VIEW

@pytest.mark.asyncio
async def test_log_activity_with_session(service, mock_db, mock_redis):
    mock_db.__getitem__ = MagicMock(return_value=AsyncMock())
    mock_db.__getitem__().insert_one = AsyncMock()
    
    activity = ActivityCreate(
        user_id="user123",
        activity_type=ActivityType.PURCHASE,
        description="Purchased item",
        session_id="session456",
        device_type=DeviceType.ANDROID,
        ip_address="192.168.1.1"
    )
    
    result = await service.log_activity(activity)
    
    assert result is not None
    assert result.session_id == "session456"

@pytest.mark.asyncio
async def test_get_user_history(service, mock_db, mock_redis):
    mock_collection = MagicMock()
    mock_cursor = MagicMock()
    mock_cursor.sort.return_value = mock_cursor
    mock_cursor.skip.return_value = mock_cursor
    mock_cursor.limit.return_value = mock_cursor
    mock_cursor.to_list = AsyncMock(return_value=[
        {
            "_id": "1",
            "user_id": "user123",
            "activity_type": "page_view",
            "description": "Test",
            "timestamp": datetime.utcnow()
        }
    ])
    mock_collection.find = MagicMock(return_value=mock_cursor)
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)
    
    result = await service.get_user_history("user123")
    
    assert result is not None or result == []

@pytest.mark.asyncio
async def test_get_user_history_with_filters(service, mock_db, mock_redis):
    mock_collection = MagicMock()
    mock_cursor = MagicMock()
    mock_cursor.sort.return_value = mock_cursor
    mock_cursor.skip.return_value = mock_cursor
    mock_cursor.limit.return_value = mock_cursor
    mock_cursor.to_list = AsyncMock(return_value=[])
    mock_collection.find = MagicMock(return_value=mock_cursor)
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)
    
    result = await service.get_user_history(
        "user123",
        activity_types=[ActivityType.PAGE_VIEW],
        start_date=datetime.utcnow() - timedelta(days=7),
        end_date=datetime.utcnow(),
        limit=10
    )
    
    assert result is not None or result == []

@pytest.mark.asyncio
async def test_get_user_history_with_resource_filters(service, mock_db):
    mock_collection = MagicMock()
    mock_cursor = MagicMock()
    mock_cursor.sort.return_value = mock_cursor
    mock_cursor.skip.return_value = mock_cursor
    mock_cursor.limit.return_value = mock_cursor
    mock_cursor.to_list = AsyncMock(return_value=[])
    mock_collection.find = MagicMock(return_value=mock_cursor)
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)
    
    result = await service.get_user_history(
        "user123",
        activity_types=[ActivityType.PAGE_VIEW],
        start_date=datetime.utcnow() - timedelta(days=7),
        end_date=datetime.utcnow(),
        resource_type="page",
        resource_id="page123",
        limit=10
    )
    
    assert result is not None or result == []

@pytest.mark.asyncio
async def test_get_recent_activity(service, mock_redis):
    mock_redis.lrange = AsyncMock(return_value=['activity1', 'activity2'])
    
    result = await service.get_recent_activity("user123")
    
    assert result is not None or result == []

@pytest.mark.asyncio
async def test_get_recent_activity_when_redis_none(service):
    with patch('modules.History.service.history_service.get_redis_cache', new_callable=AsyncMock) as mock_redis:
        mock_redis.return_value = None
        result = await service.get_recent_activity("user123")
        assert result == []

@pytest.mark.asyncio
async def test_clear_user_history(service, mock_db, mock_redis):
    mock_db.__getitem__ = MagicMock(return_value=AsyncMock())
    mock_db.__getitem__().delete_many = AsyncMock(return_value=AsyncMock(deleted_count=5))
    
    result = await service.clear_user_history("user123")
    
    assert isinstance(result, bool)

@pytest.mark.asyncio
async def test_get_history_stats(service, mock_db):
    # Patch private methods to return sample data
    now = datetime.utcnow()
    with patch.object(service, '_get_total_activities', return_value=42), \
         patch.object(service, '_get_activities_by_type', return_value={'page_view': 10}), \
         patch.object(service, '_get_most_active_day', return_value=now), \
         patch.object(service, '_get_average_session_duration', return_value=123.4), \
         patch.object(service, '_get_top_locations', return_value=[{'location': 'NY', 'count': 5}]), \
         patch.object(service, '_get_recent_activity_types', return_value=[{'type': 'login', 'last_occurrence': now, 'count_in_recent': 3}]):
        stats = await service.get_history_stats("user123")
        assert stats.user_id == "user123"
        assert stats.total_activities == 42
        assert stats.activities_by_type == {'page_view': 10}
        assert stats.most_active_day == now
        assert stats.average_session_duration == 123.4
        assert len(stats.top_locations) == 1
        assert stats.recent_activity_types[0]['type'] == 'login'

# --- Additional tests to increase coverage ---

@pytest.mark.asyncio
async def test_get_user_sessions(service, mock_db):
    # Mock sessions collection and page_visits collection
    mock_sessions_collection = MagicMock()
    mock_page_visits_collection = MagicMock()

    now = datetime.utcnow()
    session_doc = {
        "_id": "sess1",
        "user_id": "user123",
        "start_time": now,
        "end_time": None,
        "device_type": "web",
        "ip_address": "127.0.0.1",
        "location": "NY",
        "activities_count": 0,
    }

    # Cursor for sessions
    mock_sessions_cursor = MockAsyncCursor([session_doc])
    mock_sessions_collection.find.return_value = mock_sessions_cursor

    # Cursor for page visits (empty)
    mock_page_cursor = MockAsyncCursor([])
    mock_page_visits_collection.find.return_value = mock_page_cursor

    def getitem(key):
        if key == "user_sessions":
            return mock_sessions_collection
        elif key == "page_visits":
            return mock_page_visits_collection
        return MagicMock()
    mock_db.__getitem__ = MagicMock(side_effect=getitem)

    result = await service.get_user_sessions("user123")

    assert isinstance(result, list)
    assert len(result) == 1
    assert isinstance(result[0], SessionSummary)
    assert result[0].session_id == "sess1"
    # duration should be computed from now - start_time
    assert result[0].duration >= 0

@pytest.mark.asyncio
async def test_create_session(service, mock_db):
    mock_collection = MagicMock()
    mock_collection.insert_one = AsyncMock()
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)

    session = await service.create_session(
        user_id="user123",
        device_type=DeviceType.WEB,
        ip_address="127.0.0.1",
        location="NY"
    )

    assert session is not None
    assert session.user_id == "user123"
    assert session.device_type == DeviceType.WEB
    mock_collection.insert_one.assert_called_once()

@pytest.mark.asyncio
async def test_end_session(service, mock_db, monkeypatch):
    # Stub bson module to avoid ImportError
    bson = types.ModuleType('bson')
    class DummyObjectId: pass
    bson.ObjectId = DummyObjectId
    monkeypatch.setitem(sys.modules, 'bson', bson)

    mock_collection = MagicMock()
    mock_result = MagicMock(modified_count=1)
    mock_collection.update_one = AsyncMock(return_value=mock_result)
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)

    result = await service.end_session("sess123")

    assert result is True
    mock_collection.update_one.assert_called_once()

@pytest.mark.asyncio
async def test_log_page_visit(service, mock_db):
    mock_page_visits = MagicMock()
    mock_page_visits.insert_one = AsyncMock()
    mock_sessions = MagicMock()
    mock_sessions.update_one = AsyncMock()
    mock_db.__getitem__.side_effect = lambda key: {
        "page_visits": mock_page_visits,
        "user_sessions": mock_sessions
    }[key]

    page_id = await service.log_page_visit(
        session_id="sess123",
        user_id="user123",
        path="/home",
        title="Home",
        referrer="/",
        duration=30
    )

    assert page_id is not None
    mock_page_visits.insert_one.assert_called_once()
    mock_sessions.update_one.assert_called_once()

@pytest.mark.asyncio
async def test_delete_user_history(service, mock_db):
    mock_collection = MagicMock()
    mock_result = MagicMock(deleted_count=5)
    mock_collection.delete_many = AsyncMock(return_value=mock_result)
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)

    deleted = await service.delete_user_history("user123")

    assert deleted == 5
    # delete_many called on history, sessions, and page_visits (same mock) => at least once
    assert mock_collection.delete_many.call_count >= 1

@pytest.mark.asyncio
async def test_delete_user_history_with_before_date(service, mock_db):
    mock_collection = MagicMock()
    mock_result = MagicMock(deleted_count=3)
    mock_collection.delete_many = AsyncMock(return_value=mock_result)
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)

    before = datetime.utcnow() - timedelta(days=30)
    deleted = await service.delete_user_history("user123", before_date=before)

    assert deleted == 3

@pytest.mark.asyncio
async def test_get_total_activities(service, mock_db):
    mock_collection = MagicMock()
    mock_collection.count_documents = AsyncMock(return_value=42)
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)

    total = await service._get_total_activities(mock_db, "user123")

    assert total == 42
    mock_collection.count_documents.assert_called_once_with({"user_id": "user123"})

@pytest.mark.asyncio
async def test_get_activities_by_type(service, mock_db):
    mock_collection = MagicMock()
    async def mock_aggregate(*args, **kwargs):
        yield {"_id": "page_view", "count": 5}
        yield {"_id": "login", "count": 3}
    mock_collection.aggregate = MagicMock(return_value=mock_aggregate())
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)

    result = await service._get_activities_by_type(mock_db, "user123")

    assert isinstance(result, dict)
    assert result["page_view"] == 5
    assert result["login"] == 3

@pytest.mark.asyncio
async def test_get_most_active_day(service, mock_db):
    mock_collection = MagicMock()
    async def mock_aggregate(*args, **kwargs):
        yield {"_id": "2025-07-21"}
    mock_collection.aggregate = MagicMock(return_value=mock_aggregate())
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)

    day = await service._get_most_active_day(mock_db, "user123")

    assert day is not None
    assert isinstance(day, datetime)

@pytest.mark.asyncio
async def test_get_average_session_duration(service, mock_db):
    mock_sessions = MagicMock()
    now = datetime.utcnow()
    mock_sessions.find.return_value.to_list = AsyncMock(return_value=[
        {"start_time": now - timedelta(seconds=100), "end_time": now},
        {"start_time": now - timedelta(seconds=200), "end_time": now}
    ])
    mock_db.__getitem__.side_effect = lambda key: {
        "user_sessions": mock_sessions
    }[key]

    avg = await service._get_average_session_duration(mock_db, "user123")

    # durations: 100 and 200 sec => average 150
    assert avg == 150.0

@pytest.mark.asyncio
async def test_get_top_locations(service, mock_db):
    mock_collection = MagicMock()
    async def mock_aggregate(*args, **kwargs):
        yield {"_id": "New York", "count": 10}
        yield {"_id": "London", "count": 5}
    mock_collection.aggregate = MagicMock(return_value=mock_aggregate())
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)

    locations = await service._get_top_locations(mock_db, "user123")

    assert isinstance(locations, list)
    assert len(locations) >= 1
    assert any(loc["location"] == "New York" for loc in locations)

@pytest.mark.asyncio
async def test_get_recent_activity_types(service, mock_db):
    mock_collection = MagicMock()
    async def mock_aggregate(*args, **kwargs):
        yield {"_id": "login", "last_occurrence": now, "count_in_recent": 5}
    mock_collection.aggregate = MagicMock(return_value=mock_aggregate())
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)

    now = datetime.utcnow()
    recent = await service._get_recent_activity_types(mock_db, "user123")

    assert isinstance(recent, list)
    assert len(recent) >= 1
    assert recent[0]["type"] == "login"

