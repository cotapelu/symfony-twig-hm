"""E2E tests for Ecommerce module."""
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

def test_health():
    r = client.get("/health")
    assert r.status_code == 200

def test_list_products():
    r = client.get("/api/ecommerce/products?limit=5")
    assert r.status_code == 200
    data = r.json()
    assert "products" in data or isinstance(data, list)
