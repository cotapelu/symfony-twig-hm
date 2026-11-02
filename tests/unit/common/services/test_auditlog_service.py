""" Tests for AuditLogService. """
import pytest
from python.app.common.services.auditlog_service import AuditLogService

@pytest.mark.asyncio
async def test_perform():
    svc = AuditLogService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = AuditLogService()
    assert await svc.health() is True
