"""Unit tests for NLP Service."""
import pytest
from app.modules.nlp.service import NLPService

@pytest.fixture
def nlp_service():
    return NLPService()

@pytest.mark.asyncio
async def test_analyze_sentiment_returns_expected_keys(nlp_service):
    result = await nlp_service.analyze_sentiment("Some text")
    assert "sentiment" in result
    assert "confidence" in result
    assert "scores" in result
    assert result["sentiment"] in ["positive", "negative", "neutral"]
    assert 0 <= result["confidence"] <= 1
    assert isinstance(result["scores"], dict)
    for key in ["positive", "negative", "neutral"]:
        assert key in result["scores"]
        assert 0 <= result["scores"][key] <= 1

@pytest.mark.asyncio
async def test_extract_entities_returns_list(nlp_service):
    result = await nlp_service.extract_entities("Nguyễn Văn A lives in Hà Nội")
    assert isinstance(result, list)
    if result:
        assert "text" in result[0]
        assert "label" in result[0]
        assert "start" in result[0]
        assert "end" in result[0]

@pytest.mark.asyncio
async def test_extract_keywords_returns_list_of_dicts(nlp_service):
    result = await nlp_service.extract_keywords("Sample text for analysis")
    assert isinstance(result, list)
    if result:
        assert "word" in result[0]
        assert "score" in result[0]
        assert isinstance(result[0]["word"], str)
        assert isinstance(result[0]["score"], float)

@pytest.mark.asyncio
async def test_translate_returns_translated_text(nlp_service):
    text = "Hello world"
    result = await nlp_service.translate(text, "vi")
    assert isinstance(result, str)
    assert "Translated to vi" in result
    assert text in result

@pytest.mark.asyncio
async def test_summarize_short_text_unchanged(nlp_service):
    text = "Short text"
    result = await nlp_service.summarize(text, 100)
    assert result == text

@pytest.mark.asyncio
async def test_summarize_long_text_truncated(nlp_service):
    text = "This is a longer text that should be truncated by the summarizer."
    result = await nlp_service.summarize(text, 20)
    assert result.startswith(text[:20])
    assert result.endswith("...")

@pytest.mark.asyncio
async def test_classify_returns_known_category(nlp_service):
    result = await nlp_service.classify("Some news article")
    assert result in ["news", "sports", "technology", "entertainment", "business"]

@pytest.mark.asyncio
async def test_detect_language_returns_language_code(nlp_service):
    result = await nlp_service.detect_language("Hello")
    assert result in ["vi", "en", "zh", "ja", "ko", "fr", "de"]
