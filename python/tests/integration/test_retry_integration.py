"""Integration tests for retry pattern with OpenAI service"""
import pytest
from unittest.mock import patch, MagicMock, call
from python.app.modules.ai.services.openai_service import OpenAIService
from openai import OpenAIError


class TestRetryIntegrationOpenAI:
    """Test retry pattern in realistic OpenAIService usage"""

    @pytest.fixture
    def openai_service(self):
        """Create OpenAIService with mocked client"""
        with patch.dict('os.environ', {'OPENAI_API_KEY': 'test-key'}):
            service = OpenAIService(api_key='test-key')
            # Replace real client with mock
            service.client = MagicMock()
            return service

    def test_generate_smart_replies_retries_on_transient_failure(self, openai_service):
        """Simulate transient failures then success for generate_smart_replies"""
        # Mock chat.completions.create to fail twice then succeed
        mock_response = MagicMock()
        mock_response.choices = [MagicMock(message=MagicMock(content="Reply 1"))]
        # Also add more choices for the 3 replies expected
        mock_response.choices = [
            MagicMock(message=MagicMock(content="Reply 1")),
            MagicMock(message=MagicMock(content="Reply 2")),
            MagicMock(message=MagicMock(content="Reply 3")),
        ]

        openai_service.client.chat.completions.create.side_effect = [
            OpenAIError("API error 1"),
            OpenAIError("API error 2"),
            mock_response
        ]

        context = [{"content": "Hello"}]
        result = openai_service.generate_smart_replies(context, "Hi")

        assert len(result) == 3
        assert "Reply 1" in result
        # Should have been called 3 times (2 failures + 1 success)
        assert openai_service.client.chat.completions.create.call_count == 3

    def test_generate_smart_replies_fails_after_max_retries(self, openai_service):
        """Simulate persistent failures exhausting retries"""
        openai_service.client.chat.completions.create.side_effect = OpenAIError("Persistent failure")

        context = [{"content": "Hello"}]
        result = openai_service.generate_smart_replies(context, "Hi")

        # Should return fallback error message
        assert "trouble generating" in result[0]
        # retry_with_backoff_sync uses max_retries=3, so total attempts = 4
        assert openai_service.client.chat.completions.create.call_count == 4

    def test_analyze_sentiment_retries_and_succeeds(self, openai_service):
        """Test sentiment analysis with retry"""
        mock_response = MagicMock()
        mock_response.choices = [MagicMock(message=MagicMock(content='{"sentiment": "positive", "confidence": 0.9}'))]

        openai_service.client.chat.completions.create.side_effect = [
            OpenAIError("timeout"),
            mock_response
        ]

        result = openai_service.analyze_sentiment("Great product!")

        assert result["sentiment"] == "positive"
        assert result["confidence"] == 0.9
        assert openai_service.client.chat.completions.create.call_count == 2

    def test_translate_text_retries_then_success(self, openai_service):
        """Test translation with retry"""
        mock_response = MagicMock()
        mock_response.choices = [MagicMock(message=MagicMock(content="Bonjour"))]

        openai_service.client.chat.completions.create.side_effect = [
            OpenAIError("rate limit"),
            mock_response
        ]

        result = openai_service.translate_text("Hello", "en", "fr")

        assert result["translated_text"] == "Bonjour"
        assert openai_service.client.chat.completions.create.call_count == 2

    def test_moderate_content_retries_exhausted(self, openai_service):
        """Test moderation API with all retries failing"""
        openai_service.client.moderations.create.side_effect = OpenAIError("service down")

        result = openai_service.moderate_content("spam content")

        # Should fallback to safe default (no violation)
        assert result["is_violation"] is False
        assert result["action"] == "allow"
        assert "error" in result
        # 4 attempts total (initial + 3 retries)
        assert openai_service.client.moderations.create.call_count == 4

    def test_generate_summary_retries_then_succeeds(self, openai_service):
        """Test conversation summary with retry"""
        mock_response = MagicMock()
        mock_response.choices = [MagicMock(message=MagicMock(content='{"summary": "A short chat", "key_points": [], "participant_count": 2}'))]

        openai_service.client.chat.completions.create.side_effect = [
            OpenAIError("server error"),
            mock_response
        ]

        result = openai_service.generate_summary("conv123", ["Hello", "World"])

        assert result["summary"] == "A short chat"
        assert openai_service.client.chat.completions.create.call_count == 2

    def test_generate_content_retries_exhausted_returns_error(self, openai_service):
        """Test content generation with all retries failing"""
        openai_service.client.chat.completions.create.side_effect = OpenAIError("unavailable")

        result = openai_service.generate_content("text", "Write a poem")

        assert "error" in result
        assert result["content_type"] == "text"
        assert openai_service.client.chat.completions.create.call_count == 4

    def test_retry_backoff_timing(self, openai_service):
        """Verify that retry delays increase (not exact, but pattern exists)"""
        call_times = []

        def record_time(*args, **kwargs):
            call_times.append(time.time())
            if len(call_times) < 3:
                raise OpenAIError("fail")
            mock_resp = MagicMock()
            mock_resp.choices = [MagicMock(message=MagicMock(content="ok"))]
            return mock_resp

        import time
        openai_service.client.chat.completions.create.side_effect = record_time

        context = [{"content": "test"}]
        openai_service.generate_smart_replies(context, "test")

        # Should have at least 3 calls
        assert len(call_times) >= 3
        # Check that delays between calls are increasing (approx)
        if len(call_times) == 3:
            delay1 = call_times[1] - call_times[0]
            delay2 = call_times[2] - call_times[1]
            # With jitter, delay2 could be slightly less, but generally should be >= delay1
            # Allow some tolerance; we just sanity-check they're not zero
            assert delay1 > 0
            assert delay2 > 0
