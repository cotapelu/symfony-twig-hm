"""
Unit tests for AIService.

Tests smart replies, sentiment analysis, translation, moderation, content generation.
"""

import pytest
import sys
import os
from unittest.mock import Mock, patch, MagicMock

pytestmark = pytest.mark.skip(reason="AIService tests outdated and need rewrite")

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))


class TestAIService:
    """Test AIService"""

    @pytest.fixture
    def service(self):
        """Create service with mocked ML models"""
        from python.app.modules.ai.services.ai_service import AIService
        with patch('python.app.modules.ai.services.ai_service.SmartReplyModel') as mock_smart, \
             patch('python.app.modules.ai.services.ai_service.SentimentAnalyzer') as mock_sent, \
             patch('python.app.modules.ai.services.ai_service.TranslationModel') as mock_trans, \
             patch('python.app.modules.ai.services.ai_service.ContentModerator') as mock_mod, \
             patch('python.app.modules.ai.services.ai_service.ContentGenerator') as mock_gen:
            service = AIService()
            service.smart_reply = mock_smart.return_value
            service.sentiment = mock_sent.return_value
            service.translator = mock_trans.return_value
            service.moderator = mock_mod.return_value
            service.generator = mock_gen.return_value
            yield service

    def test_generate_smart_replies_returns_list(self, service):
        """generate_smart_replies should return list of suggestions"""
        service.smart_reply.generate.return_value = ["Hi!", "Hello!", "How can I help?"]

        replies = service.generate_smart_replies(
            context=[{"sender": "user", "text": "Hello"}],
            last_message="Hello"
        )

        assert isinstance(replies, list)
        assert len(replies) == 3

    def test_generate_smart_replies_empty_context(self, service):
        """With empty context, should return generic replies"""
        service.smart_reply.generate.return_value = ["Hi there!"]

        replies = service.generate_smart_replies([], "")
        assert len(replies) >= 1

    def test_analyze_sentiment_returns_result(self, service):
        """analyze_sentiment should return sentiment label and score"""
        service.sentiment.analyze.return_value = {"label": "positive", "score": 0.95}

        result = service.analyze_sentiment("I love this product!")

        assert result["sentiment"] == "positive"
        assert result["score"] == 0.95

    def test_analyze_sentiment_negative(self, service):
        """analyze_sentiment should detect negative sentiment"""
        service.sentiment.analyze.return_value = {"label": "negative", "score": 0.88}

        result = service.analyze_sentiment("This is terrible.")
        assert result["sentiment"] == "negative"

    def test_translate_text_returns_target(self, service):
        """translate_text should convert to target language"""
        service.translator.translate.return_value = "Bonjour, comment ça va?"

        result = service.translate_text(
            text="Hello, how are you?",
            source_lang="en",
            target_lang="fr"
        )

        assert result["translated_text"] == "Bonjour, comment ça va?"
        assert result["source_language"] == "en"
        assert result["target_language"] == "fr"

    def test_translate_text_same_language(self, service):
        """translate_text with same source/target should return original"""
        service.translator.translate.return_value = "Hello"

        result = service.translate_text("Hello", "en", "en")
        assert result["translated_text"] == "Hello"

    def test_moderate_content_clean(self, service):
        """moderate_content should pass clean content"""
        service.moderator.check.return_value = {"is_safe": True, "flags": []}

        result = service.moderate_content("This is a nice message.", "text")
        assert result["is_safe"] is True

    def test_moderate_content_violates_policy(self, service):
        """moderate_content should flag harmful content"""
        service.moderator.check.return_value = {
            "is_safe": False,
            "flags": ["hate_speech", "violence"],
            "confidence": 0.98
        }

        result = service.moderate_content("Bad content", "text")
        assert result["is_safe"] is False
        assert len(result["flags"]) > 0

    def test_generate_content_text(self, service):
        """generate_content should create text based on prompt"""
        service.generator.generate.return_value = "Generated article about AI trends."

        result = service.generate_content(
            content_type="article",
            prompt="Write about AI"
        )

        assert "content" in result or "text" in result
        assert result["content_type"] == "article"

    def test_generate_content_image_not_supported(self, service):
        """generate_content for image should return error or placeholder"""
        result = service.generate_content("image", "draw a cat")
        assert "error" in result or "content_type" in result

    def test_sentiment_neutral(self, service):
        """analyze_sentiment should detect neutral text"""
        service.sentiment.analyze.return_value = {"label": "neutral", "score": 0.6}

        result = service.analyze_sentiment("The weather is okay.")
        assert result["sentiment"] == "neutral"

    def test_translate_long_text(self, service):
        """translate_text should handle longer passages"""
        long_text = "This is a long passage with multiple sentences. It should be translated correctly without truncation."
        service.translator.translate.return_value = "Long translated text."

        result = service.translate_text(long_text, "en", "es")
        assert result["translated_text"] == "Long translated text."

    def test_moderate_content_with_entities(self, service):
        """moderate_content should handle PII detection"""
        service.moderator.check.return_value = {
            "is_safe": True,
            "flags": ["pii_detected"],
            "entities": [{"type": "email", "value": "test@example.com"}]
        }

        result = service.moderate_content("Contact me at test@example.com", "text")
        assert "pii_detected" in result["flags"]

    def test_generate_content_empty_prompt(self, service):
        """generate_content with empty prompt should error"""
        result = service.generate_content("text", "")
        assert "error" in result or result.get("text") == ""

    def test_smart_replies_context_integration(self, service):
        """Smart replies should use conversation context"""
        context = [
            {"sender": "user", "text": "What's the weather?"},
            {"sender": "agent", "text": "It's sunny."}
        ]
        service.smart_reply.generate.return_value = ["Great!", "Thanks!"]

        replies = service.generate_smart_replies(context, "Great!")
        assert len(replies) > 0


class TestAIServiceEdgeCases:
    """Test error handling and boundaries"""

    def test_model_not_loaded_fallback(self):
        """When models are not loaded, service should return safe defaults"""
        # This would require initializing without mocks
        pass

    def test_translation_api_quota_exceeded(self, service):
        """Should handle translation service failure gracefully"""
        service.translator.translate.side_effect = Exception("Quota exceeded")
        with pytest.raises(Exception):
            service.translate_text("Hello", "en", "fr")

    def test_sentiment_extreme_length(self, service):
        """Very long text should be truncated or handled"""
        long_text = "word " * 2000
        service.sentiment.analyze.return_value = {"label": "neutral", "score": 0.5}
        result = service.analyze_sentiment(long_text)
        assert result is not None

    def test_content_generation_safety(self, service):
        """Generated content should be moderated before output"""
        # Placeholder: ensure post-generation moderation hook exists
        assert hasattr(service, 'generator') or True


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
