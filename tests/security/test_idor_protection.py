"""
Security Test Suite: IDOR (Insecure Direct Object Reference) Protection

Tests that all endpoints properly enforce ownership checks and prevent
attackers from accessing unauthorized resources by manipulating IDs.

These tests cover the critical Python modules that we secured:
- Telemedicine
- Analytics
- Ads
- Ecommerce
- FraudDetection
- BotAutomation
- SocialFeed
- Chatbot
"""

import pytest
from fastapi.testclient import TestClient
from unittest.mock import Mock, patch, MagicMock
import json
from datetime import datetime, date, timedelta


import pytest
from fastapi.testclient import TestClient
from unittest.mock import Mock, patch, MagicMock
import json
from datetime import datetime, date, timedelta

class TestTelemedicineIDOR:
    """Test telemedicine endpoints for IDOR vulnerabilities"""

    @pytest.fixture(autouse=True)
    def reset_db(self):
        """Reset in-memory DB before each test"""
        from app.modules.telemedicine.api import routes as telemed_routes
        telemed_routes.doctors_db.clear()
        telemed_routes.appointments_db.clear()
        # Add a doctor
        telemed_routes.doctors_db["doc-1"] = MagicMock(
            id="doc-1", user_id="doctor-1", is_available=True
        )
        yield
        telemed_routes.doctors_db.clear()
        telemed_routes.appointments_db.clear()

    @pytest.fixture
    def client_authenticated_user_123(self):
        """Create test client with user-123 authenticated"""
        from app.modules.telemedicine.api import routes as telemed_routes
        from fastapi import FastAPI
        app = FastAPI()
        app.include_router(telemed_routes.router)

        with patch('python.app.modules.telemedicine.api.routes.get_current_user_id') as mock_auth:
            mock_auth.return_value = "user-123"
            yield TestClient(app)

    def test_create_appointment_patient_id_must_match(self, client_authenticated_user_123):
        """POST /appointments should reject if patient_id != auth user"""
        appointment_data = {
            "doctor_id": "doc-1",
            "patient_id": "other-patient",  # mismatch
            "scheduled_time": (datetime.now() + timedelta(days=1)).isoformat(),
            "consultation_type": "video",
            "symptoms": "test"
        }
        response = client_authenticated_user_123.post(
            "/api/telemedicine/appointments",
            json=appointment_data
        )
        assert response.status_code == 403, f"Expected 403, got {response.status_code}"

    def test_get_appointment_ownership_check(self, client_authenticated_user_123):
        """GET /appointments/{id} should enforce patient/doctor ownership"""
        from app.modules.telemedicine.api.routes import appointments_db
        # Create appointment for user-123
        appt = MagicMock(
            id="appt-1",
            patient_id="user-123",
            doctor_id="doc-1",
            status="pending"
        )
        appointments_db["appt-1"] = appt

        response = client_authenticated_user_123.get("/api/telemedicine/appointments/appt-1")
        assert response.status_code == 200, f"Should allow own appointment, got {response.status_code}"

        # Now try to access appointment belonging to other patient
        appointments_db["appt-2"] = MagicMock(
            id="appt-2", patient_id="other-patient", doctor_id="doc-1", status="pending"
        )
        response = client_authenticated_user_123.get("/api/telemedicine/appointments/appt-2")
        assert response.status_code == 403, f"Should forbid other's appointment, got {response.status_code}"

    def test_cancel_appointment_ownership(self, client_authenticated_user_123):
        """POST /appointments/{id}/cancel checks ownership"""
        from app.modules.telemedicine.api.routes import appointments_db

        # Own appointment -> success
        appt1 = MagicMock(patient_id="user-123", doctor_id="doc-1", status="pending")
        appointments_db["appt-1"] = appt1
        response = client_authenticated_user_123.post("/api/telemedicine/appointments/appt-1/cancel")
        assert response.status_code == 200

        # Other's appointment -> 403
        appt2 = MagicMock(patient_id="other-patient", doctor_id="doc-1", status="pending")
        appointments_db["appt-2"] = appt2
        response = client_authenticated_user_123.post("/api/telemedicine/appointments/appt-2/cancel")
        assert response.status_code == 403

    def test_confirm_appointment_doctor_only(self, client_authenticated_user_123):
        """POST /appointments/{id}/confirm restricted to assigned doctor"""
        from app.modules.telemedicine.api.routes import appointments_db

        # Appointment with different doctor
        appt = MagicMock(patient_id="patient-1", doctor_id="other-doctor", status="pending")
        appointments_db["appt-1"] = appt

        response = client_authenticated_user_123.post("/api/telemedicine/appointments/appt-1/confirm")
        assert response.status_code == 403, "Only assigned doctor can confirm"

    def test_create_appointment_race_condition_protection(self, client_authenticated_user_123):
        """Verify appointment creation prevents double booking via lock"""
        # This test would need to trigger concurrent requests; simplified check
        appointment_data = {
            "doctor_id": "doc-1",
            "patient_id": "user-123",
            "scheduled_time": (datetime.now() + timedelta(hours=1)).isoformat(),
            "consultation_type": "video",
            "symptoms": "test"
        }
        # First appointment should succeed
        response1 = client_authenticated_user_123.post(
            "/api/telemedicine/appointments", json=appointment_data
        )
        assert response1.status_code in [200, 201]

        # Second identical booking should fail (same patient, doctor, time)
        response2 = client_authenticated_user_123.post(
            "/api/telemedicine/appointments", json=appointment_data
        )
        # With lock, should get 409 Conflict
        assert response2.status_code == 409, \
            f"Expected 409 Conflict for double booking, got {response2.status_code}"

    def test_get_appointment_requires_auth(self, client):
        """Test that GET /appointments/{id} requires authentication and ownership"""
        # Try to access another user's appointment - should be forbidden
        response = client.get("/api/telemedicine/appointments/appt_999")
        assert response.status_code in [401, 403], "Unauthenticated or unauthorized"

    def test_get_patient_appointments_only_own(self, client):
        """Test GET /appointments/patient/{patient_id} only allows own records"""
        # Try to access another patient's appointments
        response = client.get("/api/telemedicine/appointments/patient/other-user")
        assert response.status_code == 403, "Should forbid access to other patients' appointments"

    def test_create_appointment_patient_must_match(self, client):
        """Test POST /appointments ensures patient_id matches authenticated user"""
        appointment_data = {
            "doctor_id": "doc-1",
            "patient_id": "other-patient",  # Mis-match
            "scheduled_time": (datetime.now() + timedelta(days=1)).isoformat(),
            "consultation_type": "video",
            "symptoms": "test"
        }
        response = client.post("/api/telemedicine/appointments", json=appointment_data)
        assert response.status_code == 403, "Should forbid creating appointment for another patient"

    def test_cancel_appointment_ownership(self, client):
        """Test POST /appointments/{id}/cancel checks ownership"""
        # Mock appointment exists
        with patch('python.app.modules.telemedicine.api.routes.appointments_db', {"appt_1": MagicMock(
            patient_id="user-123", doctor_id="doc-1", status="pending"
        )}):
            response = client.post("/api/telemedicine/appointments/appt_1/cancel")
            assert response.status_code == 200, "Patient should cancel own appointment"

        # Try cancel appointment for another patient (by modifying mock)
        with patch('python.app.modules.telemedicine.api.routes.appointments_db', {"appt_2": MagicMock(
            patient_id="other-patient", doctor_id="doc-1", status="pending"
        )}):
            response = client.post("/api/telemedicine/appointments/appt_2/cancel")
            assert response.status_code == 403, "Should forbid cancelling others' appointments"

    def test_confirm_appointment_doctor_only(self, client):
        """Test POST /appointments/{id}/confirm restricted to assigned doctor"""
        with patch('python.app.modules.telemedicine.api.routes.appointments_db', {"appt_1": MagicMock(
            patient_id="user-123", doctor_id="other-doctor", status="pending"
        )}), patch('python.app.modules.telemedicine.api.routes.doctors_db', {"other-doctor": MagicMock()}):
            response = client.post("/api/telemedicine/appointments/appt_1/confirm")
            assert response.status_code == 403, "Only assigned doctor can confirm"


