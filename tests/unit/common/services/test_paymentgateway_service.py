""" Tests for PaymentGatewayService. """
import pytest
from python.app.common.services.paymentgateway_service import PaymentGatewayService

@pytest.mark.asyncio
async def test_perform():
    svc = PaymentGatewayService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = PaymentGatewayService()
    assert await svc.health() is True
