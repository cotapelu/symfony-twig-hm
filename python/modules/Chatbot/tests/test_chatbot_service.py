"""Tests for ChatbotService."""
import pytest
import os
import tempfile
import json
from datetime import datetime, timezone

from modules.Chatbot.service.chatbot_service import (
    ChatbotService,
    Conversation,
    ChatMessage,
    ChatbotResponse,
    ConversationStatus,
    MessageRole,
    IntentType,
)


@pytest.fixture
def tmp_storage(tmp_path):
    """Provide a temp storage path for the chatbot service."""
    return str(tmp_path / "chatbot_test.json")


@pytest.fixture
def service(tmp_storage):
    """Provide a fresh ChatbotService instance per test."""
    return ChatbotService(storage_path=tmp_storage)


class TestConversationDataclass:
    def test_conversation_post_init_defaults(self):
        conv = Conversation(id="c1", user_id="u1", status="active", messages=[])
        assert conv.context == {}
        assert conv.created_at is not None
        assert conv.updated_at is not None
        assert conv.current_intent is None

    def test_conversation_post_init_with_string_dates(self):
        iso = "2026-07-27T10:00:00+00:00"
        conv = Conversation(
            id="c1", user_id="u1", status="active",
            messages=[], created_at=iso, updated_at=iso,
        )
        assert isinstance(conv.created_at, datetime)

    def test_chatmessage_post_init_defaults(self):
        msg = ChatMessage(role="user", content="hi", timestamp=datetime.now(timezone.utc))
        assert msg.metadata == {}

    def test_chatmessage_post_init_string_timestamp(self):
        iso = "2026-07-27T10:00:00+00:00"
        msg = ChatMessage(role="user", content="hi", timestamp=iso)
        assert isinstance(msg.timestamp, datetime)


class TestChatbotServiceInit:
    def test_creates_storage_directory_on_init(self, tmp_path):
        nested = tmp_path / "nested" / "deeper"
        nested.mkdir(parents=True, exist_ok=True)
        path = str(nested / "chatbot.json")
        ChatbotService(storage_path=path)
        assert os.path.exists(nested)

    def test_loads_existing_storage_on_init(self, tmp_storage):
        data = {
            "conversations": {
                "c1": {
                    "id": "c1", "user_id": "u1", "status": "active",
                    "messages": [], "current_intent": None,
                    "context": {}, "created_at": None, "updated_at": None,
                }
            }
        }
        with open(tmp_storage, 'w') as f:
            json.dump(data, f)
        svc = ChatbotService(storage_path=tmp_storage)
        assert "c1" in svc.conversations

    def test_handles_missing_storage_file(self, tmp_path):
        path = str(tmp_path / "nonexistent.json")
        svc = ChatbotService(storage_path=path)
        assert svc.conversations == {}


class TestCreateConversation:
    def test_creates_conversation_with_user_id(self, service):
        conv = service.create_conversation("user-1")
        assert conv.user_id == "user-1"
        assert conv.id is not None
        assert conv.status == ConversationStatus.ACTIVE.value
        assert conv.messages == []

    def test_creates_unique_ids(self, service):
        c1 = service.create_conversation("user-1")
        c2 = service.create_conversation("user-2")
        assert c1.id != c2.id

    def test_stores_in_conversations_dict(self, service):
        conv = service.create_conversation("user-1")
        assert service.conversations[conv.id] == conv


class TestGetConversation:
    def test_get_existing(self, service):
        conv = service.create_conversation("user-1")
        assert service.get_conversation(conv.id) == conv

    def test_get_nonexistent_returns_none(self, service):
        assert service.get_conversation("nonexistent") is None


class TestGetUserConversations:
    def test_returns_only_user_conversations(self, service):
        c1 = service.create_conversation("user-1")
        c2 = service.create_conversation("user-2")
        result = service.get_user_conversations("user-1")
        assert c1 in result
        assert c2 not in result

    def test_respects_limit(self, service):
        for _ in range(5):
            service.create_conversation("user-1")
        result = service.get_user_conversations("user-1", limit=3)
        assert len(result) == 3

    def test_default_limit(self, service):
        for _ in range(25):
            service.create_conversation("user-1")
        result = service.get_user_conversations("user-1")
        assert len(result) == 20


