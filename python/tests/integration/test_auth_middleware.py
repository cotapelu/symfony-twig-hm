import pytest
from fastapi.testclient import TestClient
from fastapi import status
from unittest.mock import patch

from app.main import app
from app.shared.middleware.auth import verify_token, get_current_user_id, create_token
from app.shared.config.settings import settings

class TestAuthMiddleware:
    """Test cases for authentication middleware."""
    
    @pytest.fixture
    def client(self):
        """Create test client."""
        return TestClient(app=app)
    
    @pytest.fixture
    def auth_token(self, client):
        """Create a valid JWT token for testing."""
        # Rely on create_token to set a future expiration
        return create_token({"sub": "test-user-id", "iss": "qcoder-community"})
    
    @pytest.fixture
    def expired_token(self):
        """Create an expired JWT token."""
        import time
        now = int(time.time())
        return create_token(
            {"sub": "test-user-id", "iat": now - 86401, "exp": now - 86400, "iss": "qcoder-community"}
        )
    
    def test_verify_token_valid_token(self, client, auth_token):
        """Test that valid tokens are accepted."""
        response = client.get(
            "/api/protected",
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code == 200
        assert response.json() == {
            "message": "This is a protected endpoint",
            "user_id": "test-user-id"
        }
    
    def test_verify_token_missing_header(self, client):
        """Test that missing Authorization header is rejected."""
        response = client.get("/api/protected")
        assert response.status_code == 403
        # FastAPI HTTPBearer returns 403 with detail for missing creds
        assert response.json().get("detail") == "Not authenticated"
    
    def test_verify_token_invalid_token(self, client):
        """Test that invalid tokens are rejected."""
        response = client.get(
            "/api/protected",
            headers={"Authorization": "Bearer invalid-token"}
        )
        assert response.status_code == 401
        # Response uses 'detail' key for error message
        assert response.json().get("detail") in ["Invalid or expired token", "Could not validate credentials"]
    
    def test_verify_token_expired_token(self, client, expired_token):
        """Test that expired tokens are rejected."""
        response = client.get(
            "/api/protected",
            headers={"Authorization": f"Bearer {expired_token}"}
        )
        assert response.status_code == 401
        assert response.json().get("detail") in ["Token has expired", "Invalid or expired token"]
    
    def test_get_current_user_id(self, client, auth_token):
        """Test that user ID is extracted from valid token."""
        response = client.get(
            "/api/protected",
            headers={"Authorization": f"Bearer {auth_token}"}
        )
        assert response.status_code == 200
        assert response.json()["user_id"] == "test-user-id"
    
    def test_health_endpoint_no_auth(self, client):
        """Test that health endpoint doesn't require authentication."""
        response = client.get("/health")
        assert response.status_code == 200
        # Accept healthy or degraded (service up but some subsystems may be degraded in test env)
        assert response.json()["status"] in ["healthy", "degraded"]
        assert response.json()["service"] == "python-monolith"
    
    def test_root_endpoint_no_auth(self, client):
        """Test that root endpoint doesn't require authentication."""
        response = client.get("/")
        assert response.status_code == 200
        assert response.json()["status"] == "running"
        # Modules list may vary
        modules = response.json().get("modules", [])
        assert "ai" in modules
        assert "media" in modules
    
    def test_token_creation(self):
        """Test that tokens can be created with proper payload."""
        from datetime import timedelta
        
        token = create_token({"sub": "user-123"})
        
        assert isinstance(token, str)
        assert len(token) > 0
        assert "." in token
    
    def test_token_expiry_configurable(self, client):
        """Test that token expiration time is configurable."""
        # Create a token with explicit future expiration
        import time
        now = int(time.time())
        token = create_token({"sub": "user-456", "exp": now + 3600})
        
        # Token should be valid
        response = client.get(
            "/api/protected",
            headers={"Authorization": f"Bearer {token}"}
        )
        assert response.status_code == 200
        assert response.json()["user_id"] == "user-456"
    
    def test_secret_key_from_env(self):
        """Test that secret key is loaded from environment."""
        assert settings.jwt.secret_key is not None
        assert len(settings.jwt.secret_key) > 0
        
    def test_algorithm_from_env(self):
        """Test that algorithm is loaded from environment."""
        assert settings.jwt.algorithm is not None
        assert settings.jwt.algorithm in ["HS256", "HS384", "HS512"]