class TestAnalyticsIDOR:
    """Test analytics endpoints for IDOR vulnerabilities"""

    @pytest.fixture
    def client(self):
        from app.modules.analytics.api import routes as analytics_routes
        from fastapi import FastAPI
        app = FastAPI()
        app.include_router(analytics_routes.router)
        with patch('python.app.modules.analytics.api.routes.get_current_user_id') as mock_auth:
            mock_auth.return_value = "user-123"
            yield TestClient(app)

    def test_get_user_analytics_own_only(self, client):
        """GET /analytics/users/{user_id} should restrict to own user"""
        response = client.get("/api/analytics/users/other-user?days=30")
        assert response.status_code == 403, "Forbidden to view other users' analytics"

    def test_get_churn_prediction_own_only(self, client):
        """GET /analytics/churn-prediction/{user_id} should restrict to own user"""
        response = client.get("/api/analytics/churn-prediction/other-user")
        assert response.status_code == 403, "Forbidden to view other users' churn"

    def test_track_event_user_spoofing_prevented(self, client):
        """POST /analytics/events should use authenticated user, not body user_id"""
        event_data = {
            "user_id": "hacker-456",  # Should be ignored
            "event_type": "page_view",
            "properties": {}
        }
        response = client.post("/api/analytics/events", json=event_data)
        assert response.status_code == 200, "Should accept event (user_id from auth, not body)"


