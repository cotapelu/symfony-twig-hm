""" Tests for PDFGenerationService. """
import pytest
from python.app.common.services.pdfgeneration_service import PDFGenerationService

@pytest.mark.asyncio
async def test_perform():
    svc = PDFGenerationService()
    result = await svc.perform("test")
    assert result == f"{svc.__class__.__name__}: test"

@pytest.mark.asyncio
async def test_health():
    svc = PDFGenerationService()
    assert await svc.health() is True
