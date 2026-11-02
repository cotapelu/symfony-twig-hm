"""Test analytics module endpoints require authentication.

These tests require a running application and database. Set INTEGRATION_TESTS=1 to enable.
"""
import os
import sys
import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient
from unittest.mock import MagicMock, patch

# Skip by default
if os.getenv("INTEGRATION_TESTS") != "1":
    pytest.skip("Integration tests skipped by default (set INTEGRATION_TESTS=1 to run)", allow_module_level=True)

# Set test environment
os.environ.setdefault('JWT_SECRET_KEY', 'test-secret')
os.environ.setdefault('JWT_ALGORITHM', 'HS256')

# Mock analytics service
mock_analytics = MagicMock()
mock_analytics.get_retention_data.return_value = {"retention_rate": 0.85}
mock_analytics.analyze_funnel.return_value = {"funnel": []}
mock_analytics.get_cohort_analysis.return_value = {"cohorts": []}
mock_analytics.get_user_activity.return_value = {"activity": []}
mock_analytics.get_engagement_metrics.return_value = {"engagement": {}}
mock_analytics.predict_churn.return_value = {"score": 0.5}
mock_analytics.track_event.return_value = "event-123"

# Patch the analytics service
sys.modules['app.modules.analytics.analytics'] = MagicMock()
sys.modules['app.modules.analytics.analytics'].AnalyticsService = MagicMock(return_value=mock_analytics)

from app.modules.analytics.api.routes import router as analytics_router
from app.shared.middleware.auth import create_token

app = FastAPI()
app.include_router(analytics_router)

class TestAnalyticsAuth:
    """Test that analytics endpoints require authentication."""

    @pytest.fixture
    def client(self):
        return TestClient(app=app)

    @pytest.fixture
    def auth_token(self):
        return create_token({"sub": "test-user-id"})

    @pytest.fixture(autouse=True)
    def clear_analytics_mock(self):
        mock_analytics.reset_mock()

    # User analytics endpoints (already have auth) -------------------
    def test_user_analytics_without_token_fails(self, client):
        response = client.get("/api/analytics/users/123?days=7")
        assert response.status_code == 403

    def test_user_analytics_with_token_succeeds(self, client, auth_token):
        # Use the user_id from token
        response = client.get(
            "/api/analytics/users/test-user-id?days=7",
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code == 200

    # Admin analytics endpoints (needs auth) -------------------------
    def test_retention_without_token_fails(self, client):
        response = client.get("/api/analytics/retention?start_date=2024-01-01&end_date=2024-01-31")
        assert response.status_code == 403, f"Expected 403, got {response.status_code}"

    def test_retention_with_token_succeeds(self, client, auth_token):
        response = client.get(
            "/api/analytics/retention?start_date=2024-01-01&end_date=2024-01-31",
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code == 200, f"Expected 200, got {response.status_code}"

    def test_funnel_without_token_fails(self, client):
        response = client.get("/api/analytics/funnel?funnel_steps=step1,step2,step3")
        assert response.status_code == 403

    def test_funnel_with_token_succeeds(self, client, auth_token):
        response = client.get(
            "/api/analytics/funnel",
            params={"funnel_steps": ["step1", "step2", "step3"]},
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        print(f"Funnel response: {response.status_code} {response.text}")
        assert response.status_code == 200

    def test_cohort_without_token_fails(self, client):
        response = client.get("/api/analytics/cohort?cohort_period=week&periods=8")
        assert response.status_code == 403

    def test_cohort_with_token_succeeds(self, client, auth_token):
        response = client.get(
            "/api/analytics/cohort?cohort_period=week&periods=8",
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code == 200