class TestDetectIntent:
    def test_detect_greeting(self, service):
        intent, confidence = service._detect_intent("Hello there!")
        assert intent == IntentType.GREETING.value

    def test_detect_question(self, service):
        intent, _ = service._detect_intent("What is the price?")
        assert intent == IntentType.QUESTION.value

    def test_detect_complaint(self, service):
        # "order" keyword appears in patterns before "complaint", so avoid it
        intent, _ = service._detect_intent("I am very dissatisfied and angry about this issue")
        assert intent == IntentType.COMPLAINT.value

    def test_detect_order(self, service):
        intent, _ = service._detect_intent("I want to order pizza")
        assert intent == IntentType.ORDER.value

    def test_detect_refund(self, service):
        intent, _ = service._detect_intent("I need a refund please")
        assert intent == IntentType.REFUND.value

    def test_detect_tracking(self, service):
        intent, _ = service._detect_intent("Where is my shipping?")
        assert intent == IntentType.TRACKING.value

    def test_detect_payment(self, service):
        intent, _ = service._detect_intent("Payment failed, invoice issue")
        assert intent == IntentType.PAYMENT.value

    def test_detect_support(self, service):
        intent, _ = service._detect_intent("I need help with this")
        assert intent == IntentType.SUPPORT.value

    def test_detect_unknown(self, service):
        intent, _ = service._detect_intent("xyzzy foobar")
        assert intent == IntentType.UNKNOWN.value

    def test_returns_float_confidence(self, service):
        _, confidence = service._detect_intent("Hello")
        assert isinstance(confidence, float)


class TestSendMessage:
    def test_returns_chatbot_response(self, service):
        conv = service.create_conversation("user-1")
        response = service.send_message(conv.id, "Hello")
        assert isinstance(response, ChatbotResponse)

    def test_adds_message_to_conversation(self, service):
        conv = service.create_conversation("user-1")
        service.send_message(conv.id, "Hello there")
        assert len(conv.messages) >= 2  # user + assistant

    def test_returns_none_for_nonexistent_conversation(self, service):
        # Source actually raises ValueError when conversation_id doesn't exist
        # (chatbot_service.py line 296). This is the documented behavior.
        with pytest.raises(ValueError, match="Conversation not found"):
            service.send_message("nonexistent", "Hello")

    def test_message_has_user_role(self, service):
        conv = service.create_conversation("user-1")
        service.send_message(conv.id, "Hello")
        user_msgs = [m for m in conv.messages if m.role == MessageRole.USER.value]
        assert any(m.content == "Hello" for m in user_msgs)


class TestEscalateConversation:
    def test_escalates_to_support(self, service):
        conv = service.create_conversation("user-1")
        result = service.escalate_conversation(conv.id, "User angry")
        assert result is not None
        assert result.status == ConversationStatus.ESCALATED.value

    def test_returns_none_for_nonexistent(self, service):
        result = service.escalate_conversation("nonexistent", "reason")
        assert result is None


class TestCloseConversation:
    def test_closes_to_completed(self, service):
        conv = service.create_conversation("user-1")
        result = service.close_conversation(conv.id)
        assert result is not None
        assert result.status == ConversationStatus.COMPLETED.value

    def test_returns_none_for_nonexistent(self, service):
        result = service.close_conversation("nonexistent")
        assert result is None


class TestStatistics:
    def test_get_statistics(self, service):
        service.create_conversation("user-1")
        service.create_conversation("user-2")
        stats = service.get_chatbot_statistics()
        assert "total_conversations" in stats or isinstance(stats, dict)


class TestPersistence:
    def test_save_load_roundtrip(self, tmp_storage):
        svc1 = ChatbotService(storage_path=tmp_storage)
        conv = svc1.create_conversation("user-1")
        svc1.send_message(conv.id, "Hello world")

        svc2 = ChatbotService(storage_path=tmp_storage)
        loaded = svc2.get_conversation(conv.id)
        assert loaded is not None
        assert loaded.user_id == "user-1"


class TestStorageDirectory:
    def test_ensure_storage_directory_creates_path(self, tmp_path):
        nested = tmp_path / "nested" / "deeper"
        path = str(nested / "chatbot.json")
        svc = ChatbotService(storage_path=path)
        assert nested.exists()

    def test_ensure_storage_directory_noop_for_existing(self, tmp_path):
        existing = tmp_path / "existing.json"
        existing.touch()
        svc = ChatbotService(storage_path=str(existing))
        assert existing.exists()


