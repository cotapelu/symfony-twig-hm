""" Tests for PushNotificationService. """
import pytest
from python.app.common.services.pushnotification_service import PushNotificationService

@pytest.mark.asyncio
async def test_perform():
    svc = PushNotificationService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = PushNotificationService()
    assert await svc.health() is True
