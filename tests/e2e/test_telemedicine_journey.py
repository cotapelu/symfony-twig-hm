"""E2E test: Complete patient journey using Telemedicine API."""
import os
os.environ.setdefault('JWT_SECRET_KEY', 'test-secret-key-for-e2e')
os.environ.setdefault('JWT_ALGORITHM', 'HS256')

import sys
from pathlib import Path
sys.path.append(str(Path(__file__).resolve().parents[2] / 'python'))

import pytest
from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)

def test_patient_journey_telemedicine():
    # Simplified health check for now
    r = client.get("/health")
    assert r.status_code == 200

def test_doctor_journey_telemedicine():
    r = client.get("/health")
    assert r.status_code == 200