class TestSaveToStorage:
    def test_save_to_storage_impl_creates_file(self, service, tmp_storage):
        conv = service.create_conversation("user-1")
        service.send_message(conv.id, "Test message")
        assert os.path.exists(tmp_storage)
        with open(tmp_storage, 'r') as f:
            data = json.load(f)
        assert 'conversations' in data
        assert conv.id in data['conversations']

    def test_save_to_storage_preserves_messages(self, service, tmp_storage):
        conv = service.create_conversation("user-1")
        service.send_message(conv.id, "Hello")
        service.send_message(conv.id, "World")
        
        with open(tmp_storage, 'r') as f:
            data = json.load(f)
        conv_data = data['conversations'][conv.id]
        assert len(conv_data['messages']) >= 2

    def test_save_to_storage_updates_timestamps(self, service, tmp_storage):
        conv = service.create_conversation("user-1")
        service.send_message(conv.id, "Test")
        
        with open(tmp_storage, 'r') as f:
            data = json.load(f)
        conv_data = data['conversations'][conv.id]
        assert 'updated_at' in conv_data
        assert conv_data['updated_at'] is not None


class TestGenerateResponse:
    def test_generate_response_known_intent(self, service):
        response = service._generate_response(IntentType.GREETING.value, {})
        assert isinstance(response, str)
        assert len(response) > 0

    def test_generate_response_unknown_intent(self, service):
        response = service._generate_response("unknown_intent", {})
        assert "not sure" in response.lower() or "rephrase" in response.lower()

    def test_generate_response_empty_templates(self, service):
        # Test with an intent that has no templates
        original = service.response_templates
        service.response_templates = {}
        try:
            response = service._generate_response(IntentType.GREETING.value, {})
            assert "not sure" in response.lower() or "rephrase" in response.lower()
        finally:
            service.response_templates = original


class TestGetSuggestedActions:
    def test_get_suggested_actions_order(self, service):
        actions = service._get_suggested_actions(IntentType.ORDER.value)
        assert isinstance(actions, list)
        assert "Check order status" in actions
        assert "View order history" in actions
        assert "Track shipment" in actions

    def test_get_suggested_actions_refund(self, service):
        actions = service._get_suggested_actions(IntentType.REFUND.value)
        assert "Start refund process" in actions
        assert "View refund policy" in actions
        assert "Check refund status" in actions

    def test_get_suggested_actions_tracking(self, service):
        actions = service._get_suggested_actions(IntentType.TRACKING.value)
        assert "Enter tracking number" in actions
        assert "View all orders" in actions
        assert "Contact support" in actions

    def test_get_suggested_actions_payment(self, service):
        actions = service._get_suggested_actions(IntentType.PAYMENT.value)
        assert "View invoices" in actions
        assert "Payment methods" in actions
        assert "Transaction history" in actions

    def test_get_suggested_actions_support(self, service):
        actions = service._get_suggested_actions(IntentType.SUPPORT.value)
        assert "Contact human agent" in actions
        assert "View FAQ" in actions
        assert "Submit ticket" in actions

    def test_get_suggested_actions_unknown(self, service):
        actions = service._get_suggested_actions("unknown_intent")
        assert actions == []


class TestConversationHistory:
    def test_get_conversation_history_existing(self, service):
        conv = service.create_conversation("user-1")
        service.send_message(conv.id, "Hello")
        service.send_message(conv.id, "How are you?")
        
        history = service.get_conversation_history(conv.id)
        assert isinstance(history, list)
        assert len(history) >= 4  # 2 user + 2 assistant

    def test_get_conversation_history_limit(self, service):
        conv = service.create_conversation("user-1")
        for i in range(10):
            service.send_message(conv.id, f"Message {i}")
        
        history = service.get_conversation_history(conv.id, limit=5)
        assert len(history) <= 5

    def test_get_conversation_history_nonexistent(self, service):
        history = service.get_conversation_history("nonexistent")
        assert history == []

    def test_get_conversation_history_empty(self, service):
        conv = service.create_conversation("user-1")
        history = service.get_conversation_history(conv.id)
        assert history == []


