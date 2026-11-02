"""E2E tests for Fraud Detection module."""
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

def test_fraud_health():
    r = client.get("/health")
    assert r.status_code == 200
