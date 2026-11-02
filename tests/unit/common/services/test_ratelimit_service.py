""" Tests for RateLimitService. """
import pytest
from python.app.common.services.ratelimit_service import RateLimitService

@pytest.mark.asyncio
async def test_perform():
    svc = RateLimitService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = RateLimitService()
    assert await svc.health() is True