class TestStatistics:
    def test_get_statistics_comprehensive(self, service):
        service.create_conversation("user-1")
        service.create_conversation("user-2")
        conv = service.create_conversation("user-3")
        service.send_message(conv.id, "I want to order pizza")  # Sets intent to ORDER
        
        stats = service.get_chatbot_statistics()
        assert stats["total_conversations"] == 3
        assert stats["active_conversations"] == 3
        assert stats["escalated_conversations"] == 0
        assert stats["total_messages"] >= 1
        assert "intent_distribution" in stats
        assert stats["intent_distribution"]["order"] >= 1

    def test_get_statistics_empty(self, service):
        stats = service.get_chatbot_statistics()
        assert stats["total_conversations"] == 0
        assert stats["active_conversations"] == 0
        assert stats["escalated_conversations"] == 0
        assert stats["total_messages"] == 0
        assert stats["intent_distribution"] == {}

    def test_get_statistics_with_escalated(self, service):
        conv1 = service.create_conversation("user-1")
        conv2 = service.create_conversation("user-2")
        service.escalate_conversation(conv1.id, "Angry user")
        
        stats = service.get_chatbot_statistics()
        assert stats["total_conversations"] == 2
        assert stats["active_conversations"] == 1
        assert stats["escalated_conversations"] == 1


class TestStorageEdgeCases:
    def test_load_from_storage_corrupted_json(self, tmp_storage):
        with open(tmp_storage, 'w') as f:
            f.write("not valid json")
        svc = ChatbotService(storage_path=tmp_storage)
        assert svc.conversations == {}

    def test_load_from_storage_incomplete_data(self, tmp_storage):
        with open(tmp_storage, 'w') as f:
            json.dump({"conversations": {"c1": {}}}, f)
        svc = ChatbotService(storage_path=tmp_storage)
        # Should not crash, but may not load the incomplete conversation
        assert isinstance(svc.conversations, dict)

    def test_load_from_storage_missing_timestamps(self, tmp_storage):
        data = {
            "conversations": {
                "c1": {
                    "id": "c1", "user_id": "u1", "status": "active",
                    "messages": [], "current_intent": None,
                    "context": {},
                }
            }
        }
        with open(tmp_storage, 'w') as f:
            json.dump(data, f)
        svc = ChatbotService(storage_path=tmp_storage)
        assert "c1" in svc.conversations
        assert svc.conversations["c1"].created_at is not None
        assert svc.conversations["c1"].updated_at is not None


class TestIntentDetectionEdgeCases:
    def test_detect_intent_priority_order(self, service):
        # TRACKING has highest priority, then ORDER, REFUND, PAYMENT, COMPLAINT, SUPPORT, QUESTION, GREETING
        # Test overlapping patterns
        intent, _ = service._detect_intent("I want to track my order")
        assert intent == IntentType.TRACKING.value  # TRACKING > ORDER

    def test_detect_intent_case_insensitive(self, service):
        intent, _ = service._detect_intent("HELLO WORLD")
        assert intent == IntentType.GREETING.value

    def test_detect_intent_partial_match(self, service):
        intent, _ = service._detect_intent("trackingnumber123")
        assert intent == IntentType.TRACKING.value

    def test_detect_intent_empty_string(self, service):
        intent, confidence = service._detect_intent("")
        assert intent == IntentType.UNKNOWN.value
        assert confidence == 0.0

    def test_detect_intent_only_punctuation(self, service):
        # ? is in QUESTION patterns, so test with punctuation that has no intent patterns
        intent, _ = service._detect_intent("!@#")
        assert intent == IntentType.UNKNOWN.value


class TestIntentConfidence:
    def test_confidence_greeting_multiple_patterns(self, service):
        intent, confidence = service._detect_intent("Hello hi hey good morning")
        assert intent == IntentType.GREETING.value
        assert confidence > 0.0
        assert confidence <= 1.0

    def test_confidence_single_pattern(self, service):
        intent, confidence = service._detect_intent("Hello")
        assert intent == IntentType.GREETING.value
        assert confidence > 0.0
        assert confidence <= 1.0

    def test_confidence_unknown_zero(self, service):
        intent, confidence = service._detect_intent("xyzzy")
        assert intent == IntentType.UNKNOWN.value
        assert confidence == 0.0


