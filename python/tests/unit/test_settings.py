"""Tests for Settings Service"""
import pytest
from unittest.mock import AsyncMock, MagicMock, patch
from modules.Settings.service.settings_service import SettingsService
from modules.Settings.models.schemas import UserSettings
from datetime import datetime

from tests.utils.mocks import MockConnection


@pytest.fixture
def settings_service():
    return SettingsService()

@pytest.mark.asyncio
async def test_get_settings_success(settings_service):
    mock_conn = MockConnection(find_one_return={
        "user_id": "user123",
        "profile": {"display_name": "Test User", "email": "test@example.com"},
        "notifications": {"email": True, "push": False},
        "updated_at": datetime.utcnow()
    })
    mock_cache = MagicMock()
    mock_cache.get = AsyncMock(return_value=None)
    mock_cache.set = AsyncMock()

    with patch('modules.Settings.service.settings_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
         patch('modules.Settings.service.settings_service.get_cache', new_callable=AsyncMock) as mock_get_cache:
        mock_get_db.return_value = mock_conn
        mock_get_cache.return_value = mock_cache

        settings = await settings_service.get_settings("user123")

        assert isinstance(settings, UserSettings)
        assert settings.user_id == "user123"
        mock_conn._coll.find_one.assert_called_once()

@pytest.mark.asyncio
async def test_update_settings_success(settings_service):
    mock_conn = MockConnection(update_one_return=MagicMock(matched_count=1, modified_count=1))
    mock_cache = MagicMock()
    mock_cache.delete = AsyncMock()
    mock_cache.get = AsyncMock(return_value=None)
    mock_cache.set = AsyncMock()

    with patch('modules.Settings.service.settings_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
         patch('modules.Settings.service.settings_service.get_cache', new_callable=AsyncMock) as mock_get_cache:
        mock_get_db.return_value = mock_conn
        mock_get_cache.return_value = mock_cache

        updates = {"theme": "dark", "language": "vi"}
        result = await settings_service.update_settings("user123", updates)

        assert isinstance(result, UserSettings)
        mock_conn._coll.update_one.assert_called_once()
        mock_cache.delete.assert_called_once()

@pytest.mark.asyncio
async def test_update_settings_with_section(settings_service):
    mock_conn = MockConnection(update_one_return=MagicMock(matched_count=1, modified_count=1))
    mock_cache = MagicMock()
    mock_cache.delete = AsyncMock()
    mock_cache.get = AsyncMock(return_value=None)
    mock_cache.set = AsyncMock()

    with patch('modules.Settings.service.settings_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
         patch('modules.Settings.service.settings_service.get_cache', new_callable=AsyncMock) as mock_get_cache:
        mock_get_db.return_value = mock_conn
        mock_get_cache.return_value = mock_cache

        updates = {"email": True}
        result = await settings_service.update_settings("user123", updates, section="notifications")

        assert isinstance(result, UserSettings)
        mock_conn._coll.update_one.assert_called_once()

@pytest.mark.asyncio
async def test_get_settings_not_found(settings_service):
    # Simulate no existing settings; service should create defaults
    mock_conn = MockConnection(
        find_one_return=None,
        insert_one_return=MagicMock(inserted_id="some_id")
    )
    mock_cache = MagicMock()
    mock_cache.get = AsyncMock(return_value=None)
    mock_cache.set = AsyncMock()

    with patch('modules.Settings.service.settings_service.get_db_connection', new_callable=AsyncMock) as mock_get_db, \
         patch('modules.Settings.service.settings_service.get_cache', new_callable=AsyncMock) as mock_get_cache:
        mock_get_db.return_value = mock_conn
        mock_get_cache.return_value = mock_cache

        settings = await settings_service.get_settings("nonexistent")
        assert isinstance(settings, UserSettings)
        assert settings.user_id == "nonexistent"
        mock_conn._coll.insert_one.assert_called_once()

@pytest.mark.asyncio
async def test_update_settings_invalid_user(settings_service):
    mock_db = AsyncMock()
    mock_db.update_one.return_value = AsyncMock(matched_count=0, modified_count=0)

    with patch('modules.Settings.service.settings_service.get_db_connection', return_value=mock_db):
        with pytest.raises(Exception):
            await settings_service.update_settings("invalid_user", {"theme": "dark"})

if __name__ == "__main__":
    pytest.main([__file__, "-v"])
