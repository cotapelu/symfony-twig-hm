from pydantic import BaseModel
from typing import List, Dict, Optional, Any, Literal
from datetime import datetime, timezone

class Advertisement(BaseModel):
    id: str
    title: str
    description: str
    advertiser_id: str
    ad_type: Literal["banner", "video", "native", "popup"]
    target_url: str
    image_url: Optional[str] = None
    video_url: Optional[str] = None
    budget: float
    daily_budget: float
    cost_per_click: float
    cost_per_impression: float
    start_date: datetime
    end_date: datetime
    status: Literal["draft", "active", "paused", "completed", "rejected"] = "draft"
    targeting: Dict[str, Any] = {}
    created_at: datetime = datetime.now(timezone.utc)
    updated_at: datetime = datetime.now(timezone.utc)

class AdCampaign(BaseModel):
    id: str
    name: str
    advertiser_id: str
    ads: List[str] = []
    total_budget: float
    spent_budget: float = 0.0
    start_date: datetime
    end_date: datetime
    status: Literal["active", "paused", "completed"] = "active"
    created_at: datetime = datetime.now(timezone.utc)

class AdImpression(BaseModel):
    id: str
    ad_id: str
    user_id: Optional[str] = None
    session_id: Optional[str] = None
    device_type: Optional[str] = None
    location: Optional[str] = None
    timestamp: datetime = datetime.now(timezone.utc)

class AdClick(BaseModel):
    id: str
    ad_id: str
    user_id: Optional[str] = None
    session_id: Optional[str] = None
    device_type: Optional[str] = None
    location: Optional[str] = None
    timestamp: datetime = datetime.now(timezone.utc)
    conversion: bool = False

class AdStats(BaseModel):
    ad_id: str
    impressions: int = 0
    clicks: int = 0
    conversions: int = 0
    spend: float = 0.0
    ctr: float = 0.0
    cpc: float = 0.0
    date: datetime

class TargetingCriteria(BaseModel):
    age_range: Optional[Dict[str, int]] = None
    gender: Optional[List[str]] = None
    locations: Optional[List[str]] = None
    interests: Optional[List[str]] = None
    device_types: Optional[List[str]] = None
    time_of_day: Optional[Dict[str, str]] = None
