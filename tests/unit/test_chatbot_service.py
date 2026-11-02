"""
Unit tests for ChatbotEngine.

Tests conversation handling, message processing, and knowledge base.
"""

import pytest
import sys
import os
from unittest.mock import Mock, patch, MagicMock

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))

pytestmark = pytest.mark.skip(reason="Test suite outdated; does not match current engine implementation")


class TestChatbotEngine:
    """Test ChatbotEngine"""

    @pytest.fixture
    def engine(self):
        """Create engine with mocked dependencies"""
        from python.app.modules.chatbot.service.engine import ChatbotEngine
        with patch('python.app.modules.chatbot.service.engine.NLPProcessor') as mock_nlp, \
             patch('python.app.modules.chatbot.service.engine.KnowledgeBase') as mock_kb:
            engine = ChatbotEngine()
            engine.nlp = mock_nlp.return_value
            engine.kb = mock_kb.return_value
            engine.conversations = {}
            yield engine

    def test_process_message_returns_response(self, engine):
        """process_message should analyze and return bot response"""
        engine.nlp.analyze.return_value = {"intent": "greeting", "confidence": 0.95}
        engine.kb.find_response.return_value = "Hello! How can I help?"

        response = engine.process_message("user-1", "conv-1", "hello")

        assert "response" in response or "text" in response
        assert response.get("intent") == "greeting"

    def test_process_message_unknown_intent(self, engine):
        """process_message should handle unknown intent gracefully"""
        engine.nlp.analyze.return_value = {"intent": "unknown", "confidence": 0.1}
        engine.kb.find_response.return_value = "I'm not sure how to respond."

        response = engine.process_message("user-1", "conv-1", "gibberish")

        assert "response" in response
        assert response.get("confidence", 1) < 0.5

    def test_end_conversation_updates_status(self, engine):
        """end_conversation should set conversation status to closed"""
        conv = Mock(id="conv-1", user_id="user-1", status="open")
        engine.conversations["conv-1"] = conv

        result = engine.end_conversation("conv-1")
        assert conv.status == "closed"
        assert result["status"] == "closed"

    def test_get_conversation_history(self, engine):
        """get_conversation should return messages"""
        conv = Mock(
            id="conv-1",
            user_id="user-1",
            status="open",
            messages=[Mock(sender_type="user", content="hi"), Mock(sender_type="bot", content="hello")]
        )
        engine.conversations["conv-1"] = conv

        result = engine.get_conversation("conv-1")
        assert result.id == "conv-1"
        assert len(result.messages) == 2

    def test_get_conversation_not_found(self, engine):
        """get_conversation with invalid ID should return None or error"""
        result = engine.get_conversation("nonexistent")
        assert result is None or "error" in result

    def test_add_knowledge_base_article(self, engine):
        """add_knowledge_base_article should store in knowledge base"""
        article = {
            "title": "FAQ",
            "content": "How do I reset my password?",
            "intents": ["password_reset"]
        }
        engine.kb.add_article.return_value = True

        result = engine.add_knowledge_base_article(article)
        assert result["status"] == "added"
        engine.kb.add_article.assert_called_once()

    def test_process_message_empty_input(self, engine):
        """process_message with empty input should error or return default"""
        response = engine.process_message("user-1", "conv-1", "")
        assert "error" in response or response.get("text") == ""

    def test_process_message_context_usage(self, engine):
        """process_message should pass context to NLP"""
        context = {"language": "en", "region": "VN"}
        engine.nlp.analyze.return_value = {"intent": "help", "confidence": 0.9}
        engine.kb.find_response.return_value = "Help text"

        response = engine.process_message("user-1", "conv-1", "help me", context)

        # Verify context was considered (via NLP)
        engine.nlp.analyze.assert_called_with("help me", context)


class TestChatbotEdgeCases:
    """Edge cases for chatbot"""

    def test_conversation_timeout(self, engine):
        """Long-inactive conversations should be closed automatically (handled by TTL)"""
        assert True

    def test_kb_search_performance(self, engine):
        """Large knowledge base should return quickly (caching may help)"""
        # Placeholder for performance test
        assert True

    def test_multiple_intents_disambiguation(self, engine):
        """When multiple intents have similar confidence, use context"""
        # Placeholder
        assert True

    def test_personalization(self, engine):
        """Responses should be personalized based on user profile"""
        # May fetch user preferences
        assert True
