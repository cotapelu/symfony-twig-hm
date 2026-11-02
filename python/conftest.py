"""Pytest conftest to ensure module imports work correctly."""
import sys
from pathlib import Path

# Ensure the python project root is on sys.path for absolute imports like 'modules...'
root = Path(__file__).parent.resolve()
if str(root) not in sys.path:
    sys.path.insert(0, str(root))

import pytest
from unittest.mock import AsyncMock, MagicMock, patch


def pytest_ignore_collect(collection_path, config):
    """Ignore lowercase chatbot symlink to avoid duplicate test collection."""
    path_str = str(collection_path)
    if "modules/chatbot" in path_str:
        return True
    return False


# SocialFeed engine fixture
@pytest.fixture
def engine():
    from modules.SocialFeed.service.engine import SocialFeedEngine
    e = SocialFeedEngine()
    e.repo = AsyncMock()
    return e

# Telemedicine fixtures
@pytest.fixture
def telemedicine_db():
    return MagicMock()

@pytest.fixture
def telemedicine_service(telemedicine_db):
    with patch('modules.Telemedicine.service.service.AppointmentRepository') as ar, \
         patch('modules.Telemedicine.service.service.TimeSlotRepository') as tr, \
         patch('modules.Telemedicine.service.service.DoctorRepository') as dr, \
         patch('modules.Telemedicine.service.service.PatientRepository') as pr, \
         patch('modules.Telemedicine.service.service.VideoSessionRepository') as vr, \
         patch('modules.Telemedicine.service.service.MedicalRecordRepository') as mr:
        ar.return_value = AsyncMock()
        tr.return_value = AsyncMock()
        dr.return_value = AsyncMock()
        pr.return_value = AsyncMock()
        vr.return_value = AsyncMock()
        mr.return_value = AsyncMock()
        from modules.Telemedicine.service.service import TelemedicineService
        return TelemedicineService(telemedicine_db)
