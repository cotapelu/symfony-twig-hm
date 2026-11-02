"""Tests for retry logic in OpenAIService."""
import pytest
from unittest.mock import MagicMock, patch
from openai import OpenAIError

from app.modules.ai.services.openai_service import OpenAIService


def create_mock_response(content: str) -> MagicMock:
    """Create a mock OpenAI response with given content."""
    choice = MagicMock()
    choice.message.content = content
    response = MagicMock()
    response.choices = [choice]
    return response


def test_generate_smart_replies_retries_on_failure(monkeypatch):
    """Test that generate_smart_replies retries on OpenAIError and eventually succeeds."""
    # Patch time.sleep to avoid delays in tests
    monkeypatch.setattr("time.sleep", lambda x: None)

    service = OpenAIService(api_key="dummy")

    # Mock client with side_effect: two errors then success
    mock_client = MagicMock()
    mock_client.chat.completions.create = MagicMock(
        side_effect=[
            OpenAIError("fail 1"),
            OpenAIError("fail 2"),
            create_mock_response("Hello! How can I help?")
        ]
    )
    service.client = mock_client

    context = [{"role": "user", "content": "previous"}]
    result = service.generate_smart_replies(context, "test message")

    assert result == ["Hello! How can I help?"]
    assert mock_client.chat.completions.create.call_count == 3


def test_analyze_sentiment_retries_on_failure(monkeypatch):
    """Test analyze_sentiment retries correctly."""
    monkeypatch.setattr("time.sleep", lambda x: None)

    service = OpenAIService(api_key="dummy")
    mock_client = MagicMock()
    mock_client.chat.completions.create = MagicMock(
        side_effect=[
            OpenAIError("timeout"),
            create_mock_response('{"sentiment": "positive", "confidence": 0.9}')
        ]
    )
    service.client = mock_client

    result = service.analyze_sentiment("I love this!")

    assert result.get("sentiment") == "positive"
    assert result.get("confidence") == 0.9
    assert mock_client.chat.completions.create.call_count == 2


def test_translate_text_retries_on_failure(monkeypatch):
    """Test translate_text retries correctly."""
    monkeypatch.setattr("time.sleep", lambda x: None)

    service = OpenAIService(api_key="dummy")
    mock_client = MagicMock()
    mock_client.chat.completions.create = MagicMock(
        side_effect=[
            OpenAIError("error"),
            create_mock_response("Hola")
        ]
    )
    service.client = mock_client

    result = service.translate_text("Hello", "en", "es")

    assert result["translated_text"] == "Hola"
    assert result["source_language"] == "en"
    assert result["target_language"] == "es"
    assert mock_client.chat.completions.create.call_count == 2


def test_moderate_content_retries_on_failure(monkeypatch):
    """Test moderate_content retries correctly."""
    monkeypatch.setattr("time.sleep", lambda x: None)

    service = OpenAIService(api_key="dummy")
    mock_client = MagicMock()
    # Create mock moderation result
    mod_result = MagicMock()
    mod_result.results = [MagicMock(flagged=False, categories=MagicMock(model_dump=lambda: {}))]
    mock_client.moderations.create = MagicMock(
        side_effect=[
            OpenAIError("fail"),
            mod_result
        ]
    )
    service.client = mock_client

    result = service.moderate_content("test", "text")

    assert result["is_violation"] is False
    assert result["action"] == "allow"
    assert mock_client.moderations.create.call_count == 2


def test_generate_summary_retries_on_failure(monkeypatch):
    """Test generate_summary retries correctly."""
    monkeypatch.setattr("time.sleep", lambda x: None)

    service = OpenAIService(api_key="dummy")
    mock_client = MagicMock()
    mock_client.chat.completions.create = MagicMock(
        side_effect=[
            OpenAIError("error"),
            create_mock_response('{"summary": "Great discussion", "key_points": [], "action_items": []}')
        ]
    )
    service.client = mock_client

    result = service.generate_summary("conv123", ["Hello", "World"])

    assert result["summary"] == "Great discussion"
    assert mock_client.chat.completions.create.call_count == 2


def test_generate_content_retries_on_failure(monkeypatch):
    """Test generate_content retries correctly."""
    monkeypatch.setattr("time.sleep", lambda x: None)

    service = OpenAIService(api_key="dummy")
    mock_client = MagicMock()
    mock_client.chat.completions.create = MagicMock(
        side_effect=[
            OpenAIError("error"),
            create_mock_response("Generated code")
        ]
    )
    service.client = mock_client

    result = service.generate_content("code", "print hello")

    assert result["generated_content"] == "Generated code"
    assert mock_client.chat.completions.create.call_count == 2
