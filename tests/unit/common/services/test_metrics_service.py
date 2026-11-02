""" Tests for MetricsService. """
import pytest
from python.app.common.services.metrics_service import MetricsService

@pytest.mark.asyncio
async def test_perform():
    svc = MetricsService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = MetricsService()
    assert await svc.health() is True
