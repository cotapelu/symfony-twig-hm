from fastapi import FastAPI, HTTPException, Query
from typing import Dict, List, Optional
from datetime import datetime, timezone
from ..models.schemas import Advertisement, AdCampaign, TargetingCriteria
from service.ads_service import AdsService

app = FastAPI(title="Ads API", version="1.0.0")
service = AdsService()

@app.post("/api/ads")
async def create_ad(
    title: str,
    description: str,
    advertiser_id: str,
    ad_type: str,
    target_url: str,
    budget: float,
    daily_budget: float,
    start_date: datetime,
    end_date: datetime,
    targeting: Optional[Dict] = None
):
    # Input validation
    if budget <= 0:
        raise HTTPException(status_code=400, detail="Budget must be greater than 0")
    if daily_budget < 0:
        raise HTTPException(status_code=400, detail="Daily budget cannot be negative")
    if end_date <= start_date:
        raise HTTPException(status_code=400, detail="End date must be after start date")
    valid_ad_types = ["banner", "video", "native", "popup"]
    if ad_type not in valid_ad_types:
        raise HTTPException(status_code=400, detail=f"Invalid ad type. Must be one of {valid_ad_types}")
    # Basic URL format check for target_url
    if not target_url.startswith(("http://", "https://")):
        raise HTTPException(status_code=400, detail="Target URL must start with http:// or https://")
    
    ad = service.create_ad(
        title=title,
        description=description,
        advertiser_id=advertiser_id,
        ad_type=ad_type,
        target_url=target_url,
        budget=budget,
        daily_budget=daily_budget,
        start_date=start_date,
        end_date=end_date,
        targeting=targeting
    )
    return {
        "id": ad.id,
        "title": ad.title,
        "status": ad.status,
        "created_at": ad.created_at.isoformat()
    }

@app.get("/api/ads")
async def list_ads(
    advertiser_id: Optional[str] = None,
    status: Optional[str] = None
):
    ads = service.list_ads(advertiser_id, status)
    return {
        "count": len(ads),
        "ads": [
            {
                "id": ad.id,
                "title": ad.title,
                "ad_type": ad.ad_type,
                "status": ad.status,
                "budget": ad.budget,
                "start_date": ad.start_date.isoformat(),
                "end_date": ad.end_date.isoformat()
            }
            for ad in ads
        ]
    }

@app.get("/api/ads/{ad_id}")
async def get_ad(ad_id: str, advertiser_id: str):
    ad = service.get_ad(ad_id, advertiser_id)
    if not ad:
        raise HTTPException(status_code=404, detail="Ad not found or access denied")
    
    return {
        "id": ad.id,
        "title": ad.title,
        "description": ad.description,
        "ad_type": ad.ad_type,
        "target_url": ad.target_url,
        "status": ad.status,
        "budget": ad.budget,
        "daily_budget": ad.daily_budget,
        "start_date": ad.start_date.isoformat(),
        "end_date": ad.end_date.isoformat(),
        "targeting": ad.targeting
    }

@app.put("/api/ads/{ad_id}/status")
async def update_ad_status(ad_id: str, status: str, advertiser_id: str):
    # Check ownership
    ad = service.get_ad(ad_id, advertiser_id)
    if not ad:
        raise HTTPException(status_code=404, detail="Ad not found or access denied")
    
    updated_ad = service.update_ad_status(ad_id, status)
    if not updated_ad:
        raise HTTPException(status_code=404, detail="Ad not found")
    
    return {
        "id": updated_ad.id,
        "status": updated_ad.status,
        "updated_at": updated_ad.updated_at.isoformat()
    }

@app.post("/api/ads/{ad_id}/impression")
async def record_impression(
    ad_id: str,
    user_id: Optional[str] = None,
    session_id: Optional[str] = None,
    device_type: Optional[str] = None
):
    impression = service.record_impression(ad_id, user_id, session_id, device_type)
    return {"status": "impression recorded", "id": impression.id}

@app.post("/api/ads/{ad_id}/click")
async def record_click(
    ad_id: str,
    user_id: Optional[str] = None,
    session_id: Optional[str] = None,
    device_type: Optional[str] = None
):
    click = service.record_click(ad_id, user_id, session_id, device_type)
    return {"status": "click recorded", "id": click.id}

@app.get("/api/ads/{ad_id}/stats")
async def get_ad_stats(ad_id: str):
    stats = service.get_ad_stats(ad_id)
    return stats

@app.get("/api/ads/targeted/{user_id}")
async def get_targeted_ads(
    user_id: str,
    device_type: Optional[str] = None
):
    user_profile = {}
    ads = service.get_targeted_ads(user_id, user_profile, device_type)
    
    return {
        "count": len(ads),
        "ads": [
            {
                "id": ad.id,
                "title": ad.title,
                "description": ad.description,
                "ad_type": ad.ad_type,
                "target_url": ad.target_url,
                "image_url": ad.image_url
            }
            for ad in ads
        ]
    }

@app.post("/api/ads/campaigns")
async def create_campaign(
    name: str,
    advertiser_id: str,
    total_budget: float,
    start_date: datetime,
    end_date: datetime
):
    campaign = service.create_campaign(
        name=name,
        advertiser_id=advertiser_id,
        total_budget=total_budget,
        start_date=start_date,
        end_date=end_date
    )
    
    return {
        "id": campaign.id,
        "name": campaign.name,
        "status": campaign.status,
        "created_at": campaign.created_at.isoformat()
    }

@app.get("/api/ads/health")
async def health_check():
    return {"status": "healthy", "service": "ads"}
