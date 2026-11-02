"""Test AI module authentication (ensures unprotected routes are secured).

These tests require a running application. Set INTEGRATION_TESTS=1 to enable.
"""
import os
import sys
import pytest
from fastapi import FastAPI
from fastapi.testclient import TestClient
from unittest.mock import MagicMock

# Skip by default
if os.getenv("INTEGRATION_TESTS") != "1":
    pytest.skip("Integration tests skipped by default (set INTEGRATION_TESTS=1 to run)", allow_module_level=True)

# Set test environment variables before imports
os.environ.setdefault('JWT_SECRET_KEY', 'test-secret')
os.environ.setdefault('JWT_ALGORITHM', 'HS256')

# Create mock AIService
mock_ai_service = MagicMock()
mock_ai_service.generate_smart_replies.return_value = ["mocked reply"]
mock_ai_service.analyze_sentiment.return_value = {"sentiment": "neutral"}
mock_ai_service.translate_text.return_value = {"translated_text": "hola"}
mock_ai_service.moderate_content.return_value = {"is_violation": False}
mock_ai_service.generate_content.return_value = {"generated_content": "mocked"}
mock_ai_service.generate_summary.return_value = {
    "conversation_id": "test-convo-id",
    "summary": "Test summary",
    "key_points": ["point1"],
    "action_items": []
}

# Patch the AIService in the services module before importing the router
sys.modules['app.modules.ai.services.ai_service'] = MagicMock()
sys.modules['app.modules.ai.services.ai_service'].AIService = MagicMock(return_value=mock_ai_service)

# Now import the AI router and auth utils
from app.modules.ai.api.routes import router as ai_router
from app.shared.middleware.auth import create_token

# Build minimal FastAPI app for testing
app = FastAPI()
app.include_router(ai_router)

class TestAIAuth:
    """Test AI module endpoints require authentication."""

    @pytest.fixture(autouse=True)
    def clear_rate_limit(self):
        """Clear rate limit state if present."""
        try:
            from app.middleware.rate_limit import clear_rate_limit
            clear_rate_limit()
        except ImportError:
            pass
        yield

    @pytest.fixture
    def client(self):
        return TestClient(app=app)

    @pytest.fixture
    def auth_token(self):
        return create_token({"sub": "test-user-id"})

    def test_generate_content_requires_auth(self, client):
        response = client.post(
            "/api/ai/generate",
            json={"content_type": "text", "prompt": "Hello"}
        )
        assert response.status_code == 403, f"Expected 403, got {response.status_code}"

    def test_generate_content_with_auth_succeeds(self, client, auth_token):
        response = client.post(
            "/api/ai/generate",
            json={"content_type": "text", "prompt": "Hello"},
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code == 200, f"Expected 200, got {response.status_code}"

    def test_smart_replies_require_auth(self, client):
        response = client.post(
            "/api/ai/smart-replies",
            json={"conversation_context": [], "last_message": "Hi"}
        )
        assert response.status_code == 403

    def test_smart_replies_with_auth(self, client, auth_token):
        response = client.post(
            "/api/ai/smart-replies",
            json={"conversation_context": [], "last_message": "Hi"},
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code == 200

    def test_sentiment_requires_auth(self, client):
        response = client.post(
            "/api/ai/sentiment",
            json={"content": "I love this!"}
        )
        assert response.status_code == 403

    def test_sentiment_with_auth(self, client, auth_token):
        response = client.post(
            "/api/ai/sentiment",
            json={"content": "I love this!"},
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code == 200

    def test_translate_requires_auth(self, client):
        response = client.post(
            "/api/ai/translate",
            json={"text": "Hello", "source_language": "en", "target_language": "vi"}
        )
        assert response.status_code == 403

    def test_translate_with_auth(self, client, auth_token):
        response = client.post(
            "/api/ai/translate",
            json={"text": "Hello", "source_language": "en", "target_language": "vi"},
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code == 200

    def test_moderate_requires_auth(self, client):
        response = client.post(
            "/api/ai/moderate",
            json={"content": "Some text to moderate", "content_type": "text"}
        )
        assert response.status_code == 403

    def test_moderate_with_auth(self, client, auth_token):
        response = client.post(
            "/api/ai/moderate",
            json={"content": "Some text to moderate", "content_type": "text"},
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code == 200

    def test_summary_already_protected(self, client, auth_token):
        response = client.get(
            "/api/ai/summary/test-convo-id",
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code != 401
