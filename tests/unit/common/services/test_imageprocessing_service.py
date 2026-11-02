""" Tests for ImageProcessingService. """
import pytest
from python.app.common.services.imageprocessing_service import ImageProcessingService

@pytest.mark.asyncio
async def test_perform():
    svc = ImageProcessingService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = ImageProcessingService()
    assert await svc.health() is True