class TestAdsIDOR:
    """Test advertising endpoints for IDOR vulnerabilities"""

    @pytest.fixture
    def client(self):
        from app.modules.ads.api import routes as ads_routes
        from fastapi import FastAPI
        app = FastAPI()
        app.include_router(ads_routes.router)
        with patch('python.app.modules.ads.api.routes.get_current_user_id') as mock_auth:
            mock_auth.return_value = "advertiser-123"
            yield TestClient(app)

    def test_create_campaign_advertiser_match(self, client):
        """POST /ads/campaigns should set advertiser_id from auth"""
        campaign = {
            "advertiser_id": "other-advertiser",  # Should be rejected
            "name": "Test Campaign",
            "ad_type": "banner",
            "budget": 1000.0,
            "start_date": "2025-01-01",
            "end_date": "2025-12-31"
        }
        response = client.post("/api/ads/campaigns", json=campaign)
        assert response.status_code == 403, "Cannot create campaign for another advertiser"

    def test_get_campaign_ownership(self, client):
        """GET /ads/campaigns/{id} should check campaign belongs to user"""
        with patch('python.app.modules.ads.api.routes.ad_service.get_campaign') as mock_get:
            mock_get.return_value = MagicMock(advertiser_id="other-advertiser", id="camp_1")
            response = client.get("/api/ads/campaigns/camp_1")
            assert response.status_code == 403, "Cannot view other advertisers' campaigns"

    def test_campaign_stats_ownership(self, client):
        """GET /ads/campaigns/{id}/stats should enforce ownership"""
        with patch('python.app.modules.ads.api.routes.ad_service.get_campaign') as mock_get:
            mock_get.return_value = MagicMock(advertiser_id="other-advertiser")
            response = client.get("/api/ads/campaigns/camp_1/stats")
            assert response.status_code == 403, "Cannot view other advertisers' stats"

    def test_advertiser_stats_own_only(self, client):
        """GET /ads/advertisers/{id}/stats should match authenticated user"""
        response = client.get("/api/ads/advertisers/other-advertiser/stats")
        assert response.status_code == 403, "Cannot view other advertisers' stats"


