"""
Settings Module - User and System Settings Management
Handles user preferences, app settings, and system configurations.
"""

from datetime import datetime
from typing import Optional, Dict, Any, List
from enum import Enum
from pydantic import BaseModel, Field


class NotificationChannel(str, Enum):
    """Notification delivery channels"""
    EMAIL = "email"
    PUSH = "push"
    SMS = "sms"
    IN_APP = "in_app"
    WHATSAPP = "whatsapp"


class NotificationType(str, Enum):
    """Types of notifications"""
    MARKETING = "marketing"
    TRANSACTIONAL = "transactional"
    SOCIAL = "social"
    SYSTEM = "system"
    REMINDERS = "reminders"
    NEWS = "news"


class ThemeMode(str, Enum):
    """App theme modes"""
    LIGHT = "light"
    DARK = "dark"
    SYSTEM = "system"
    AUTO = "auto"


class Language(str, Enum):
    """Supported languages"""
    VI = "vi"
    EN = "en"
    ZH = "zh"
    JA = "ja"
    KO = "ko"


class PrivacyLevel(str, Enum):
    """Privacy levels for profile visibility"""
    PUBLIC = "public"
    FRIENDS = "friends"
    PRIVATE = "private"
    CUSTOM = "custom"


class Timezone(str, Enum):
    """Supported timezones"""
    Asia_Ho_Chi_Minh = "Asia/Ho_Chi_Minh"
    Asia_Bangkok = "Asia/Bangkok"
    Asia_Singapore = "Asia/Singapore"
    Asia_Tokyo = "Asia/Tokyo"
    UTC = "UTC"


# User Settings Models
class UserSettings(BaseModel):
    """Complete user settings"""
    user_id: str
    profile: "ProfileSettings"
    notifications: "NotificationSettings"
    privacy: "PrivacySettings"
    appearance: "AppearanceSettings"
    accessibility: "AccessibilitySettings"
    security: "SecuritySettings"
    integrations: "IntegrationSettings"
    created_at: datetime
    updated_at: datetime


class ProfileSettings(BaseModel):
    """Profile-related settings"""
    display_name: str = ""
    bio: Optional[str] = None
    avatar_url: Optional[str] = None
    language: Language = Language.VI
    timezone: Timezone = Timezone.Asia_Ho_Chi_Minh
    date_format: str = "DD/MM/YYYY"
    time_format: str = "24h"
    show_online_status: bool = True
    allow_find_by_phone: bool = True
    allow_find_by_email: bool = True


class NotificationSettings(BaseModel):
    """Notification preferences"""
    enabled: bool = True
    quiet_hours_enabled: bool = False
    quiet_hours_start: Optional[str] = None  # "22:00"
    quiet_hours_end: Optional[str] = None   # "08:00"
    
    # Channel preferences
    email_enabled: bool = True
    push_enabled: bool = True
    sms_enabled: bool = False
    in_app_enabled: bool = True
    whatsapp_enabled: bool = False
    
    # Type preferences
    marketing_notifications: bool = True
    transactional_notifications: bool = True
    social_notifications: bool = True
    system_notifications: bool = True
    reminder_notifications: bool = True
    news_notifications: bool = False
    
    # Frequency (for email)
    email_digest: str = "daily"  # none, daily, weekly
    marketing_email_frequency: str = "weekly"


class PrivacySettings(BaseModel):
    """Privacy and visibility settings"""
    profile_visibility: PrivacyLevel = PrivacyLevel.FRIENDS
    show_activity_status: bool = True
    show_last_seen: bool = True
    show_read_receipts: bool = True
    allow_stranger_messages: bool = False
    allow_group_invites: bool = True
    show_online_to: List[str] = []  # User IDs who can see online status
    hide_from: List[str] = []  # User IDs to hide from
    
    # Data sharing
    share_usage_data: bool = True
    share_crash_reports: bool = True
    personalized_ads: bool = True
    
    # Blocking
    blocked_users: List[str] = []
    muted_users: List[str] = []


