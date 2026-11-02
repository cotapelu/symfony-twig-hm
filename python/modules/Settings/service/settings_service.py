"""
Settings Service - User Settings Management
Handles CRUD operations for user settings.
"""

import uuid
from datetime import datetime
from typing import Optional, Dict, Any, List
import json

import sqlite3

from app.appcore.database import get_db_connection
from app.appcore.cache import get_cache

from ..models.schemas import (
    UserSettings,
    ProfileSettings,
    NotificationSettings,
    PrivacySettings,
    AppearanceSettings,
    AccessibilitySettings,
    SecuritySettings,
    IntegrationSettings,
    SettingsUpdateRequest,
    NotificationPreferencesUpdate,
    PrivacyUpdateRequest,
    AppearanceUpdateRequest,
    SecurityUpdateRequest,
    BlockUserRequest,
    ExportDataRequest,
    DeleteAccountRequest,
)


class SettingsService:
    """Service for managing user settings"""
    
    SETTINGS_COLLECTION = "user_settings"
    SETTINGS_CACHE_PREFIX = "user_settings:"
    SETTINGS_CACHE_TTL = 3600  # 1 hour
    
    async def get_settings(self, user_id: str) -> UserSettings:
        """
        Get user settings.
        
        Args:
            user_id: User ID
            
        Returns:
            User settings
        """
        # Try cache first
        cache = await get_cache()
        if cache:
            cached = await cache.get(f"{self.SETTINGS_CACHE_PREFIX}{user_id}")
            if cached:
                return UserSettings(**json.loads(cached))
        
        # Fetch from database
        conn = await get_db_connection()
        try:
            doc = await conn[self.SETTINGS_COLLECTION].find_one({"user_id": user_id})
            
            if not doc:
                # Create default settings
                return await self.create_default_settings(user_id)
            
            # Parse settings sections
            settings = UserSettings(
                user_id=doc["user_id"],
                profile=ProfileSettings(**doc.get("profile", {})),
                notifications=NotificationSettings(**doc.get("notifications", {})),
                privacy=PrivacySettings(**doc.get("privacy", {})),
                appearance=AppearanceSettings(**doc.get("appearance", {})),
                accessibility=AccessibilitySettings(**doc.get("accessibility", {})),
                security=SecuritySettings(**doc.get("security", {})),
                integrations=IntegrationSettings(**doc.get("integrations", {})),
                created_at=doc.get("created_at", datetime.utcnow()),
                updated_at=doc.get("updated_at", datetime.utcnow())
            )
            
            # Cache settings
            if cache:
                await cache.set(
                    f"{self.SETTINGS_CACHE_PREFIX}{user_id}",
                    settings.model_dump_json(),
                    ex=self.SETTINGS_CACHE_TTL
                )
            
            return settings
        finally:
            await conn.close()
    
    async def create_default_settings(self, user_id: str) -> UserSettings:
        """
        Create default settings for a user.
        
        Args:
            user_id: User ID
            
        Returns:
            Default user settings
        """
        now = datetime.utcnow()
        settings = UserSettings(
            user_id=user_id,
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
        
        conn = await get_db_connection()
        try:
            await conn[self.SETTINGS_COLLECTION].insert_one({
                "user_id": user_id,
                "profile": settings.profile.model_dump(),
                "notifications": settings.notifications.model_dump(),
                "privacy": settings.privacy.model_dump(),
                "appearance": settings.appearance.model_dump(),
                "accessibility": settings.accessibility.model_dump(),
                "security": settings.security.model_dump(),
                "integrations": settings.integrations.model_dump(),
                "created_at": now,
                "updated_at": now
            })
            
            # Cache settings
            cache = await get_cache()
            if cache:
                await cache.set(
                    f"{self.SETTINGS_CACHE_PREFIX}{user_id}",
                    settings.model_dump_json(),
                    ex=self.SETTINGS_CACHE_TTL
                )
            
            return settings
        finally:
            await conn.close()
    
    async def update_settings(
        self,
        user_id: str,
        updates: Dict[str, Any],
        section: Optional[str] = None
    ) -> UserSettings:
        """
        Update user settings.
        
        Args:
            user_id: User ID
            updates: Settings updates
            section: Specific section to update
            
        Returns:
            Updated user settings
        """
        conn = await get_db_connection()
        try:
            now = datetime.utcnow()
            
            if section:
                if len(updates) == 1 and "." not in list(updates.keys())[0]:
                    update_field = f"{section}.{list(updates.keys())[0]}"
                else:
                    update_field = section
                
                await conn[self.SETTINGS_COLLECTION].update_one(
                    {"user_id": user_id},
                    {
                        "$set": {
                            f"{section}.{k}": v for k, v in updates.items()
                        },
                        "$set": {"updated_at": now}
                    },
                    upsert=True
                )
            else:
                # Update top-level or merge
                await conn[self.SETTINGS_COLLECTION].update_one(
                    {"user_id": user_id},
                    {
                        "$set": updates,
                        "$set": {"updated_at": now}
                    },
                    upsert=True
                )
            
            # Invalidate cache
            cache = await get_cache()
            if cache:
                await cache.delete(f"{self.SETTINGS_CACHE_PREFIX}{user_id}")
            
            # Return updated settings
            return await self.get_settings(user_id)
        finally:
            await conn.close()
    
    async def update_profile(self, user_id: str, updates: Dict[str, Any]) -> ProfileSettings:
        """
        Update profile settings.
        
        Args:
            user_id: User ID
            updates: Profile updates
            
        Returns:
            Updated profile settings
        """
        settings = await self.get_settings(user_id)
        
        current = settings.profile.model_dump()
        current.update(updates)
        
        await self.update_settings(user_id, {"profile": current}, "profile")
        
        return ProfileSettings(**current)
    
    async def update_notifications(
        self,
        user_id: str,
        updates: Dict[str, Any]
    ) -> NotificationSettings:
        """
        Update notification settings.
        
        Args:
            user_id: User ID
            updates: Notification updates
            
        Returns:
            Updated notification settings
        """
        settings = await self.get_settings(user_id)
        
        current = settings.notifications.model_dump()
        current.update(updates)
        
        await self.update_settings(user_id, {"notifications": current}, "notifications")
        
        return NotificationSettings(**current)
    
    async def update_privacy(self, user_id: str, updates: Dict[str, Any]) -> PrivacySettings:
        """
        Update privacy settings.
        
        Args:
            user_id: User ID
            updates: Privacy updates
            
        Returns:
            Updated privacy settings
        """
        settings = await self.get_settings(user_id)
        
        current = settings.privacy.model_dump()
        current.update(updates)
        
        await self.update_settings(user_id, {"privacy": current}, "privacy")
        
        return PrivacySettings(**current)
    
    async def update_appearance(
        self,
        user_id: str,
        updates: Dict[str, Any]
    ) -> AppearanceSettings:
        """
        Update appearance settings.
        
        Args:
            user_id: User ID
            updates: Appearance updates
            
        Returns:
            Updated appearance settings
        """
        settings = await self.get_settings(user_id)
        
        current = settings.appearance.model_dump()
        current.update(updates)
        
        await self.update_settings(user_id, {"appearance": current}, "appearance")
        
        return AppearanceSettings(**current)
    
    async def update_security(self, user_id: str, updates: Dict[str, Any]) -> SecuritySettings:
        """
        Update security settings.
        
        Args:
            user_id: User ID
            updates: Security updates
            
        Returns:
            Updated security settings
        """
        settings = await self.get_settings(user_id)
        
        current = settings.security.model_dump()
        current.update(updates)
        
        await self.update_settings(user_id, {"security": current}, "security")
        
        return SecuritySettings(**current)
    
    async def block_user(self, user_id: str, request: BlockUserRequest) -> PrivacySettings:
        """
        Block or unblock a user.
        
        Args:
            user_id: User ID
            request: Block request
            
        Returns:
            Updated privacy settings
        """
        settings = await self.get_settings(user_id)
        
        blocked = set(settings.privacy.blocked_users)
        muted = set(settings.privacy.muted_users)
        
        if request.action == "block":
            blocked.add(request.user_id)
        elif request.action == "unblock":
            blocked.discard(request.user_id)
        elif request.action == "mute":
            muted.add(request.user_id)
        elif request.action == "unmute":
            muted.discard(request.user_id)
        
        await self.update_privacy(user_id, {
            "blocked_users": list(blocked),
            "muted_users": list(muted)
        })
        
        return settings.privacy
    
    async def export_user_data(
        self,
        user_id: str,
        request: ExportDataRequest
    ) -> Dict[str, Any]:
        """
        Export user data for GDPR compliance.
        
        Args:
            user_id: User ID
            request: Export request
            
        Returns:
            Exported data
        """
        data = {"exported_at": datetime.utcnow().isoformat()}
        
        if request.include_settings:
            settings = await self.get_settings(user_id)
            data["settings"] = settings.model_dump()
            # Remove sensitive data
            if "integrations" in data["settings"]:
                data["settings"]["integrations"]["google_token"] = "***"
                data["settings"]["integrations"]["facebook_token"] = "***"
                data["settings"]["integrations"]["apple_token"] = "***"
        
        return data
    
    async def delete_account(
        self,
        user_id: str,
        request: DeleteAccountRequest
    ) -> bool:
        """
        Delete user account (GDPR right to erasure).
        
        Args:
            user_id: User ID
            request: Delete request
            
        Returns:
            True if successful
        """
        if request.confirmation != "DELETE":
            return False
        
        conn = await get_db_connection()
        try:
            # Delete settings
            await conn[self.SETTINGS_COLLECTION].delete_one({"user_id": user_id})
            
            # Invalidate cache
            cache = await get_cache()
            if cache:
                await cache.delete(f"{self.SETTINGS_CACHE_PREFIX}{user_id}")
            
            # Log deletion request for analytics
            await conn["account_deletions"].insert_one({
                "user_id": user_id,
                "reason": request.reason,
                "feedback": request.feedback,
                "exported": request.export_data,
                "deleted_at": datetime.utcnow()
            })
            
            return True
        finally:
            await conn.close()
    
    async def reset_to_defaults(self, user_id: str) -> UserSettings:
        """
        Reset user settings to defaults.
        
        Args:
            user_id: User ID
            
        Returns:
            Default settings
        """
        conn = await get_db_connection()
        try:
            # Delete current settings
            await conn[self.SETTINGS_COLLECTION].delete_one({"user_id": user_id})
            
            # Invalidate cache
            cache = await get_cache()
            if cache:
                await cache.delete(f"{self.SETTINGS_CACHE_PREFIX}{user_id}")
            
            # Create new default settings
            return await self.create_default_settings(user_id)
        finally:
            await conn.close()


# Singleton instance
settings_service = SettingsService()
