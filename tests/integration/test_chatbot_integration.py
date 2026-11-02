"""Integration tests for Chatbot API endpoints."""
import pytest
import sys
import os
from fastapi import FastAPI
from fastapi.testclient import TestClient

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..', 'python')))

os.environ.setdefault('JWT_SECRET_KEY', 'test-secret-key-for-integration')
os.environ.setdefault('JWT_ALGORITHM', 'HS256')

from app.modules.chatbot.router import router as chatbot_router
from app.modules.chatbot.service.engine import ChatbotEngine
from app.shared.middleware.auth import get_current_user_id as original_get_current_user_id

@pytest.fixture(autouse=True)
def reset_engine(monkeypatch):
    """Provide a fresh ChatbotEngine for each test and clear rate limits."""
    from app.modules.chatbot import router as cb_router
    new_engine = ChatbotEngine()
    monkeypatch.setattr(cb_router, 'engine', new_engine)
    cb_router._chatbot_rate_data.clear()
    return new_engine

@pytest.fixture
def app():
    app = FastAPI()
    app.include_router(chatbot_router)

    async def override_get_current_user_id():
        return "test_user_123"
    app.dependency_overrides[original_get_current_user_id] = override_get_current_user_id

    return app

@pytest.fixture
def client(app):
    with TestClient(app) as client:
        yield client

def test_chat_endpoint_returns_response(client, reset_engine):
    """POST /chatbot/chat/{user_id}/{conversation_id} should process message and return response."""
    response = client.post(
        "/chatbot/chat/test_user_123/conv_1",
        json={"message": "Hello"}
    )
    if response.status_code != 200:
        print(f"FAIL DEBUG: status={response.status_code}, body={response.text}")
    assert response.status_code == 200, f"Expected 200, got {response.status_code} - {response.text}"
    data = response.json()
    assert "response" in data
    assert data["conversation_id"] == "conv_1"
    assert data["success"] is True
    # Check engine stored conversation
    conv = reset_engine.get_conversation("conv_1")
    assert conv is not None
    assert conv.user_id == "test_user_123"
    assert len(conv.messages) == 2  # user + bot

def test_chat_new_endpoint_creates_conversation(client, reset_engine):
    """POST /chatbot/chat/{user_id} should create new conversation with random ID."""
    response = client.post(
        "/chatbot/chat/test_user_123",
        json={"message": "Hi there!"}
    )
    assert response.status_code == 200
    data = response.json()
    assert "conversation_id" in data
    conv_id = data["conversation_id"]
    # Verify conversation exists in engine
    conv = reset_engine.get_conversation(conv_id)
    assert conv is not None
    assert conv.user_id == "test_user_123"

def test_get_conversation_ownership(client, reset_engine):
    """GET /chatbot/chat/{conversation_id} should enforce ownership."""
    conv_id = "conv_owner_test"
    reset_engine.conversations[conv_id] = type('ConversationState', (), {
        'id': conv_id,
        'user_id': 'test_user_123',
        'status': 'open',
        'messages': []
    })()
    response = client.get(f"/chatbot/chat/{conv_id}")
    assert response.status_code == 200
    # Change owner to another user via direct engine manipulation
    reset_engine.conversations[conv_id].user_id = "other_user"
    response = client.get(f"/chatbot/chat/{conv_id}")
    assert response.status_code == 403

def test_end_conversation(client, reset_engine):
    """POST /chatbot/chat/{conversation_id}/end should close conversation."""
    conv_id = "conv_end_test"
    reset_engine.conversations[conv_id] = type('ConversationState', (), {
        'id': conv_id,
        'user_id': 'test_user_123',
        'status': 'open',
        'messages': []
    })()
    response = client.post(f"/chatbot/chat/{conv_id}/end")
    if response.status_code != 200:
        print(f"DEBUG end_conversation: {response.status_code} - {response.text}")
    assert response.status_code == 200
    assert response.json()["status"] == "closed"
    assert reset_engine.conversations[conv_id].status == "closed"

def test_add_knowledge_base(client, reset_engine):
    """POST /chatbot/knowledge-base should add article."""
    article = {
        "title": "FAQ",
        "content": "How do I reset my password?",
        "intents": ["password_reset"]
    }
    response = client.post("/chatbot/knowledge-base", json=article)
    assert response.status_code == 200
    assert response.json()["status"] == "added"
    # Verify article in engine
    assert any(kb.get("title") == "FAQ" for kb in reset_engine.knowledge_base)

def test_chat_unauthorized_different_user(client, reset_engine):
    """Chat endpoint should reject if user_id in path does not match authenticated user."""
    response = client.post(
        "/chatbot/chat/other_user/conv_unauth",
        json={"message": "Hello"}
    )
    assert response.status_code == 403

def test_get_conversation_not_found(client):
    """GET nonexistent conversation returns 404."""
    response = client.get("/chatbot/chat/nonexistent_conv")
    assert response.status_code == 404

if __name__ == "__main__":
    pytest.main([__file__, "-v"])
