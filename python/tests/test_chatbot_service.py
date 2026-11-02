"""Tests for Chatbot Service"""
import pytest
import os
import tempfile
from datetime import datetime, timezone
import uuid


@pytest.fixture
def chatbot_service():
    """Create chatbot service for testing"""
    from modules.Chatbot.service.chatbot_service import ChatbotService
    with tempfile.NamedTemporaryFile(delete=False, suffix='.json') as f:
        service = ChatbotService(storage_path=f.name)
        yield service
        try:
            os.unlink(f.name)
        except:
            pass


def test_create_conversation(chatbot_service):
    """Test creating a conversation"""
    conversation = chatbot_service.create_conversation(user_id="user_123")
    assert conversation.user_id == "user_123"
    assert conversation.status == "active"
    assert len(conversation.messages) == 0


def test_get_conversation(chatbot_service):
    """Test retrieving a conversation"""
    created = chatbot_service.create_conversation("user_456")
    
    retrieved = chatbot_service.get_conversation(created.id)
    assert retrieved is not None
    assert retrieved.id == created.id


def test_get_user_conversations(chatbot_service):
    """Test getting user conversations"""
    chatbot_service.create_conversation("user_1")
    chatbot_service.create_conversation("user_1")
    chatbot_service.create_conversation("user_2")
    
    conversations = chatbot_service.get_user_conversations("user_1")
    assert len(conversations) == 2


def test_send_message_greeting(chatbot_service):
    """Test sending a greeting message"""
    conversation = chatbot_service.create_conversation("user_1")
    
    response = chatbot_service.send_message(conversation.id, "Hello!")
    assert response.message is not None
    assert response.intent == "greeting"
    assert response.confidence > 0


def test_send_message_question(chatbot_service):
    """Test sending a question message"""
    conversation = chatbot_service.create_conversation("user_1")
    
    response = chatbot_service.send_message(conversation.id, "What is the status?")
    assert response.message is not None
    assert response.intent == "question"


def test_send_message_order(chatbot_service):
    """Test sending an order message"""
    conversation = chatbot_service.create_conversation("user_1")
    
    response = chatbot_service.send_message(conversation.id, "I want to order a product")
    assert response.message is not None
    assert response.intent == "order"


def test_send_message_unknown_intent(chatbot_service):
    """Test sending a message with unknown intent"""
    conversation = chatbot_service.create_conversation("user_1")
    
    response = chatbot_service.send_message(conversation.id, "xyzzy plugh")
    assert response.message is not None
    assert response.intent == "unknown"


def test_conversation_not_found(chatbot_service):
    """Test error handling for non-existent conversation"""
    fake_id = str(uuid.uuid4())
    
    with pytest.raises(ValueError):
        chatbot_service.send_message(fake_id, "Hello")


def test_escalate_conversation(chatbot_service):
    """Test escalating a conversation"""
    conversation = chatbot_service.create_conversation("user_1")
    
    escalated = chatbot_service.escalate_conversation(conversation.id, "Complex issue")
    assert escalated is not None
    assert escalated.status == "escalated"
    assert escalated.context.get('escalation_reason') == "Complex issue"


def test_close_conversation(chatbot_service):
    """Test closing a conversation"""
    conversation = chatbot_service.create_conversation("user_1")
    
    closed = chatbot_service.close_conversation(conversation.id)
    assert closed is not None
    assert closed.status == "completed"


def test_get_conversation_history(chatbot_service):
    """Test getting conversation history"""
    conversation = chatbot_service.create_conversation("user_1")
    chatbot_service.send_message(conversation.id, "Hello")
    chatbot_service.send_message(conversation.id, "How are you?")
    
    history = chatbot_service.get_conversation_history(conversation.id)
    assert len(history) >= 3  # user message + assistant response for each


def test_get_statistics(chatbot_service):
    """Test getting chatbot statistics"""
    chatbot_service.create_conversation("user_1")
    chatbot_service.create_conversation("user_2")
    
    stats = chatbot_service.get_chatbot_statistics()
    assert stats['total_conversations'] == 2


def test_suggested_actions(chatbot_service):
    """Test getting suggested actions for intent"""
    conversation = chatbot_service.create_conversation("user_1")
    
    response = chatbot_service.send_message(conversation.id, "I want to track my order")
    assert response.intent == "tracking"
    assert len(response.suggested_actions) > 0


if __name__ == "__main__":
    pytest.main([__file__, "-v"])