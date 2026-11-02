import pytest
from unittest.mock import AsyncMock, MagicMock, patch
from datetime import datetime
from modules.Settings.service.settings_service import SettingsService
from modules.Settings.models.schemas import (
    UserSettings, ProfileSettings, NotificationSettings, 
    PrivacySettings, AppearanceSettings, SecuritySettings,
    AccessibilitySettings, IntegrationSettings
)

@pytest.fixture
def service():
    return SettingsService()

@pytest.fixture
def mock_db():
    with patch('modules.Settings.service.settings_service.get_db_connection', new_callable=AsyncMock) as mock:
        conn = AsyncMock()
        mock.return_value = conn
        yield conn

@pytest.fixture
def mock_cache():
    with patch('modules.Settings.service.settings_service.get_cache', new_callable=AsyncMock) as mock:
        cache = AsyncMock()
        cache.get = AsyncMock(return_value=None)
        mock.return_value = cache
        yield cache

@pytest.mark.asyncio
async def test_create_default_settings(service, mock_db, mock_cache):
    mock_db.__getitem__ = MagicMock(return_value=AsyncMock())
    mock_db.__getitem__().insert_one = AsyncMock()
    
    settings = await service.create_default_settings("user123")
    
    assert settings is not None
    assert settings.user_id == "user123"

@pytest.mark.asyncio
async def test_update_settings(service, mock_db, mock_cache):
    mock_db.__getitem__ = MagicMock(return_value=AsyncMock())
    mock_db.__getitem__().update_one = AsyncMock()
    
    # Mock get_settings to return updated settings
    now = datetime.utcnow()
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name="Test User"),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    
    with patch.object(service, 'get_settings', AsyncMock(return_value=mock_settings)):
        result = await service.update_settings("user123", {"theme": "dark"}, "appearance")
        assert result is not None

@pytest.mark.asyncio
async def test_update_profile(service, mock_db, mock_cache):
    now = datetime.utcnow()
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name=""),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    
    with patch.object(service, 'get_settings', AsyncMock(return_value=mock_settings)):
        with patch.object(service, 'update_settings', AsyncMock(return_value=mock_settings)):
            result = await service.update_profile("user123", {"display_name": "Test User"})
            assert result is not None

@pytest.mark.asyncio
async def test_block_user(service, mock_db, mock_cache):
    from modules.Settings.models.schemas import BlockUserRequest
    
    now = datetime.utcnow()
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name=""),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(blocked_users=[], muted_users=[]),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    
    request = BlockUserRequest(user_id="blocked_user", action="block")
    
    with patch.object(service, 'get_settings', AsyncMock(return_value=mock_settings)):
        with patch.object(service, 'update_privacy', AsyncMock()):
            result = await service.block_user("user123", request)
            assert result is not None

@pytest.mark.asyncio
async def test_export_user_data(service, mock_db, mock_cache):
    from modules.Settings.models.schemas import ExportDataRequest
    
    now = datetime.utcnow()
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name=""),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(blocked_users=[], muted_users=[]),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    
    request = ExportDataRequest(include_settings=True)
    
    with patch.object(service, 'get_settings', AsyncMock(return_value=mock_settings)):
        result = await service.export_user_data("user123", request)
        assert "exported_at" in result

@pytest.mark.asyncio
async def test_delete_account_invalid_confirmation(service, mock_db, mock_cache):
    from modules.Settings.models.schemas import DeleteAccountRequest
    
    request = DeleteAccountRequest(confirmation="wrong", reason=None, feedback=None, export_data=False)
    
    result = await service.delete_account("user123", request)
    assert result is False

@pytest.mark.asyncio
async def test_reset_to_defaults(service, mock_db, mock_cache):
    mock_db.__getitem__ = MagicMock(return_value=AsyncMock())
    mock_db.__getitem__().delete_one = AsyncMock()
    
    now = datetime.utcnow()
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name=""),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    
    with patch.object(service, 'create_default_settings', AsyncMock(return_value=mock_settings)):
        result = await service.reset_to_defaults("user123")
        assert result is not None

@pytest.mark.asyncio
async def test_get_settings_cache_hit(service, mock_db, mock_cache):
    now = datetime.utcnow()
    settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name="Test"),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    mock_cache.get.return_value = settings.model_dump_json()
    
    result = await service.get_settings("user123")
    
    assert result.user_id == "user123"
    mock_cache.get.assert_called_once_with(f"{service.SETTINGS_CACHE_PREFIX}user123")
    mock_db.__getitem__.assert_not_called()