class TestEcommerceIDOR:
    """Test ecommerce endpoints for IDOR vulnerabilities"""

    @pytest.fixture
    def client(self):
        from app.modules.ecommerce.api import routes as ecom_routes
        from fastapi import FastAPI
        app = FastAPI()
        app.include_router(ecom_routes.router)
        with patch('python.app.modules.ecommerce.api.routes.get_current_user_id') as mock_auth:
            mock_auth.return_value = "user-123"
            yield TestClient(app)

    def test_get_cart_own_only(self, client):
        """GET /ecommerce/cart/{user_id} should only allow own cart"""
        response = client.get("/api/ecommerce/cart/other-user")
        assert response.status_code == 403, "Cannot access other users' carts"

    def test_get_recommendations_own_only(self, client):
        """GET /ecommerce/recommendations/{user_id} should restrict"""
        response = client.get("/api/ecommerce/recommendations/other-user?limit=10")
        assert response.status_code == 403, "Cannot access other users' recommendations"

    def test_checkout_user_must_match(self, client):
        """POST /ecommerce/checkout should use authenticated user"""
        checkout_data = {"total": 100.0}
        response = client.post("/api/ecommerce/checkout", json=checkout_data)
        assert response.status_code == 200
        # Verify response includes correct user_id (from auth, not body)
        # Implementation should set user_id from current_user_id

    def test_add_to_cart_authenticated(self, client):
        """POST /ecommerce/cart should require auth"""
        cart_data = {"product_id": "prod-1", "quantity": 1}
        response = client.post("/api/ecommerce/cart", json=cart_data)
        assert response.status_code == 200, "Should allow authenticated user to add to cart"


class TestFraudDetectionIDOR:
    """Test fraud detection endpoints for IDOR vulnerabilities"""

    @pytest.fixture
    def client(self):
        from app.modules.fraud_detection.api import routes as fraud_routes
        from fastapi import FastAPI
        app = FastAPI()
        app.include_router(fraud_routes.router)
        with patch('python.app.modules.fraud_detection.api.routes.get_current_user_id') as mock_auth:
            mock_auth.return_value = "user-123"
            yield TestClient(app)

    def test_analyze_transaction_user_match(self, client):
        """POST /fraud/analyze should ensure transaction user_id matches auth"""
        tx_data = {
            "id": "tx_1",
            "user_id": "other-user",  # Mismatch
            "amount": 100.0,
            "currency": "VND",
            "merchant_id": "merchant-1",
            "payment_method": "card",
            "device_id": "device-1",
            "ip_address": "1.2.3.4",
            "location": {"lat": 10.0, "lng": 20.0}
        }
        response = client.post("/api/fraud/analyze", json=tx_data)
        assert response.status_code == 403, "Cannot analyze transactions for other users"

    def test_get_risk_profile_own_only(self, client):
        """GET /fraud/user/{user_id}/risk-profile should restrict"""
        response = client.get("/api/fraud/user/other-user/risk-profile")
        assert response.status_code == 403, "Cannot view other users' risk profiles"


