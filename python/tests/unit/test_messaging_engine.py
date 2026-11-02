"""Unit tests for MessagingEngine."""
import pytest
from datetime import datetime
from modules.Messaging.service.engine import MessagingEngine, MessagingService
from modules.Messaging.models.schemas import Conversation, Message, GroupChat, MessageReadReceipt

class TestMessagingEngine:
    """Test suite for MessagingEngine."""

    @pytest.fixture
    def engine(self):
        return MessagingEngine()

    def test_create_conversation(self, engine):
        conv = engine.create_conversation(participants=["u1", "u2"])
        assert conv.id is not None
        assert conv.participants == ["u1", "u2"]
        assert isinstance(conv.created_at, datetime)
        assert conv.id in engine.conversations
        assert conv.id in engine.messages
        assert engine.messages[conv.id] == []

    def test_get_user_conversations_returns_only_for_user(self, engine):
        conv1 = engine.create_conversation(participants=["u1", "u2"])
        conv2 = engine.create_conversation(participants=["u2", "u3"])
        conv3 = engine.create_conversation(participants=["u3", "u4"])
        u1_convos = engine.get_user_conversations("u1")
        assert len(u1_convos) == 1
        assert u1_convos[0].id == conv1.id
        u2_convos = engine.get_user_conversations("u2")
        assert len(u2_convos) == 2  # conv1 and conv2
        ids = {c.id for c in u2_convos}
        assert ids == {conv1.id, conv2.id}

    def test_get_user_conversations_empty_user_returns_empty(self, engine):
        conv = engine.create_conversation(participants=["u1", "u2"])
        result = engine.get_user_conversations(None)
        assert result == []
        result = engine.get_user_conversations("")
        assert result == []

    def test_send_message_creates_message_and_updates_conversation(self, engine):
        conv = engine.create_conversation(participants=["u1", "u2"])
        msg = Message(conversation_id=conv.id, sender_id="u1", content="Hello")
        sent = engine.send_message(msg)
        assert sent.id is not None
        assert sent.content == "Hello"
        assert sent.created_at is not None
        # Should be in conversation's messages
        assert conv.id in engine.messages
        assert len(engine.messages[conv.id]) == 1
        assert engine.messages[conv.id][0] == sent
        # Conversation last_message and time updated
        assert conv.last_message == "Hello"
        assert conv.last_message_time == sent.created_at
        # unread_count unchanged because sender is participant
        assert conv.unread_count == 0

    def test_send_message_from_non_participant_increments_unread(self, engine):
        conv = engine.create_conversation(participants=["u1", "u2"])
        initial_unread = conv.unread_count
        msg = Message(conversation_id=conv.id, sender_id="external", content="Hi")
        engine.send_message(msg)
        assert conv.unread_count == initial_unread + 1

    def test_get_conversation_messages_returns_last_n(self, engine):
        conv = engine.create_conversation(participants=["u1"])
        # send 5 messages
        messages = []
        for i in range(5):
            msg = Message(conversation_id=conv.id, sender_id="u1", content=f"Msg{i}")
            sent = engine.send_message(msg)
            messages.append(sent)
        # Get last 3
        last_three = engine.get_conversation_messages(conv.id, limit=3)
        assert len(last_three) == 3
        assert last_three[0].content == "Msg2"
        assert last_three[1].content == "Msg3"
        assert last_three[2].content == "Msg4"

    def test_mark_message_read_success(self, engine):
        conv = engine.create_conversation(participants=["u1", "u2"])
        msg = Message(conversation_id=conv.id, sender_id="u1", content="Test")
        sent = engine.send_message(msg)
        # Initially not read
        assert not any(r.message_id == sent.id and r.user_id == "u2" for r in engine.read_receipts.values())
        result = engine.mark_message_read(sent.id, "u2")
        assert result is True
        # Check receipt exists
        key = f"{sent.id}:u2"
        assert key in engine.read_receipts
        receipt = engine.read_receipts[key]
        assert receipt.message_id == sent.id
        assert receipt.user_id == "u2"
        # Also check message is_read flag
        # In our engine, we set msg.is_read = True; need to retrieve the message from engine.messages
        msgs = engine.messages[conv.id]
        read_msg = next(m for m in msgs if m.id == sent.id)
        assert read_msg.is_read is True

    def test_mark_message_read_not_found(self, engine):
        result = engine.mark_message_read("nonexistent", "u1")
        assert result is False

    def test_leave_conversation_removes_participant(self, engine):
        conv = engine.create_conversation(participants=["u1", "u2"])
        result = engine.leave_conversation(conv.id, "u2")
        assert result is True
        conv = engine.conversations[conv.id]
        assert "u2" not in conv.participants
        assert "u1" in conv.participants

    def test_leave_conversation_nonexistent(self, engine):
        result = engine.leave_conversation("fakeid", "u1")
        assert result is False

    def test_create_group_chat(self, engine):
        chat = GroupChat(id="", name="Group1", participants=["u1", "u2", "u3"])
        created = engine.create_group_chat(chat)
        assert created.id is not None
        assert created.name == "Group1"
        assert created.participants == ["u1", "u2", "u3"]
        assert created.id in engine.group_chats

    def test_get_user_group_chats(self, engine):
        g1 = GroupChat(name="G1", participants=["u1", "u2"])
        g2 = GroupChat(name="G2", participants=["u2", "u3"])
        g3 = GroupChat(name="G3", participants=["u1", "u3"])
        engine.create_group_chat(g1)
        engine.create_group_chat(g2)
        engine.create_group_chat(g3)
        u1_groups = engine.get_user_group_chats("u1")
        assert len(u1_groups) == 2
        names = {g.name for g in u1_groups}
        assert names == {"G1", "G3"}

    def test_get_unread_count_sums_conversations(self, engine):
        conv1 = engine.create_conversation(participants=["u1"])
        conv2 = engine.create_conversation(participants=["u1"])
        # Simulate unread counts
        conv1.unread_count = 2
        conv2.unread_count = 3
        result = engine.get_unread_count("u1")
        assert result["unread_count"] == 5

    def test_send_multiple_messages_ordering(self, engine):
        conv = engine.create_conversation(participants=["u1"])
        for i in range(3):
            msg = Message(conversation_id=conv.id, sender_id="u1", content=f"Msg{i}")
            engine.send_message(msg)
        retrieved = engine.get_conversation_messages(conv.id, limit=3)
        assert [m.content for m in retrieved] == ["Msg0", "Msg1", "Msg2"]

if __name__ == "__main__":
    pytest.main([__file__, "-v"])