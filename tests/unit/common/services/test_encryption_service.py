""" Tests for EncryptionService. """
import pytest
from python.app.common.services.encryption_service import EncryptionService

@pytest.mark.asyncio
async def test_perform():
    svc = EncryptionService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = EncryptionService()
    assert await svc.health() is True
