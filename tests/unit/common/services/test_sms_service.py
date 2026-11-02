""" Tests for SMSService. """
import pytest
from python.app.common.services.sms_service import SMSService

@pytest.mark.asyncio
async def test_perform():
    svc = SMSService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = SMSService()
    assert await svc.health() is True
