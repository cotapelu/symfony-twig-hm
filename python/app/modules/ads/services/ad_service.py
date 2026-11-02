"""
Advertising Service for Ad Campaign Management
Handles ad serving, targeting, and campaign analytics
"""
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Tuple
from dataclasses import dataclass
from enum import Enum
import random
import uuid

class AdType(str, Enum):
    BANNER = "banner"
    INTERSTITIAL = "interstitial"
    NATIVE = "native"
    VIDEO = "video"
    REWARDED = "rewarded"

class AdStatus(str, Enum):
    DRAFT = "draft"
    PENDING = "pending"
    ACTIVE = "active"
    PAUSED = "paused"
    COMPLETED = "completed"
    REJECTED = "rejected"

@dataclass
class AdCampaign:
    id: str
    name: str
    advertiser_id: str
    ad_type: AdType
    status: AdStatus
    budget: float
    daily_budget: float
    spent: float
    start_date: datetime
    end_date: datetime
    targeting: Dict
    creatives: List[Dict]
    created_at: datetime

@dataclass
class AdImpression:
    id: str
    campaign_id: str
    user_id: str
    placement: str
    timestamp: datetime
    device_type: str
    location: Optional[Dict] = None

@dataclass
class AdClick:
    id: str
    impression_id: str
    campaign_id: str
    user_id: str
    timestamp: datetime
    conversion_value: Optional[float] = None

