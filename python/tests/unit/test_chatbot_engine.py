"""Comprehensive unit tests for ChatbotEngine."""
import sys
import pytest
import json
import os
from datetime import datetime, timezone
from unittest.mock import MagicMock, patch, mock_open

# Ensure we load the real ChatbotEngine module, not mocks from other tests.
sys.modules.pop('modules.chatbot.service.engine', None)
sys.modules.pop('modules.chatbot.models.schemas', None)

from modules.Chatbot.service.engine import ChatbotEngine
from modules.Chatbot.models.schemas import (
    ChatMessage, Conversation, ChatbotResponse, Intent,
    ConversationContext, KnowledgeBaseArticle, Entity
)


@pytest.fixture
def ets_file(tmp_path):
    return str(tmp_path / "chatbot.json")


@pytest.fixture
def engine(ets_file):
    """Engine with heavy I/O methods stubbed to speed up tests."""
    with patch.object(ChatbotEngine, 'ensure_data_directory') as mock_ensure, \
         patch.object(ChatbotEngine, 'restore_from_ets') as mock_restore, \
         patch.object(ChatbotEngine, 'persist_to_ets') as mock_persist:
        eng = ChatbotEngine(ets_file=ets_file)
        eng._mock_ensure = mock_ensure
        eng._mock_restore = mock_restore
        eng._mock_persist = mock_persist
        yield eng


def test_init_creates_intents_and_calls_restore(engine):
    assert "greeting" in engine.intents
    assert "help" in engine.intents
    engine._mock_restore.assert_called_once()


def test_load_default_intents_contains_expected(engine):
    intents = engine.intents
    expected_examples = {
        "greeting": ["hello", "hi", "hey", "good morning", "good evening"],
        "order_food": ["order food", "I want to eat", "hungry", "food delivery"],
        "book_ride": ["book a ride", "taxi", "grab", "driver"]
    }
    for name, examples in expected_examples.items():
        assert name in intents
        assert intents[name].examples == examples


def test_process_message_creates_new_conversation_and_context(engine):
    user_id = "user1"
    conv_id = "conv1"
    message = "hello"
    response = engine.process_message(user_id, conv_id, message)
    assert conv_id in engine.conversations
    conv = engine.conversations[conv_id]
    assert conv.user_id == user_id
    assert len(conv.messages) == 2
    assert isinstance(response, ChatbotResponse)
    assert response.intent == "greeting"
    assert response.message is not None
    assert any(word in response.message.lower() for word in ["hello", "hi", "hey"])


def test_process_message_appends_to_existing_conversation(engine):
    user_id = "user1"
    conv_id = "conv1"
    engine.process_message(user_id, conv_id, "hello")
    response2 = engine.process_message(user_id, conv_id, "order food")
    conv = engine.conversations[conv_id]
    assert len(conv.messages) == 4
    assert conv.messages[-2].content == "order food"
    assert conv.messages[-1].sender_type == "bot"


def test_detect_intent_matches_examples(engine):
    intent, conf, ents = engine._detect_intent("Hello there!")
    assert intent == "greeting"
    assert conf > 0

    intent, conf, ents = engine._detect_intent("I want to order food")
    assert intent == "order_food"

    intent, conf, ents = engine._detect_intent("unknown phrase xyz")
    assert intent == "help"
    assert conf == 0.5


def test_generate_response_uses_template(engine):
    ctx = ConversationContext(user_id="u1")
    response = engine._generate_response("greeting", ctx, [])
    assert "Hello" in response

    ctx.slots["name"] = "John"
    response = engine._generate_response("help", ctx, [])
    assert "I can help you with" in response


def test_generate_response_unknown_intent(engine):
    ctx = ConversationContext(user_id="u1")
    response = engine._generate_response("unknown", ctx, [])
    assert "not sure" in response.lower()


def test_add_knowledge_base_article(engine):
    article_data = {
        "id": "kb1",
        "title": "How to reset password",
        "content": "Go to settings and click reset.",
        "keywords": ["password", "reset"],
        "category": "account"
    }
    engine.add_knowledge_base_article(article_data)
    assert len(engine.knowledge_base) == 1
    kb = engine.knowledge_base[0]
    assert kb.question == "How to reset password"
    assert kb.answer == "Go to settings and click reset."
    assert kb.keywords == ["password", "reset"]


def test_search_knowledge_base_finds_match(engine):
    article = KnowledgeBaseArticle(
        id="kb1",
        question="reset password",
        answer="Go to settings",
        category="account",
        keywords=["password", "reset"],
        confidence=0.9,
        usage_count=0
    )
    engine.knowledge_base.append(article)
    result = engine.search_knowledge_base("how do i reset my password")
    assert result == "Go to settings"


