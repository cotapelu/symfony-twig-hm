"""
History Module - User History Tracking
Stores and manages user activity history across the platform.
"""

from datetime import datetime
from typing import Optional, List
from enum import Enum
from pydantic import BaseModel, Field


class ActivityType(str, Enum):
    """Types of user activities tracked in history"""
    LOGIN = "login"
    LOGOUT = "logout"
    PAGE_VIEW = "page_view"
    SEARCH = "search"
    MESSAGE_SENT = "message_sent"
    MESSAGE_RECEIVED = "message_received"
    FILE_UPLOAD = "file_upload"
    FILE_DOWNLOAD = "file_download"
    PAYMENT = "payment"
    PURCHASE = "purchase"
    BOOKING = "booking"
    APPOINTMENT = "appointment"
    REVIEW = "review"
    RATING = "rating"
    COMMENT = "comment"
    SHARE = "share"
    FOLLOW = "follow"
    UNFOLLOW = "unfollow"
    LIKE = "like"
    UNLIKE = "unlike"
    PROFILE_UPDATE = "profile_update"
    SETTINGS_CHANGE = "settings_change"
    DEVICE_CHANGE = "device_change"
    PASSWORD_CHANGE = "password_change"
    TWO_FACTOR = "two_factor"
    API_CALL = "api_call"


class DeviceType(str, Enum):
    """Types of devices used"""
    WEB = "web"
    IOS = "ios"
    ANDROID = "android"
    TABLET = "tablet"
    OTHER = "other"


# Pydantic Models for API
class ActivityBase(BaseModel):
    """Base activity model"""
    activity_type: ActivityType
    description: str
    metadata: Optional[dict] = None


class ActivityCreate(ActivityBase):
    """Activity creation request"""
    user_id: str
    session_id: Optional[str] = None
    device_type: Optional[DeviceType] = None
    ip_address: Optional[str] = None
    user_agent: Optional[str] = None
    location: Optional[str] = None
    timestamp: Optional[datetime] = None


class ActivityResponse(ActivityBase):
    """Activity response model"""
    id: str
    user_id: str
    session_id: Optional[str]
    device_type: Optional[DeviceType]
    ip_address: Optional[str]
    user_agent: Optional[str]
    location: Optional[str]
    timestamp: datetime
    created_at: datetime

    class Config:
        from_attributes = True


class HistoryEntry(BaseModel):
    """User history entry"""
    id: str
    user_id: str
    activity_type: ActivityType
    category: str
    title: str
    description: str
    resource_type: Optional[str] = None
    resource_id: Optional[str] = None
    metadata: Optional[dict] = None
    timestamp: datetime
    duration: Optional[int] = None  # Duration in seconds
    location: Optional[str] = None
    device_type: Optional[DeviceType] = None


class HistorySearchRequest(BaseModel):
    """Search request for history"""
    user_id: str
    activity_types: Optional[List[ActivityType]] = None
    start_date: Optional[datetime] = None
    end_date: Optional[datetime] = None
    resource_type: Optional[str] = None
    resource_id: Optional[str] = None
    limit: int = 50
    offset: int = 0


class HistoryStats(BaseModel):
    """History statistics"""
    user_id: str
    total_activities: int
    activities_by_type: dict
    most_active_day: Optional[datetime] = None
    average_session_duration: float
    top_locations: List[dict]
    recent_activity_types: List[dict]


class SessionSummary(BaseModel):
    """User session summary"""
    session_id: str
    user_id: str
    start_time: datetime
    end_time: Optional[datetime] = None
    duration: int  # Duration in seconds
    device_type: DeviceType
    ip_address: str
    location: Optional[str] = None
    activities_count: int
    pages_visited: List[str]


class PageVisit(BaseModel):
    """Page visit record"""
    path: str
    title: Optional[str] = None
    referrer: Optional[str] = None
    duration: int  # Time spent on page in seconds
    timestamp: datetime
