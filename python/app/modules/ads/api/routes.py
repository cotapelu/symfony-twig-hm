from fastapi import APIRouter, HTTPException, Depends
from typing import Dict, List, Optional
from datetime import datetime
from pydantic import BaseModel

from ..services.ad_service import AdServingService, AdType, AdStatus
from app.shared.middleware.auth import get_current_user_id

router = APIRouter(prefix="/api/ads", tags=["advertising"])

# Initialize service
ad_service = AdServingService()

class CampaignCreateRequest(BaseModel):
    name: str
    advertiser_id: str
    ad_type: str
    budget: float
    daily_budget: Optional[float] = None
    start_date: str
    end_date: str
    targeting: Optional[Dict] = {}
    creatives: Optional[List[Dict]] = []

class CampaignUpdateRequest(BaseModel):
    name: Optional[str] = None
    status: Optional[str] = None
    budget: Optional[float] = None
    daily_budget: Optional[float] = None

class AdServeRequest(BaseModel):
    placement: str
    user_id: str
    context: Optional[Dict] = None

class AdClickRequest(BaseModel):
    impression_id: str
    user_id: str

@router.post("/campaigns")
async def create_campaign(
    campaign: CampaignCreateRequest,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict:
    """Create a new ad campaign (IDOR protected)"""
    # Ensure the advertiser_id in the request matches the authenticated user
    if campaign.advertiser_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot create campaign for another advertiser")
    try:
        result = ad_service.create_campaign(campaign.dict())
        return {
            "campaign_id": result.id,
            "name": result.name,
            "status": result.status.value,
            "created_at": result.created_at.isoformat()
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/campaigns/{campaign_id}")
async def get_campaign(
    campaign_id: str,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict:
    """Get campaign details (IDOR protected)"""
    campaign = ad_service.get_campaign(campaign_id)
    if not campaign:
        raise HTTPException(status_code=404, detail="Campaign not found")
    
    # Check ownership: only advertiser or admin can view
    if campaign.advertiser_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other advertisers' campaigns")
    
    return {
        "id": campaign.id,
        "name": campaign.name,
        "advertiser_id": campaign.advertiser_id,
        "ad_type": campaign.ad_type.value,
        "status": campaign.status.value,
        "budget": campaign.budget,
        "daily_budget": campaign.daily_budget,
        "spent": campaign.spent,
        "start_date": campaign.start_date.isoformat(),
        "end_date": campaign.end_date.isoformat(),
        "targeting": campaign.targeting,
        "creatives": campaign.creatives
    }

@router.put("/campaigns/{campaign_id}")
async def update_campaign(
    campaign_id: str,
    updates: CampaignUpdateRequest,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict:
    """Update campaign (IDOR protected)"""
    campaign = ad_service.get_campaign(campaign_id)
    if not campaign:
        raise HTTPException(status_code=404, detail="Campaign not found")
    # Check ownership: only advertiser can update
    if campaign.advertiser_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot update other advertisers' campaigns")
    
    campaign = ad_service.update_campaign(campaign_id, updates.dict(exclude_unset=True))
    return {
        "id": campaign.id,
        "name": campaign.name,
        "status": campaign.status.value,
        "updated": True
    }

@router.post("/serve")
async def serve_ad(
    request: AdServeRequest,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict[str, Any]:
    """Serve an ad (IDOR protected)"""
    if request.user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot serve ads for another user")
    try:
        ad = ad_service.serve_ad(
            placement=request.placement,
            user_id=request.user_id,
            context=request.context
        )
        
        if not ad:
            return {"ad": None, "message": "No ad available"}
        
        return {"ad": ad}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/click")
async def record_click(
    request: AdClickRequest,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict[str, Any]:
    """Record ad click (IDOR protected)"""
    if request.user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot record click for another user")
    try:
        click = ad_service.record_click(
            impression_id=request.impression_id,
            user_id=request.user_id
        )
        
        if not click:
            raise HTTPException(status_code=404, detail="Impression not found")
        
        return {
            "click_id": click.id,
            "campaign_id": click.campaign_id,
            "timestamp": click.timestamp.isoformat()
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/campaigns/{campaign_id}/stats")
async def get_campaign_stats(
    campaign_id: str,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict:
    """Get campaign statistics (IDOR protected)"""
    try:
        campaign = ad_service.get_campaign(campaign_id)
        if not campaign:
            raise HTTPException(status_code=404, detail="Campaign not found")
        # Check ownership
        if campaign.advertiser_id != current_user_id:
            raise HTTPException(status_code=403, detail="Cannot access other advertisers' stats")
        
        stats = ad_service.get_campaign_stats(campaign_id)
        return stats
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/advertisers/{advertiser_id}/stats")
async def get_advertiser_stats(
    advertiser_id: str,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict:
    """Get advertiser statistics (IDOR protected)"""
    if advertiser_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other advertisers' stats")
    try:
        stats = ad_service.get_advertiser_stats(advertiser_id)
        return stats
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/ad-types")
async def get_ad_types():
    """Get list of supported ad types"""
    return {
        "ad_types": [
            {
                "type": at.value,
                "description": {
                    AdType.BANNER: "Standard banner ad",
                    AdType.INTERSTITIAL: "Full-screen interstitial",
                    AdType.NATIVE: "Native in-feed ad",
                    AdType.VIDEO: "Video advertisement",
                    AdType.REWARDED: "Rewarded video ad"
                }.get(at, "Unknown")
            }
            for at in AdType
        ]
    }

@router.get("/statuses")
async def get_campaign_statuses():
    """Get list of campaign statuses"""
    return {
        "statuses": [
            {
                "status": s.value,
                "description": {
                    AdStatus.DRAFT: "Campaign draft",
                    AdStatus.PENDING: "Pending approval",
                    AdStatus.ACTIVE: "Active and serving",
                    AdStatus.PAUSED: "Paused by user",
                    AdStatus.COMPLETED: "Campaign ended",
                    AdStatus.REJECTED: "Rejected"
                }.get(s, "Unknown")
            }
            for s in AdStatus
        ]
    }
