""" Tests for FeatureFlagService. """
import pytest
from python.app.common.services.featureflag_service import FeatureFlagService

@pytest.mark.asyncio
async def test_perform():
    svc = FeatureFlagService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = FeatureFlagService()
    assert await svc.health() is True
