import pytest
import os
import tempfile
from datetime import datetime, timezone, timedelta
from modules.Ads.service.ads_service import AdsService

@pytest.fixture
def temp_storage():
    with tempfile.NamedTemporaryFile(mode='w', suffix='.json', delete=False) as f:
        yield f.name
    os.unlink(f.name)

@pytest.fixture
def ads_service(temp_storage):
    return AdsService(ets_file=temp_storage)

def test_init(ads_service):
    assert ads_service is not None
    assert ads_service.ads == {}
    assert ads_service.campaigns == {}

def test_create_ad(ads_service):
    ad = ads_service.create_ad(
        title="Test Ad",
        description="Test description",
        advertiser_id="advertiser123",
        ad_type="banner",
        target_url="https://example.com",
        budget=1000.0,
        daily_budget=100.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    assert ad is not None
    assert ad.title == "Test Ad"
    assert ad.advertiser_id == "advertiser123"
    assert ad.id in ads_service.ads

def test_get_ad(ads_service):
    ad = ads_service.create_ad(
        title="Test Ad",
        description="Test description",
        advertiser_id="advertiser123",
        ad_type="banner",
        target_url="https://example.com",
        budget=1000.0,
        daily_budget=100.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    retrieved = ads_service.get_ad(ad.id)
    assert retrieved is not None
    assert retrieved.id == ad.id

def test_get_ad_not_found(ads_service):
    result = ads_service.get_ad("nonexistent")
    assert result is None

def test_get_ad_unauthorized(ads_service):
    ad = ads_service.create_ad(
        title="Test Ad",
        description="Test description",
        advertiser_id="advertiser123",
        ad_type="banner",
        target_url="https://example.com",
        budget=1000.0,
        daily_budget=100.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    result = ads_service.get_ad(ad.id, advertiser_id="other_user")
    assert result is None

def test_list_ads(ads_service):
    for i in range(3):
        ads_service.create_ad(
            title=f"Ad {i}",
            description="Test",
            advertiser_id="advertiser123",
            ad_type="banner",
            target_url="https://example.com",
            budget=1000.0,
            daily_budget=100.0,
            start_date=datetime.now(timezone.utc),
            end_date=datetime.now(timezone.utc) + timedelta(days=30)
        )
    
    ads = ads_service.list_ads()
    assert len(ads) == 3

def test_update_ad_status(ads_service):
    ad = ads_service.create_ad(
        title="Test Ad",
        description="Test description",
        advertiser_id="advertiser123",
        ad_type="banner",
        target_url="https://example.com",
        budget=1000.0,
        daily_budget=100.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    updated = ads_service.update_ad_status(ad.id, "paused")
    assert updated.status == "paused"

def test_create_campaign(ads_service):
    campaign = ads_service.create_campaign(
        name="Test Campaign",
        advertiser_id="advertiser123",
        total_budget=5000.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    assert campaign is not None
    assert campaign.name == "Test Campaign"

def test_record_impression(ads_service):
    ad = ads_service.create_ad(
        title="Test Ad",
        description="Test description",
        advertiser_id="advertiser123",
        ad_type="banner",
        target_url="https://example.com",
        budget=1000.0,
        daily_budget=100.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    impression = ads_service.record_impression(ad.id, user_id="user123", device_type="mobile")
    assert impression is not None
    assert impression.ad_id == ad.id

def test_record_click(ads_service):
    ad = ads_service.create_ad(
        title="Test Ad",
        description="Test description",
        advertiser_id="advertiser123",
        ad_type="banner",
        target_url="https://example.com",
        budget=1000.0,
        daily_budget=100.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    click = ads_service.record_click(ad.id, user_id="user123")
    assert click is not None
    assert click.ad_id == ad.id

def test_get_ad_stats(ads_service):
    ad = ads_service.create_ad(
        title="Test Ad",
        description="Test description",
        advertiser_id="advertiser123",
        ad_type="banner",
        target_url="https://example.com",
        budget=1000.0,
        daily_budget=100.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    ads_service.record_impression(ad.id)
    ads_service.record_click(ad.id)
    
    stats = ads_service.get_ad_stats(ad.id)
    assert stats["impressions"] == 1
    assert stats["clicks"] == 1
    assert stats["ctr"] > 0

def test_get_targeted_ads(ads_service):
    ads_service.create_ad(
        title="Test Ad",
        description="Test description",
        advertiser_id="advertiser123",
        ad_type="banner",
        target_url="https://example.com",
        budget=1000.0,
        daily_budget=100.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    ads = ads_service.get_targeted_ads("user123", {"interests": ["tech"]})
    assert len(ads) > 0

def test_list_campaigns(ads_service):
    for i in range(2):
        ads_service.create_campaign(
            name=f"Campaign {i}",
            advertiser_id="advertiser123",
            total_budget=1000.0,
            start_date=datetime.now(timezone.utc),
            end_date=datetime.now(timezone.utc) + timedelta(days=30)
        )
    
    campaigns = ads_service.list_campaigns()
    assert len(campaigns) == 2

def test_log_impression(ads_service):
    campaign = ads_service.create_campaign(
        name="Test Campaign",
        advertiser_id="advertiser123",
        total_budget=1000.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    result = ads_service.log_impression(campaign.id, user_id="user123", placement="header")
    assert result is True

def test_log_click(ads_service):
    campaign = ads_service.create_campaign(
        name="Test Campaign",
        advertiser_id="advertiser123",
        total_budget=1000.0,
        start_date=datetime.now(timezone.utc),
        end_date=datetime.now(timezone.utc) + timedelta(days=30)
    )
    
    result = ads_service.log_click(campaign.id, user_id="user123")
    assert result is True