@pytest.mark.asyncio
async def test_get_settings_cache_miss_db_found(service, mock_db, mock_cache):
    now = datetime.utcnow()
    doc = {
        "user_id": "user123",
        "profile": {"display_name": "Test"},
        "notifications": {},
        "privacy": {},
        "appearance": {},
        "accessibility": {},
        "security": {},
        "integrations": {},
        "created_at": now,
        "updated_at": now
    }
    mock_cache.get.return_value = None
    mock_collection = MagicMock()
    mock_collection.find_one = AsyncMock(return_value=doc)
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)
    
    result = await service.get_settings("user123")
    
    assert result.user_id == "user123"
    mock_cache.set.assert_called_once()

@pytest.mark.asyncio
async def test_get_settings_cache_miss_db_not_found_creates_default(service, mock_db, mock_cache):
    mock_cache.get.return_value = None
    mock_collection = MagicMock()
    mock_collection.find_one = AsyncMock(return_value=None)
    mock_collection.insert_one = AsyncMock()
    mock_db.__getitem__ = MagicMock(return_value=mock_collection)
    
    result = await service.get_settings("user123")
    
    assert isinstance(result, UserSettings)
    assert result.user_id == "user123"
    mock_cache.set.assert_called_once()

@pytest.mark.asyncio
async def test_update_settings_invalidates_cache(service, mock_db, mock_cache):
    now = datetime.utcnow()
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name="Updated"),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    mock_db.__getitem__ = MagicMock(return_value=AsyncMock())
    mock_db.__getitem__().update_one = AsyncMock()
    mock_cache.delete = AsyncMock()
    
    with patch.object(service, 'get_settings', AsyncMock(return_value=mock_settings)):
        result = await service.update_settings("user123", {"profile.display_name": "Updated"})
        mock_cache.delete.assert_called_once_with(f"{service.SETTINGS_CACHE_PREFIX}user123")

@pytest.mark.asyncio
async def test_update_notifications(service, mock_db, mock_cache):
    now = datetime.utcnow()
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name=""),
        notifications=NotificationSettings(email_enabled=False, push_enabled=False),
        privacy=PrivacySettings(),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    with patch.object(service, 'get_settings', AsyncMock(return_value=mock_settings)):
        with patch.object(service, 'update_settings', AsyncMock(return_value=mock_settings)):
            result = await service.update_notifications("user123", {"email_enabled": True})
            assert isinstance(result, NotificationSettings)
            assert result.email_enabled is True

@pytest.mark.asyncio
async def test_update_privacy(service, mock_db, mock_cache):
    from modules.Settings.models.schemas import PrivacyLevel
    now = datetime.utcnow()
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name=""),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(profile_visibility=PrivacyLevel.PRIVATE),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    with patch.object(service, 'get_settings', AsyncMock(return_value=mock_settings)):
        with patch.object(service, 'update_settings', AsyncMock(return_value=mock_settings)):
            result = await service.update_privacy("user123", {"profile_visibility": PrivacyLevel.PUBLIC})
            assert isinstance(result, PrivacySettings)
            assert result.profile_visibility == PrivacyLevel.PUBLIC

@pytest.mark.asyncio
async def test_delete_account_success(service, mock_db, mock_cache):
    from modules.Settings.models.schemas import DeleteAccountRequest
    
    settings_collection = AsyncMock()
    deletion_collection = AsyncMock()
    mock_db.__getitem__ = MagicMock(side_effect=lambda key: settings_collection if key == service.SETTINGS_COLLECTION else deletion_collection if key == "account_deletions" else AsyncMock())
    
    request = DeleteAccountRequest(
        confirmation="DELETE",
        reason="testing",
        feedback="test feedback",
        export_data=True
    )
    
    result = await service.delete_account("user123", request)
    
    assert result is True
    settings_collection.delete_one.assert_called_once_with({"user_id": "user123"})
    mock_cache.delete.assert_called_once_with(f"{service.SETTINGS_CACHE_PREFIX}user123")
    deletion_collection.insert_one.assert_called_once()
    insert_call = deletion_collection.insert_one.call_args[0][0]
    assert insert_call["user_id"] == "user123"
    assert insert_call["reason"] == "testing"
    assert insert_call["feedback"] == "test feedback"
    assert insert_call["exported"] is True

