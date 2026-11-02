""" Tests for FileStorageService. """
import pytest
from python.app.common.services.filestorage_service import FileStorageService

@pytest.mark.asyncio
async def test_perform():
    svc = FileStorageService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = FileStorageService()
    assert await svc.health() is True