def test_search_knowledge_base_no_match(engine):
    result = engine.search_knowledge_base("something irrelevant")
    assert result is None


def test_ensure_data_directory_creates_when_missing(engine):
    engine._mock_ensure.assert_called_once()


@patch.object(ChatbotEngine, 'ensure_data_directory')
def test_restore_from_ets_reads_valid_data(mock_ensure):
    ets_file = "/fake/path/chatbot.json"
    now_iso = datetime.now(timezone.utc).isoformat()
    data = {
        "conversations": {
            "c1": {
                "id": "c1",
                "user_id": "u1",
                "channel": "chat",
                "status": "active",
                "messages": [
                    {
                        "id": "m1",
                        "sender_type": "user",
                        "content": "hi",
                        "intent": "greeting",
                        "created_at": now_iso
                    }
                ],
                "created_at": now_iso,
                "updated_at": now_iso
            }
        },
        "intents": {
            "greeting": {
                "name": "greeting",
                "examples": ["hello"],
                "response_template": "Hello!",
                "required_entities": [],
                "follow_up_intents": []
            }
        },
        "next_id": 2
    }
    m = mock_open(read_data=json.dumps(data))
    with patch("builtins.open", m), patch("os.path.exists", return_value=True):
        eng = ChatbotEngine(ets_file=ets_file)
    assert "c1" in eng.conversations
    assert eng.conversations["c1"].user_id == "u1"
    assert "greeting" in eng.intents
    assert eng.next_id == 3


@patch.object(ChatbotEngine, 'ensure_data_directory')
def test_restore_from_ets_handles_missing_file(mock_ensure):
    ets_file = "/fake/path/chatbot.json"
    with patch("os.path.exists", return_value=False) as mock_exists:
        eng = ChatbotEngine(ets_file=ets_file)
        mock_exists.assert_called_once_with(ets_file)
    assert len(eng.conversations) == 0
    assert "greeting" in eng.intents  # defaults still there


@patch.object(ChatbotEngine, 'ensure_data_directory')
def test_restore_from_ets_handles_invalid_json(mock_ensure):
    ets_file = "/fake/path/chatbot.json"
    m = mock_open(read_data="invalid json")
    with patch("builtins.open", m), patch("os.path.exists", return_value=True):
        eng = ChatbotEngine(ets_file=ets_file)  # should not raise
    assert len(eng.conversations) == 0


@patch.object(ChatbotEngine, 'ensure_data_directory')
def test_persist_to_ets_writes_correct_data(mock_ensure):
    ets_file = "/fake/path/chatbot.json"
    eng = ChatbotEngine(ets_file=ets_file)
    # Set up some state
    conv = Conversation(
        id="c1", user_id="u1", channel="chat", status="active",
        messages=[],
        created_at=datetime.now(timezone.utc),
        updated_at=datetime.now(timezone.utc)
    )
    eng.conversations["c1"] = conv
    eng.intents["test"] = Intent(name="test", examples=["test"], response_template="test response")
    eng.next_id = 5

    m = mock_open()
    with patch("builtins.open", m), patch("json.dump") as mock_dump:
        eng.persist_to_ets()
        m.assert_called_once_with(ets_file, 'w')
        args, kwargs = mock_dump.call_args
        dumped_data = args[0]
        assert "c1" in dumped_data["conversations"]
        assert dumped_data["conversations"]["c1"]["user_id"] == "u1"
        assert "test" in dumped_data["intents"]
        assert dumped_data["next_id"] == 5


def test_get_conversation_returns_existing_or_none(engine):
    conv = Conversation(id="c1", user_id="u1", channel="chat", status="active", messages=[])
    engine.conversations["c1"] = conv
    assert engine.get_conversation("c1") is conv
    assert engine.get_conversation("nonexistent") is None


def test_end_conversation_closes_and_persists(engine):
    conv = Conversation(id="c1", user_id="u1", channel="chat", status="active", messages=[])
    engine.conversations["c1"] = conv
    engine._mock_persist.reset_mock()
    result = engine.end_conversation("c1")
    assert result["status"] == "closed"
    assert conv.status == "closed"
    engine._mock_persist.assert_called_once()


def test_end_conversation_returns_none_if_not_found(engine):
    result = engine.end_conversation("nonexistent")
    assert result is None


def test_terminate_calls_persist(engine):
    engine._mock_persist.reset_mock()
    engine.terminate()
    engine._mock_persist.assert_called_once()
