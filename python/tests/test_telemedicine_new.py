"""Tests for Telemedicine Service (New)"""
import pytest


def test_service_import():
    """Test that TelemedicineManager can be imported"""
    from modules.Telemedicine.telemedicine_manager import TelemedicineManager
    assert TelemedicineManager is not None


def test_service_alias():
    """Test that TelemedicineManager is an alias for TelemedicineService"""
    from modules.Telemedicine.telemedicine_manager import TelemedicineManager
    from modules.Telemedicine.service.service import TelemedicineService
    assert TelemedicineManager == TelemedicineService


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