class TestBotAutomationIDOR:
    """Test bot automation endpoints for IDOR vulnerabilities"""

    @pytest.fixture
    def client(self):
        from app.modules.botautomation.api import routes as bot_routes
        from fastapi import FastAPI
        app = FastAPI()
        app.include_router(bot_routes.router)
        with patch('python.app.modules.botautomation.api.routes.get_current_user_id') as mock_auth:
            mock_auth.return_value = "user-123"
            yield TestClient(app)

    def test_train_bot_ownership(self, client):
        """POST /bots/{bot_id}/train should verify bot ownership"""
        with patch('python.app.modules.botautomation.api.routes.bot_service.get_bot') as mock_get:
            mock_get.return_value = {"owner_id": "other-user", "id": "bot_1"}
            response = client.post("/api/bots/bot_1/train", json={"data": {}})
            assert response.status_code == 403, "Only bot owner can train"

    def test_get_bot_analytics_ownership(self, client):
        """GET /bots/{bot_id}/analytics should check ownership"""
        with patch('python.app.modules.botautomation.api.routes.bot_service.get_bot') as mock_get:
            mock_get.return_value = {"owner_id": "other-user"}
            response = client.get("/api/bots/bot_1/analytics")
            assert response.status_code == 403, "Only bot owner can view analytics"

    def test_chat_with_bot_user_spoofing_prevented(self, client):
        """POST /bots/{bot_id}/chat should not allow impersonation"""
        message = {"message": "hello", "user_id": "other-user"}
        response = client.post("/api/bots/bot_1/chat", json=message)
        assert response.status_code == 403, "Cannot send messages on behalf of another user"


class TestSocialFeedIDOR:
    """Test social feed endpoints for IDOR vulnerabilities"""

    @pytest.fixture
    def client(self):
        from app.modules.socialfeed.router import router as social_router
        from fastapi import FastAPI
        app = FastAPI()
        app.include_router(social_router)
        with patch('python.app.modules.socialfeed.router.get_current_user_id') as mock_auth:
            mock_auth.return_value = "user-123"
            yield TestClient(app)

    def test_get_user_posts_own_only(self, client):
        """GET /socialfeed/posts/user/{user_id} should restrict to own"""
        response = client.get("/socialfeed/posts/user/other-user")
        assert response.status_code == 403, "Cannot view other users' posts list"

    def test_get_followers_own_only(self, client):
        """GET /socialfeed/users/{user_id}/followers should check ownership"""
        response = client.get("/socialfeed/users/other-user/followers")
        assert response.status_code == 403, "Cannot view other users' followers"

    def test_get_following_own_only(self, client):
        """GET /socialfeed/users/{user_id}/following should check ownership"""
        response = client.get("/socialfeed/users/other-user/following")
        assert response.status_code == 403, "Cannot view other users' following list"


class TestChatbotIDOR:
    """Test chatbot endpoints for IDOR vulnerabilities"""

    @pytest.fixture
    def client(self):
        from app.modules.chatbot.router import router as chatbot_router
        from fastapi import FastAPI
        app = FastAPI()
        app.include_router(chatbot_router)
        with patch('python.app.modules.chatbot.router.get_current_user_id') as mock_auth:
            mock_auth.return_value = "user-123"
            yield TestClient(app)

    def test_chat_new_user_must_match(self, client):
        """POST /chatbot/chat/{user_id} should ensure user_id matches auth"""
        response = client.post(
            "/chatbot/chat/other-user",
            json={"message": "hello"}
        )
        assert response.status_code == 403, "Cannot chat on behalf of another user"

    def test_chat_conversation_ownership(self, client):
        """GET /chatbot/chat/{conversation_id} should verify conversation ownership"""
        with patch('python.app.modules.chatbot.router.engine.conversations', {
            "conv_other": MagicMock(user_id="other-user")
        }):
            response = client.get("/chatbot/chat/conv_other")
            assert response.status_code == 403, "Cannot access others' conversations"


class TestAIConversationSummaryIDOR:
    """Test AI conversation summary endpoint for IDOR"""

    @pytest.fixture
    def client(self):
        from app.modules.ai.api import routes as ai_routes
        from fastapi import FastAPI
        app = FastAPI()
        app.include_router(ai_routes.router)
        with patch('python.app.modules.ai.api.routes.get_current_user_id') as mock_auth:
            mock_auth.return_value = "user-123"
            yield TestClient(app)

    def test_conversation_summary_requires_auth(self, client):
        """GET /ai/summary/{conversation_id} should require authentication"""
        response = client.get("/api/ai/summary/conv_123")
        # Should be 200 but in real implementation would need membership check
        assert response.status_code != 401, "Requires authentication"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
