"""Unit tests for rate limiting dependencies in Chatbot, SocialFeed, and Telemedicine."""
import sys
from unittest.mock import MagicMock
import pytest
from fastapi import HTTPException

# --- Mock heavy engines BEFORE importing routers ---

# Mock ChatbotEngine (heavy, may load ML models)
mock_chatbot_engine_mod = MagicMock()
mock_chatbot_engine_mod.ChatbotEngine = MagicMock(return_value=MagicMock())
sys.modules['modules.chatbot.service.engine'] = mock_chatbot_engine_mod

# Mock SocialFeedEngine
mock_socialfeed_engine_mod = MagicMock()
mock_socialfeed_engine_mod.SocialFeedEngine = MagicMock(return_value=MagicMock())
sys.modules['modules.socialfeed.service.engine'] = mock_socialfeed_engine_mod

# Telemedicine routes are self-contained with inline models; no heavy engine to mock.

# --- Now import rate limiting functions ---
from app.modules.chatbot.router import _chatbot_rate_data as chatbot_data, _chatbot_lock as chatbot_lock, chatbot_user_with_rate_limit as chatbot_dep
from app.modules.socialfeed.router import _socialfeed_rate_data as socialfeed_data, _socialfeed_lock as socialfeed_lock, socialfeed_user_with_rate_limit as socialfeed_dep
from app.modules.telemedicine.api.routes import _telemedicine_rate_data as telemedicine_data, _telemedicine_lock as telemedicine_lock, telemedicine_user_with_rate_limit as telemedicine_dep


@pytest.fixture(autouse=True)
def clear_all_rate_data():
    """Clear rate limit state before each test."""
    chatbot_data.clear()
    socialfeed_data.clear()
    telemedicine_data.clear()
    yield


@pytest.mark.anyio
async def test_chatbot_rate_limit():
    user_id = "test_user"
    for _ in range(60):
        assert await chatbot_dep(user_id) == user_id
    with pytest.raises(HTTPException) as exc:
        await chatbot_dep(user_id)
    assert exc.value.status_code == 429
    assert "Rate limit" in exc.value.detail


@pytest.mark.anyio
async def test_socialfeed_rate_limit():
    user_id = "test_user"
    for _ in range(60):
        assert await socialfeed_dep(user_id) == user_id
    with pytest.raises(HTTPException) as exc:
        await socialfeed_dep(user_id)
    assert exc.value.status_code == 429


@pytest.mark.anyio
async def test_telemedicine_rate_limit():
    user_id = "test_user"
    for _ in range(60):
        assert await telemedicine_dep(user_id) == user_id
    with pytest.raises(HTTPException) as exc:
        await telemedicine_dep(user_id)
    assert exc.value.status_code == 429
