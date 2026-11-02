""" Tests for CacheService. """
import pytest
from python.app.common.services.cache_service import CacheService

@pytest.mark.asyncio
async def test_perform():
    svc = CacheService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = CacheService()
    assert await svc.health() is True