class AppearanceSettings(BaseModel):
    """App appearance settings"""
    theme: ThemeMode = ThemeMode.SYSTEM
    accent_color: str = "#3B82F6"
    font_size: str = "medium"  # small, medium, large
    compact_mode: bool = False
    sidebar_collapsed: bool = False
    animations_enabled: bool = True
    high_contrast: bool = False


class AccessibilitySettings(BaseModel):
    """Accessibility options"""
    screen_reader: bool = False
    reduced_motion: bool = False
    color_blind_mode: str = "none"  # none, protanopia, deuteranopia, tritanopia
    font_size_multiplier: float = 1.0
    keyboard_navigation: bool = True
    focus_indicators: bool = True
    autoplay_media: bool = True
    captions_enabled: bool = False
    captions_size: str = "medium"


class SecuritySettings(BaseModel):
    """Security and authentication settings"""
    two_factor_enabled: bool = False
    two_factor_method: Optional[str] = None  # app, sms, email
    login_alerts: bool = True
    login_alert_email: bool = True
    login_alert_push: bool = True
    session_timeout: int = 3600  # seconds
    max_sessions: int = 5
    biometric_enabled: bool = False
    password_last_changed: Optional[datetime] = None


class IntegrationSettings(BaseModel):
    """Third-party integration settings"""
    google_connected: bool = False
    google_token: Optional[str] = None
    facebook_connected: bool = False
    facebook_token: Optional[str] = None
    apple_connected: bool = False
    apple_token: Optional[str] = None
    connected_apps: List[dict] = []


# API Request/Response Models
class SettingsUpdateRequest(BaseModel):
    """Generic settings update request"""
    settings: Dict[str, Any]
    section: Optional[str] = None  # If specified, only update this section


class NotificationPreferencesUpdate(BaseModel):
    """Notification preferences update"""
    enabled: Optional[bool] = None
    email_enabled: Optional[bool] = None
    push_enabled: Optional[bool] = None
    sms_enabled: Optional[bool] = None
    in_app_enabled: Optional[bool] = None
    quiet_hours_enabled: Optional[bool] = None
    quiet_hours_start: Optional[str] = None
    quiet_hours_end: Optional[str] = None


class PrivacyUpdateRequest(BaseModel):
    """Privacy settings update"""
    profile_visibility: Optional[PrivacyLevel] = None
    show_activity_status: Optional[bool] = None
    show_last_seen: Optional[bool] = None
    show_read_receipts: Optional[bool] = None
    allow_stranger_messages: Optional[bool] = None
    allow_group_invites: Optional[bool] = None


class AppearanceUpdateRequest(BaseModel):
    """Appearance settings update"""
    theme: Optional[ThemeMode] = None
    accent_color: Optional[str] = None
    font_size: Optional[str] = None
    compact_mode: Optional[bool] = None
    sidebar_collapsed: Optional[bool] = None
    animations_enabled: Optional[bool] = None


class SecurityUpdateRequest(BaseModel):
    """Security settings update"""
    two_factor_enabled: Optional[bool] = None
    two_factor_method: Optional[str] = None
    login_alerts: Optional[bool] = None
    session_timeout: Optional[int] = None
    max_sessions: Optional[int] = None
    biometric_enabled: Optional[bool] = None


class BlockUserRequest(BaseModel):
    """Block or unblock user request"""
    user_id: str
    action: str = "block"  # block, unblock, mute, unmute


class ExportDataRequest(BaseModel):
    """Export user data request"""
    include_settings: bool = True
    include_history: bool = True
    include_notifications: bool = True
    format: str = "json"  # json, csv


class DeleteAccountRequest(BaseModel):
    """Delete account request"""
    confirmation: str = "DELETE"
    reason: Optional[str] = None
    feedback: Optional[str] = None
    export_data: bool = False
