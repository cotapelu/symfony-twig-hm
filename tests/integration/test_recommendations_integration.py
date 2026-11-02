"""Integration tests for Recommendations API endpoints."""
import pytest
import sys
import os
from fastapi import FastAPI
from fastapi.testclient import TestClient

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..', 'python')))

os.environ.setdefault('JWT_SECRET_KEY', 'test-secret-key-for-integration')
os.environ.setdefault('JWT_ALGORITHM', 'HS256')

from app.modules.recommendations.api.routes import router as rec_router
from app.shared.middleware.auth import get_current_user_id as original_get_current_user_id

@pytest.fixture
def app():
    app = FastAPI()
    app.include_router(rec_router)

    async def override_get_current_user_id():
        return "test_user_123"
    app.dependency_overrides[original_get_current_user_id] = override_get_current_user_id

    return app

@pytest.fixture
def client(app):
    with TestClient(app) as client:
        yield client

def test_get_recommendations_returns_items(client):
    """GET /recommendations/ should return a list of recommended item IDs."""
    response = client.get("/recommendations/")
    assert response.status_code == 200, f"Expected 200, got {response.status_code} - {response.text}"
    data = response.json()
    assert data["user_id"] == "test_user_123"
    assert isinstance(data["items"], list)
    # Since service returns random items, ensure at least one item
    assert len(data["items"]) > 0

def test_recommendations_limit_parameter(client):
    """Recommendations should respect limit (default 10)."""
    response = client.get("/recommendations/")
    items = response.json()["items"]
    # Default limit is 10
    assert len(items) <= 10

if __name__ == "__main__":
    pytest.main([__file__, "-v"])