@pytest.mark.asyncio
async def test_update_appearance(service, mock_db, mock_cache):
    from modules.Settings.models.schemas import AppearanceSettings
    now = datetime.utcnow()
    initial_appearance = AppearanceSettings(theme="system", accent_color="#000000", font_size="small", compact_mode=False, sidebar_collapsed=True, animations_enabled=True, high_contrast=False)
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name=""),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(),
        appearance=initial_appearance,
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    with patch.object(service, 'get_settings', AsyncMock(return_value=mock_settings)):
        with patch.object(service, 'update_settings', AsyncMock(return_value=mock_settings)) as mock_update:
            result = await service.update_appearance("user123", {"theme": "light"})
            assert isinstance(result, AppearanceSettings)
            # Check update_settings called with merged appearance dict
            mock_update.assert_called_once()
            args = mock_update.call_args[0]
            assert args[0] == "user123"
            assert isinstance(args[1], dict)
            assert "appearance" in args[1]
            updated_appearance = args[1]["appearance"]
            assert updated_appearance["theme"] == "light"
            # other fields should be from initial_appearance
            assert updated_appearance["accent_color"] == initial_appearance.accent_color
            assert updated_appearance["font_size"] == initial_appearance.font_size
            assert args[2] == "appearance"

@pytest.mark.asyncio
async def test_update_security(service, mock_db, mock_cache):
    from modules.Settings.models.schemas import SecuritySettings
    now = datetime.utcnow()
    initial_security = SecuritySettings(
        two_factor_enabled=False,
        two_factor_method=None,
        login_alerts=True,
        login_alert_email=True,
        login_alert_push=True,
        session_timeout=3600,
        max_sessions=5,
        biometric_enabled=False,
        password_last_changed=None
    )
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name=""),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=initial_security,
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    with patch.object(service, 'get_settings', AsyncMock(return_value=mock_settings)):
        with patch.object(service, 'update_settings', AsyncMock(return_value=mock_settings)) as mock_update:
            result = await service.update_security("user123", {"two_factor_enabled": True})
            assert isinstance(result, SecuritySettings)
            mock_update.assert_called_once()
            args = mock_update.call_args[0]
            assert args[0] == "user123"
            assert "security" in args[1]
            updated_security = args[1]["security"]
            assert updated_security["two_factor_enabled"] is True
            # other fields unchanged
            assert updated_security["login_alerts"] == initial_security.login_alerts
            assert args[2] == "security"

@pytest.mark.asyncio
async def test_block_user_actions(service, mock_db, mock_cache):
    from modules.Settings.models.schemas import BlockUserRequest
    now = datetime.utcnow()
    # initial privacy with some blocked/muted users
    initial_blocked = ["userA", "userB"]
    initial_muted = ["userC"]
    mock_settings = UserSettings(
        user_id="user123",
        profile=ProfileSettings(display_name=""),
        notifications=NotificationSettings(),
        privacy=PrivacySettings(blocked_users=initial_blocked, muted_users=initial_muted),
        appearance=AppearanceSettings(),
        accessibility=AccessibilitySettings(),
        security=SecuritySettings(),
        integrations=IntegrationSettings(),
        created_at=now,
        updated_at=now
    )
    with patch.object(service, 'get_settings', AsyncMock(return_value=mock_settings)):
        with patch.object(service, 'update_privacy', AsyncMock(return_value=mock_settings.privacy)) as mock_update_privacy:
            # unblock
            request_unblock = BlockUserRequest(user_id="userA", action="unblock")
            result = await service.block_user("user123", request_unblock)
            assert result is not None
            mock_update_privacy.assert_called_with("user123", {"blocked_users": ["userB"], "muted_users": initial_muted})
            # reset mock
            mock_update_privacy.reset_mock()
            # mute
            request_mute = BlockUserRequest(user_id="userD", action="mute")
            await service.block_user("user123", request_mute)
            # Verify mute call: blocked_users unchanged, muted_users contains both initial and new (order not guaranteed)
            args = mock_update_privacy.call_args[0]
            assert args[0] == "user123"
            privacy_dict = args[1]
            assert set(privacy_dict["blocked_users"]) == set(initial_blocked)
            assert set(privacy_dict["muted_users"]) == {"userC", "userD"}
            mock_update_privacy.reset_mock()
            # unmute
            request_unmute = BlockUserRequest(user_id="userC", action="unmute")
            await service.block_user("user123", request_unmute)
            # Verify unblock+unmute call: blocked_users order not important, muted empty
            args = mock_update_privacy.call_args[0]
            assert args[0] == "user123"
            privacy_dict = args[1]
            assert set(privacy_dict["blocked_users"]) == set(initial_blocked)
            assert privacy_dict["muted_users"] == []
