"""Tests for Chatbot Engine"""
import pytest


def test_chatbot_engine_import():
    """Test that ChatbotEngine can be imported"""
    from modules.Chatbot.service.engine import ChatbotEngine
    assert ChatbotEngine is not None


def test_chatbot_schemas_import():
    """Test that chatbot schemas can be imported"""
    from modules.Chatbot.models.schemas import ChatbotResponse, ChatMessage, Conversation
    assert ChatbotResponse is not None
    assert ChatMessage is not None
    assert Conversation is not None


def test_intent_schema():
    """Test Intent schema"""
    from modules.Chatbot.models.schemas import Intent
    
    intent = Intent(
        name="greeting",
        examples=["hello", "hi"],
        response_template="Hello!"
    )
    
    assert intent.name == "greeting"
    assert "hello" in intent.examples


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
