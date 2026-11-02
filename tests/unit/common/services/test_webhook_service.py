""" Tests for WebhookService. """
import pytest
from python.app.common.services.webhook_service import WebhookService

@pytest.mark.asyncio
async def test_perform():
    svc = WebhookService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = WebhookService()
    assert await svc.health() is True
