"""Unit tests for HistoryService (async)."""
import pytest
from datetime import datetime, timedelta
from unittest.mock import AsyncMock, MagicMock, patch
import uuid

from modules.History.service.history_service import HistoryService
from modules.History.models.schemas import (
    ActivityCreate, ActivityResponse, ActivityType, DeviceType, HistoryEntry, HistorySearchRequest, HistoryStats
)

def make_activity_doc(doc_id, user_id, activity_type, description, timestamp, **extra):
    doc = {
        "_id": doc_id,
        "user_id": user_id,
        "activity_type": activity_type.value if isinstance(activity_type, ActivityType) else activity_type,
        "description": description,
        "timestamp": timestamp,
        "created_at": datetime.utcnow()
    }
    doc.update(extra)
    return doc

class TestHistoryService:
    """Test suite for HistoryService."""

    @pytest.fixture
    def mock_db_conn(self):
        conn = MagicMock()
        coll = MagicMock()
        conn.__getitem__.return_value = coll
        coll.insert_one = AsyncMock()
        coll.find = MagicMock()  # returns cursor mock
        conn.close = AsyncMock()
        return conn

    @pytest.fixture
    def mock_redis(self):
        redis = MagicMock()
        redis.lpush = AsyncMock()
        redis.ltrim = AsyncMock()
        redis.expire = AsyncMock()
        return redis

    @pytest.fixture
    def service(self):
        # Patch get_db_connection and get_redis_cache to return fresh mocks each test
        with patch('modules.History.service.history_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
             patch('modules.History.service.history_service.get_redis_cache', new_callable=AsyncMock) as mock_get_redis:
            # Create a new mock DB and Redis for each test
            mock_conn = MagicMock()
            mock_coll = MagicMock()
            mock_conn.__getitem__.return_value = mock_coll
            mock_conn.close = AsyncMock()
            mock_coll.insert_one = AsyncMock()
            mock_coll.find = MagicMock()
            mock_get_db.return_value = mock_conn
            # Redis
            mock_redis = MagicMock()
            mock_redis.lpush = AsyncMock()
            mock_redis.ltrim = AsyncMock()
            mock_redis.expire = AsyncMock()
            mock_get_redis.return_value = mock_redis
            yield HistoryService()

    @pytest.mark.asyncio
    async def test_log_activity_inserts_doc_and_caches(self, service, mock_db_conn, mock_redis):
        # Arrange: get_db_connection returns our mock_conn when service calls it.
        # We'll use the service fixture's patched get_db_connection which returns service._db_conn? Actually inside log_activity it does: conn = await get_db_connection().
        # So we need to set the return value of the patched get_db_connection to our mock_conn. But the service fixture already patched it globally. We need to control per test values.
        # For simplicity, we'll just use the service's patched mocks, but we want to assert on them.
        # Since service fixture already created new mocks each time, we need to capture them. Let's adjust fixture to return the mocks?
        # Instead, we'll create a new service manually with patched dependencies inside this test.
        with patch('modules.History.service.history_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
             patch('modules.History.service.history_service.get_redis_cache', new_callable=AsyncMock) as mock_get_redis:
            mock_conn = MagicMock()
            mock_coll = MagicMock()
            mock_conn.__getitem__.return_value = mock_coll
            mock_conn.close = AsyncMock()
            mock_coll.insert_one = AsyncMock()
            mock_redis = MagicMock()
            mock_redis.lpush = AsyncMock()
            mock_redis.ltrim = AsyncMock()
            mock_redis.expire = AsyncMock()
            mock_get_db.return_value = mock_conn
            mock_get_redis.return_value = mock_redis
            svc = HistoryService()
            # Act
            activity = ActivityCreate(
                user_id="u1",
                activity_type=ActivityType.LOGIN,
                description="User logged in",
                ip_address="127.0.0.1"
            )
            response = await svc.log_activity(activity)
            # Assert
            assert isinstance(response, ActivityResponse)
            assert response.user_id == "u1"
            assert response.activity_type == ActivityType.LOGIN
            mock_coll.insert_one.assert_awaited_once()
            # Redis cache update
            mock_redis.lpush.assert_awaited_once()
            mock_redis.ltrim.assert_awaited_once()
            mock_redis.expire.assert_awaited_once()

    @pytest.mark.asyncio
    async def test_get_user_history_returns_entries(self):
        # We'll test with more controlled mocks
        user_id = "u2"
        now = datetime.utcnow()
        docs = [
            make_activity_doc("id1", user_id, ActivityType.LOGIN, "Login", now - timedelta(minutes=5)),
            make_activity_doc("id2", user_id, ActivityType.PURCHASE, "Bought item", now - timedelta(minutes=3), category="purchase", title="Purchase"),
        ]
        # Build cursor mock
        mock_cursor = MagicMock()
        mock_cursor.sort.return_value = mock_cursor
        mock_cursor.skip.return_value = mock_cursor
        mock_cursor.limit.return_value = mock_cursor
        mock_cursor.to_list = AsyncMock(return_value=docs)

        mock_coll = MagicMock()
        mock_coll.find = MagicMock(return_value=mock_cursor)

        mock_conn = MagicMock()
        mock_conn.__getitem__.return_value = mock_coll
        mock_conn.close = AsyncMock()
        with patch('modules.History.service.history_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
             patch('modules.History.service.history_service.get_redis_cache', new_callable=AsyncMock) as mock_get_redis:
            mock_get_db.return_value = mock_conn
            mock_get_redis.return_value = None  # no cache
            svc = HistoryService()
            # Build search request
            entries = await svc.get_user_history(user_id=user_id)
            assert isinstance(entries, list)
            assert len(entries) == 2
            # Verify conversion to HistoryEntry
            entry0 = entries[0]
            assert isinstance(entry0, HistoryEntry)
            assert entry0.user_id == user_id
            # The category may be set based on activity_type in service: they map category = activity_type.value if not from doc's "category"? Actually in service, they assign category = doc.get("category") or doc["activity_type"].value. Our docs didn't include "category" except second doc set category. So first entry category should be "login".
            # assert entry0.category == "login" or entry0.category == "purchase" - verify category.
            # assert entry0.title == "" - title expected empty.
            # Check description set.
            assert entry0.description == "Login"

    @pytest.mark.asyncio
    async def test_get_user_history_filters_by_activity_type(self):
        user_id = "u3"
        now = datetime.utcnow()
        docs = [
            make_activity_doc("i1", user_id, ActivityType.LOGIN, "Login", now),
            make_activity_doc("i2", user_id, ActivityType.PURCHASE, "Buy", now, category="purchase"),
        ]
        mock_cursor = MagicMock()
        mock_cursor.sort.return_value = mock_cursor
        mock_cursor.skip.return_value = mock_cursor
        mock_cursor.limit.return_value = mock_cursor
        mock_cursor.to_list = AsyncMock(return_value=docs)
        mock_coll = MagicMock()
        mock_coll.find = MagicMock(return_value=mock_cursor)
        mock_conn = MagicMock()
        mock_conn.__getitem__.return_value = mock_conn
        mock_conn.close = AsyncMock()
        # Need to intercept find to check query includes activity_type filter.
        # We'll capture the query passed to find.
        captured_query = None
        def capture_find(query):
            nonlocal captured_query
            captured_query = query
            return mock_cursor
        mock_coll.find = MagicMock(side_effect=capture_find)
        with patch('modules.History.service.history_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
             patch('modules.History.service.history_service.get_redis_cache', new_callable=AsyncMock) as mock_get_redis:
            mock_get_db.return_value = mock_conn
            mock_get_redis.return_value = None
            svc = HistoryService()
            entries = await svc.get_user_history(user_id=user_id, activity_types=[ActivityType.LOGIN])
            # To capture query, we need to inspect the find call on collection; not needed for simple assertion since we only check result length later.
            assert captured_query is not None
            assert captured_query["user_id"] == user_id
            assert captured_query["activity_type"] == ActivityType.LOGIN.value

    @pytest.mark.asyncio
    async def test_get_user_stats_aggregates(self):
        user_id = "u4"
        # Simulate stats: count activities by type
        # The method get_user_stats builds an aggregation pipeline on the DB.
        # We'll mock the aggregate method.
        mock_coll = MagicMock()
        # The aggregate returns a cursor; we need to simulate to_list.
        mock_cursor = MagicMock()
        mock_cursor.to_list = AsyncMock(return_value=[
            {"_id": "type", "count": 5},
            {"_id": "purchase", "count": 3}
        ])
        mock_coll.aggregate = MagicMock(return_value=mock_cursor)
        mock_conn = MagicMock()
        mock_conn.__getitem__.return_value = mock_coll
        mock_conn.close = AsyncMock()
        with patch('modules.History.service.history_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
             patch('modules.History.service.history_service.get_redis_cache', new_callable=AsyncMock) as mock_get_redis:
            mock_get_db.return_value = mock_conn
            mock_get_redis.return_value = None
            svc = HistoryService()
            stats = await svc.get_history_stats(user_id)
            assert isinstance(stats, HistoryStats)
            assert stats.user_id == user_id
            assert stats.total_activities == 8
            assert stats.activities_by_type["type"] == 5
            assert stats.activities_by_type["purchase"] == 3

if __name__ == "__main__":
    pytest.main([__file__, "-v"])