class AdServingService:
    """Service for serving and managing ads"""

    def __init__(self, db_connection=None):
        self.db = db_connection
        self.campaigns: Dict[str, AdCampaign] = {}
        self.impressions: List[AdImpression] = []
        self.clicks: List[AdClick] = []
        self._impression_to_campaign: Dict[str, str] = {}  # impression_id -> campaign_id

    def create_campaign(self, campaign_data: Dict) -> AdCampaign:
        """Create a new ad campaign"""
        campaign_id = f"CAMPAIGN_{uuid.uuid4().hex[:8].upper()}"
        campaign = AdCampaign(
            id=campaign_id,
            name=campaign_data["name"],
            advertiser_id=campaign_data["advertiser_id"],
            ad_type=AdType(campaign_data["ad_type"]),
            status=AdStatus.PENDING,
            budget=campaign_data["budget"],
            daily_budget=campaign_data.get("daily_budget", campaign_data["budget"] / 30),
            spent=0.0,
            start_date=datetime.fromisoformat(campaign_data["start_date"]),
            end_date=datetime.fromisoformat(campaign_data["end_date"]),
            targeting=campaign_data.get("targeting", {}),
            creatives=campaign_data.get("creatives", []),
            created_at=datetime.now()
        )
        self.campaigns[campaign_id] = campaign
        return campaign

    def get_campaign(self, campaign_id: str) -> Optional[AdCampaign]:
        """Get campaign by ID"""
        return self.campaigns.get(campaign_id)

    def update_campaign(self, campaign_id: str, updates: Dict) -> Optional[AdCampaign]:
        """Update campaign fields"""
        campaign = self.campaigns.get(campaign_id)
        if not campaign:
            return None
        if "name" in updates:
            campaign.name = updates["name"]
        if "status" in updates:
            campaign.status = AdStatus(updates["status"])
        if "budget" in updates:
            campaign.budget = updates["budget"]
        if "daily_budget" in updates:
            campaign.daily_budget = updates["daily_budget"]
        return campaign

    def list_campaigns(self, advertiser_id: Optional[str] = None) -> List[AdCampaign]:
        """List campaigns, optionally filtered by advertiser"""
        if advertiser_id:
            return [c for c in self.campaigns.values() if c.advertiser_id == advertiser_id]
        return list(self.campaigns.values())

    def pause_campaign(self, campaign_id: str) -> None:
        """Pause a campaign (set to PAUSED) if not completed"""
        campaign = self.campaigns.get(campaign_id)
        if campaign and campaign.status != AdStatus.COMPLETED:
            campaign.status = AdStatus.PAUSED

    def resume_campaign(self, campaign_id: str) -> None:
        """Resume a paused campaign (set to ACTIVE)"""
        campaign = self.campaigns.get(campaign_id)
        if campaign and campaign.status == AdStatus.PAUSED:
            campaign.status = AdStatus.ACTIVE

    def delete_campaign(self, campaign_id: str) -> bool:
        """Delete a campaign and its related data"""
        if campaign_id not in self.campaigns:
            return False
        del self.campaigns[campaign_id]
        self.impressions = [i for i in self.impressions if i.campaign_id != campaign_id]
        self.clicks = [c for c in self.clicks if c.campaign_id != campaign_id]
        self._impression_to_campaign = {k: v for k, v in self._impression_to_campaign.items() if v != campaign_id}
        return True

    def serve_ad(self, user_id: str, placement: str, **kwargs) -> Optional[Dict]:
        """Serve an ad based on targeting and availability"""
        user_location = self._extract_targeting_context(kwargs)
        eligible = self._gather_eligible_campaigns(user_location)
        if not eligible:
            return None
        campaign = random.choice(eligible)
        impression_id = self._build_impression_id()
        self._impression_to_campaign[impression_id] = campaign.id
        creative = campaign.creatives[0] if campaign.creatives else self._get_default_creative()
        return {
            "impression_id": impression_id,
            "campaign_id": campaign.id,
            "ad_type": campaign.ad_type.value,
            "creative": creative,
            "target_url": f"/ads/click?imp={impression_id}",
            "tracking_pixel": f"/ads/track?imp={impression_id}",
            "placement": placement,
        }

    def _extract_targeting_context(self, kwargs: Dict) -> Optional[Dict]:
        user_location = kwargs.get('user_location')
        context = kwargs.get('context') or {}
        if user_location is None:
            user_location = context.get('location')
        return user_location

    def _gather_eligible_campaigns(self, user_location: Optional[Dict]) -> List[AdCampaign]:
        now = datetime.now()
        candidates = [
            c for c in self.campaigns.values()
            if c.status == AdStatus.ACTIVE
            and c.start_date <= now <= c.end_date
            and c.spent < c.budget
        ]
        eligible = self._filter_by_targeting(candidates, user_location)
        eligible = self._filter_by_daily_budget(eligible)
        return eligible

    def _filter_by_targeting(self, candidates: List[AdCampaign], user_location: Optional[Dict]) -> List[AdCampaign]:
        eligible = []
        for c in candidates:
            if self._matches_targeting(c, user_location):
                eligible.append(c)
        return eligible

    def _filter_by_daily_budget(self, candidates: List[AdCampaign]) -> List[AdCampaign]:
        return [c for c in candidates if self._get_daily_spent(c.id) < c.daily_budget]

    def _build_impression_id(self) -> str:
        return f"IMP_{uuid.uuid4().hex[:8].upper()}"

    def _get_default_creative(self) -> Dict:
        return {
            "title": "Sample Ad",
            "description": "Sample description",
            "image_url": "https://example.com/ad.jpg",
            "cta_text": "Learn More"
        }

    def _matches_targeting(self, campaign: AdCampaign, user_location: Optional[Dict]) -> bool:
        """Check if campaign's targeting matches user attributes"""
        targeting = campaign.targeting or {}
        # Geographic: location countries
        loc = targeting.get("location", {})
        countries = loc.get("countries")
        if countries:
            if not user_location or "country" not in user_location:
                return False
            if user_location["country"] not in countries:
                return False
        # Extend with more targeting types as needed
        return True

    def record_impression(self, impression_id: str, campaign_id: str, user_id: str, placement: str) -> None:
        """Explicitly record an impression and account for cost"""
        # Avoid double-counting
        if any(i.id == impression_id for i in self.impressions):
            return
        impression = AdImpression(
            id=impression_id,
            campaign_id=campaign_id,
            user_id=user_id,
            placement=placement,
            timestamp=datetime.now(),
            device_type="unknown"
        )
        self.impressions.append(impression)
        campaign = self.campaigns.get(campaign_id)
        if campaign:
            campaign.spent += 0.1

    def record_click(self, impression_id: str, user_id: str, conversion_value: Optional[float] = None) -> Optional[AdClick]:
        """Record a click and associated spending"""
        campaign_id = self._get_campaign_id_with_impression(impression_id, user_id)
        if campaign_id is None:
            return None
        click_cost = 1.0 if conversion_value is None else conversion_value
        campaign = self.campaigns.get(campaign_id)
        if campaign:
            campaign.spent += click_cost
        click = AdClick(
            id=f"CLICK_{uuid.uuid4().hex[:8].upper()}",
            impression_id=impression_id,
            campaign_id=campaign_id,
            user_id=user_id,
            timestamp=datetime.now(),
            conversion_value=conversion_value,
        )
        self.clicks.append(click)
        return click

    def _get_campaign_id_with_impression(self, impression_id: str, user_id: str) -> Optional[str]:
        campaign_id = self._impression_to_campaign.get(impression_id)
        if campaign_id is None:
            imp = next((i for i in self.impressions if i.id == impression_id), None)
            if imp:
                campaign_id = imp.campaign_id
        if campaign_id is None:
            return None
        if not any(i.id == impression_id for i in self.impressions):
            self._create_impression(impression_id, campaign_id, user_id)
        return campaign_id

    def _create_impression(self, impression_id: str, campaign_id: str, user_id: str) -> None:
        impression = AdImpression(
            id=impression_id,
            campaign_id=campaign_id,
            user_id=user_id,
            placement="unknown",
            timestamp=datetime.now(),
            device_type="unknown",
        )
        self.impressions.append(impression)
        campaign = self.campaigns.get(campaign_id)
        if campaign:
            campaign.spent += 0.1

    def get_campaign_stats(self, campaign_id: str) -> Dict:
        campaign, impressions, clicks = self._get_campaign_data(campaign_id)
        if not campaign:
            return {}
        ctr = (len(clicks) / len(impressions) * 100) if impressions else 0
        return {
            "campaign_id": campaign_id,
            "status": campaign.status.value,
            "budget": campaign.budget,
            "spent": campaign.spent,
            "remaining_budget": campaign.budget - campaign.spent,
            "impressions": len(impressions),
            "clicks": len(clicks),
            "ctr": round(ctr, 2),
            "cpc": 1000,
            "start_date": campaign.start_date.isoformat(),
            "end_date": campaign.end_date.isoformat(),
        }

    def _get_campaign_data(self, campaign_id: str) -> Tuple[Optional[AdCampaign], List[AdImpression], List[AdClick]]:
        campaign = self.campaigns.get(campaign_id)
        if not campaign:
            return None, [], []
        impressions = [i for i in self.impressions if i.campaign_id == campaign_id]
        clicks = [c for c in self.clicks if c.campaign_id == campaign_id]
        return campaign, impressions, clicks

    def get_advertiser_stats(self, advertiser_id: str) -> Dict:
        """Get stats for all campaigns of an advertiser"""
        adv_campaigns = [c for c in self.campaigns.values() if c.advertiser_id == advertiser_id]
        total_budget = sum(c.budget for c in adv_campaigns)
        total_spent = sum(c.spent for c in adv_campaigns)
        return {
            "advertiser_id": advertiser_id,
            "total_campaigns": len(adv_campaigns),
            "active_campaigns": len([c for c in adv_campaigns if c.status == AdStatus.ACTIVE]),
            "total_budget": total_budget,
            "total_spent": total_spent,
            "remaining_budget": total_budget - total_spent,
            "campaigns": [self.get_campaign_stats(c.id) for c in adv_campaigns]
        }

    def _get_daily_spent(self, campaign_id: str) -> float:
        """Get amount spent today for a campaign"""
        today = datetime.now().date()
        daily_impr = [i for i in self.impressions if i.campaign_id == campaign_id and i.timestamp.date() == today]
        # Cost per impression aligns with record_impression (0.1 VND per impression)
        return len(daily_impr) * 0.1
