"""Unit tests for SettingsService (async)."""
import pytest
from datetime import datetime
from unittest.mock import AsyncMock, MagicMock, patch
import json

from modules.Settings.service.settings_service import SettingsService
from modules.Settings.models.schemas import (
    UserSettings, ProfileSettings, NotificationSettings, PrivacySettings,
    AppearanceSettings, AccessibilitySettings, SecuritySettings, IntegrationSettings
)

class TestSettingsService:
    """Test suite for SettingsService."""

    @pytest.fixture
    def mock_cache(self):
        cache = MagicMock()
        cache.get = AsyncMock()
        cache.set = AsyncMock()
        return cache

    @pytest.fixture
    def mock_db(self):
        conn = MagicMock()
        coll = MagicMock()
        conn.__getitem__.return_value = coll
        coll.find_one = AsyncMock()
        coll.insert_one = AsyncMock()
        coll.update_one = AsyncMock()
        return conn

    @pytest.fixture
    def service(self):
        # Patch get_db_connection and get_cache to use mocks
        with patch('modules.Settings.service.settings_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
             patch('modules.Settings.service.settings_service.get_cache', new_callable=AsyncMock) as mock_get_cache:
            # Create a fresh mock DB and cache per test
            mock_conn = MagicMock()
            mock_coll = MagicMock()
            mock_conn.__getitem__.return_value = mock_coll
            mock_conn.close = AsyncMock()
            mock_coll.find_one = AsyncMock()
            mock_coll.insert_one = AsyncMock()
            mock_coll.update_one = AsyncMock()
            mock_get_db.return_value = mock_conn
            mock_get_cache.return_value = MagicMock(get=AsyncMock(), set=AsyncMock())
            yield SettingsService()

    @pytest.mark.asyncio
    async def test_get_settings_cache_hit(self, service):
        # Arrange: build a minimal UserSettings
        now = datetime.now()
        settings = UserSettings(
            user_id="u1",
            profile=ProfileSettings(display_name="Alice"),
            notifications=NotificationSettings(),
            privacy=PrivacySettings(),
            appearance=AppearanceSettings(),
            accessibility=AccessibilitySettings(),
            security=SecuritySettings(),
            integrations=IntegrationSettings(),
            created_at=now,
            updated_at=now
        )
        json_str = settings.model_dump_json()
        # Mock cache get to return this JSON
        service._SettingsService__cache = None  # not using; better to mock get_cache which is used inside method
        # Actually inside get_settings, they do: cache = await get_cache() -> that's patched to our mock.
        # We'll set the mock's get to return json_str
        with patch('modules.Settings.service.settings_service.get_cache', new_callable=AsyncMock) as mock_get_cache:
            mock_cache = MagicMock()
            mock_cache.get = AsyncMock(return_value=json_str)
            mock_get_cache.return_value = mock_cache
            # Also need to patch get_db_connection to avoid DB use in case cache miss but here we have hit.
            mock_db_conn = MagicMock()
            mock_db_conn.close = AsyncMock()
            mock_db_conn.__getitem__.return_value = MagicMock(find_one=AsyncMock())
            with patch('modules.Settings.service.settings_service.get_db_connection', new_callable=AsyncMock) as mock_get_db:
                mock_get_db.return_value = mock_db_conn
                result = await service.get_settings("u1")
                assert isinstance(result, UserSettings)
                assert result.user_id == "u1"
                assert result.profile.display_name == "Alice"
                # Ensure DB not queried
                mock_db_conn.__getitem__.return_value.find_one.assert_not_called()

    @pytest.mark.asyncio
    async def test_get_settings_cache_miss_db_exists(self, service):
        user_id = "u2"
        now_iso = datetime.now().isoformat()
        doc = {
            "user_id": user_id,
            "profile": {"display_name": "Bob", "language": "en"},
            "notifications": {},
            "privacy": {},
            "appearance": {},
            "accessibility": {},
            "security": {},
            "integrations": {},
            "created_at": now_iso,
            "updated_at": now_iso
        }
        # Mock get_cache returns None (cache miss)
        mock_cache = MagicMock()
        mock_cache.get = AsyncMock(return_value=None)
        mock_cache.set = AsyncMock()
        # Mock DB find_one to return doc
        mock_db_conn = MagicMock()
        mock_coll = MagicMock()
        mock_coll.find_one = AsyncMock(return_value=doc)
        mock_db_conn.__getitem__.return_value = mock_coll
        mock_db_conn.close = AsyncMock()
        with patch('modules.Settings.service.settings_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
             patch('modules.Settings.service.settings_service.get_cache', new_callable=AsyncMock) as mock_get_cache:
            mock_get_db.return_value = mock_db_conn
            mock_get_cache.return_value = mock_cache
            result = await service.get_settings(user_id)
            assert isinstance(result, UserSettings)
            assert result.user_id == user_id
            assert result.profile.display_name == "Bob"
            # Cache set should have been called
            mock_cache.set.assert_awaited_once()
            # Insert not called
            mock_coll.insert_one.assert_not_called()

    @pytest.mark.asyncio
    async def test_get_settings_creates_default_if_missing(self, service):
        user_id = "u3"
        # Cache miss, DB find_one returns None
        mock_cache = MagicMock()
        mock_cache.get = AsyncMock(return_value=None)
        mock_cache.set = AsyncMock()
        mock_db_conn = MagicMock()
        mock_coll = MagicMock()
        mock_coll.find_one = AsyncMock(return_value=None)
        mock_coll.insert_one = AsyncMock()
        mock_db_conn.__getitem__.return_value = mock_coll
        mock_db_conn.close = AsyncMock()
        with patch('modules.Settings.service.settings_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
             patch('modules.Settings.service.settings_service.get_cache', new_callable=AsyncMock) as mock_get_cache:
            mock_get_db.return_value = mock_db_conn
            mock_get_cache.return_value = mock_cache
            result = await service.get_settings(user_id)
            assert isinstance(result, UserSettings)
            assert result.user_id == user_id
            # Default settings: profile.display_name should be empty string
            assert result.profile.display_name == ""
            # Verify insert_one called with a document that has user_id
            mock_coll.insert_one.assert_awaited_once()
            inserted_doc = mock_coll.insert_one.call_args[0][0]
            assert inserted_doc["user_id"] == user_id
            # Cache set called
            mock_cache.set.assert_awaited_once()

if __name__ == "__main__":
    pytest.main([__file__, "-v"])