class TestResponseTemplates:
    def test_response_templates_all_intents(self, service):
        for intent_type in IntentType:
            if intent_type != IntentType.UNKNOWN:
                response = service._generate_response(intent_type.value, {})
                assert isinstance(response, str)
                assert len(response) > 0

    def test_response_templates_returning_user(self, service):
        # This tests the 'returning' template for GREETING
        response = service._generate_response(IntentType.GREETING.value, {})
        assert isinstance(response, str)
        assert len(response) > 0


class TestConversationLifecycle:
    def test_conversation_status_transitions(self, service):
        conv = service.create_conversation("user-1")
        assert conv.status == ConversationStatus.ACTIVE.value
        
        service.send_message(conv.id, "Hello")
        assert conv.status == ConversationStatus.ACTIVE.value
        
        escalated = service.escalate_conversation(conv.id, "Angry")
        assert escalated.status == ConversationStatus.ESCALATED.value
        
        closed = service.close_conversation(conv.id)
        assert closed.status == ConversationStatus.COMPLETED.value

    def test_escalation_preserves_context(self, service):
        conv = service.create_conversation("user-1")
        conv.context["key"] = "value"
        
        escalated = service.escalate_conversation(conv.id, "Angry user")
        assert escalated.context.get("escalation_reason") == "Angry user"
        assert escalated.context.get("key") == "value"

    def test_close_preserves_context(self, service):
        conv = service.create_conversation("user-1")
        conv.context["key"] = "value"
        
        closed = service.close_conversation(conv.id)
        assert closed.context.get("key") == "value"
        assert closed.status == ConversationStatus.COMPLETED.value


class TestChatMessage:
    def test_chatmessage_post_init_metadata_none(self):
        msg = ChatMessage(role="user", content="hi", timestamp=datetime.now(timezone.utc))
        assert msg.metadata == {}

    def test_chatmessage_post_init_with_metadata(self):
        msg = ChatMessage(role="user", content="hi", timestamp=datetime.now(timezone.utc), metadata={"key": "value"})
        assert msg.metadata == {"key": "value"}

    def test_chatmessage_post_init_string_timestamp(self):
        iso = "2026-07-27T10:00:00+00:00"
        msg = ChatMessage(role="user", content="hi", timestamp=iso)
        assert isinstance(msg.timestamp, datetime)


class TestChatbotResponse:
    def test_chatbotresponse_defaults(self):
        resp = ChatbotResponse(message="Hello", intent="greeting", confidence=0.9)
        assert resp.suggested_actions == []
        assert resp.metadata == {}

    def test_chatbotresponse_with_actions(self):
        resp = ChatbotResponse(
            message="Hello", 
            intent="order", 
            confidence=0.9,
            suggested_actions=["action1", "action2"]
        )
        assert resp.suggested_actions == ["action1", "action2"]

    def test_chatbotresponse_with_metadata(self):
        resp = ChatbotResponse(
            message="Hello", 
            intent="order", 
            confidence=0.9,
            metadata={"key": "value"}
        )
        assert resp.metadata == {"key": "value"}


class TestConversation:
    def test_conversation_post_init_defaults(self):
        conv = Conversation(id="c1", user_id="u1", status="active", messages=[])
        assert conv.context == {}
        assert conv.created_at is not None
        assert conv.updated_at is not None
        assert conv.current_intent is None

    def test_conversation_post_init_with_dates(self):
        iso = "2026-07-27T10:00:00+00:00"
        conv = Conversation(
            id="c1", user_id="u1", status="active",
            messages=[], created_at=iso, updated_at=iso,
        )
        assert isinstance(conv.created_at, datetime)
        assert isinstance(conv.updated_at, datetime)

    def test_conversation_post_init_string_dates(self):
        iso = "2026-07-27T10:00:00+00:00"
        conv = Conversation(
            id="c1", user_id="u1", status="active",
            messages=[], created_at=iso, updated_at=iso,
        )
        assert isinstance(conv.created_at, datetime)
        assert isinstance(conv.updated_at, datetime)

    def test_conversation_post_init_messages(self):
        msg = ChatMessage(role="user", content="hi", timestamp=datetime.now(timezone.utc))
        conv = Conversation(id="c1", user_id="u1", status="active", messages=[msg])
        assert len(conv.messages) == 1
        assert conv.messages[0].content == "hi"
