"""Unit tests for MessagingEngine service.

Tests IDOR protection, conversation/message lifecycle, group chats, and unread counts.
"""
import pytest
from datetime import datetime
from modules.Messaging.service.engine import MessagingEngine, MessagingService
from modules.Messaging.models.schemas import (
    Conversation,
    Message,
    GroupChat,
    MessageReadReceipt,
)


@pytest.fixture
def engine():
    return MessagingEngine()


@pytest.fixture
def sample_conversation(engine):
    return engine.create_conversation(["user1", "user2"])


# === Initialization ===

def test_init_creates_empty_state(engine):
    assert engine.conversations == {}
    assert engine.messages == {}
    assert engine.group_chats == {}
    assert engine.read_receipts == {}


def test_messaging_service_alias_exists():
    """Backward-compatibility alias for older test imports."""
    assert MessagingService is MessagingEngine


# === create_conversation ===

def test_create_conversation_with_two_participants(engine):
    conv = engine.create_conversation(["user1", "user2"])
    assert conv.id != ""
    assert "user1" in conv.participants
    assert "user2" in conv.participants
    assert conv.created_at is not None


def test_create_conversation_initializes_message_list(engine):
    conv = engine.create_conversation(["user1", "user2"])
    assert engine.messages[conv.id] == []


def test_create_conversation_with_single_participant(engine):
    conv = engine.create_conversation(["user1"])
    assert conv.participants == ["user1"]


def test_create_multiple_conversations_have_unique_ids(engine):
    c1 = engine.create_conversation(["user1"])
    c2 = engine.create_conversation(["user1"])
    assert c1.id != c2.id


def test_create_conversation_with_empty_participants(engine):
    conv = engine.create_conversation([])
    assert conv.participants == []


# === get_user_conversations (IDOR protection) ===

def test_get_user_conversations_returns_matching(engine):
    engine.create_conversation(["user1", "user2"])
    engine.create_conversation(["user1", "user3"])
    engine.create_conversation(["user4", "user5"])
    user1_convs = engine.get_user_conversations("user1")
    assert len(user1_convs) == 2


def test_get_user_conversations_with_empty_user_id_returns_empty(engine):
    """Security: empty user_id must return empty list (IDOR guard)."""
    engine.create_conversation(["user1", "user2"])
    assert engine.get_user_conversations("") == []


def test_get_user_conversations_for_nonexistent_user(engine):
    engine.create_conversation(["user1", "user2"])
    assert engine.get_user_conversations("user99") == []


def test_get_user_conversations_does_not_leak_other_users(engine):
    """Security: user3 must not see conversations they're not part of."""
    engine.create_conversation(["user1", "user2"])
    convs = engine.get_user_conversations("user3")
    assert convs == []


# === get_conversation_messages ===

def test_get_conversation_messages_empty(engine, sample_conversation):
    msgs = engine.get_conversation_messages(sample_conversation.id)
    assert msgs == []


def test_get_conversation_messages_returns_all_within_limit(engine, sample_conversation):
    for i in range(5):
        msg = Message(
            id="", conversation_id=sample_conversation.id,
            sender_id="user1", content=f"msg-{i}"
        )
        engine.send_message(msg)
    msgs = engine.get_conversation_messages(sample_conversation.id)
    assert len(msgs) == 5


def test_get_conversation_messages_respects_limit(engine, sample_conversation):
    for i in range(10):
        msg = Message(
            id="", conversation_id=sample_conversation.id,
            sender_id="user1", content=f"msg-{i}"
        )
        engine.send_message(msg)
    msgs = engine.get_conversation_messages(sample_conversation.id, limit=3)
    assert len(msgs) == 3


def test_get_conversation_messages_returns_last_n_with_limit(engine, sample_conversation):
    for i in range(10):
        msg = Message(
            id="", conversation_id=sample_conversation.id,
            sender_id="user1", content=f"msg-{i}"
        )
        engine.send_message(msg)
    msgs = engine.get_conversation_messages(sample_conversation.id, limit=3)
    # Last 3 are msg-7, msg-8, msg-9
    assert msgs[0].content == "msg-7"
    assert msgs[-1].content == "msg-9"


def test_get_conversation_messages_limit_zero_returns_all(engine, sample_conversation):
    for i in range(3):
        msg = Message(
            id="", conversation_id=sample_conversation.id,
            sender_id="user1", content=f"msg-{i}"
        )
        engine.send_message(msg)
    msgs = engine.get_conversation_messages(sample_conversation.id, limit=0)
    assert len(msgs) == 3


def test_get_conversation_messages_unknown_id_returns_empty(engine):
    assert engine.get_conversation_messages("nonexistent") == []


# === send_message ===

def test_send_message_generates_id_if_empty(engine, sample_conversation):
    msg = Message(
        id="", conversation_id=sample_conversation.id,
        sender_id="user1", content="hello"
    )
    result = engine.send_message(msg)
    assert result.id != ""


def test_send_message_preserves_existing_id(engine, sample_conversation):
    msg = Message(
        id="custom-id", conversation_id=sample_conversation.id,
        sender_id="user1", content="hello"
    )
    result = engine.send_message(msg)
    assert result.id == "custom-id"


def test_send_message_sets_created_at(engine, sample_conversation):
    msg = Message(
        id="", conversation_id=sample_conversation.id,
        sender_id="user1", content="hello"
    )
    result = engine.send_message(msg)
    assert result.created_at is not None


def test_send_message_updates_conversation_last_message(engine, sample_conversation):
    msg = Message(
        id="", conversation_id=sample_conversation.id,
        sender_id="user1", content="Hello world"
    )
    engine.send_message(msg)
    conv = engine.get_user_conversations("user1")[0]
    assert conv.last_message == "Hello world"


def test_send_message_increments_unread_for_non_participants(engine):
    conv = engine.create_conversation(["user1", "user2"])
    msg = Message(
        id="", conversation_id=conv.id,
        sender_id="user3", content="from outside"
    )
    engine.send_message(msg)
    # user3 is NOT in conv.participants, so unread_count should increment
    updated_conv = engine.conversations[conv.id]
    assert updated_conv.unread_count == 1


def test_send_message_does_not_increment_unread_for_participant_sender(engine, sample_conversation):
    msg = Message(
        id="", conversation_id=sample_conversation.id,
        sender_id="user1", content="hi"
    )
    engine.send_message(msg)
    assert sample_conversation.unread_count == 0


def test_send_message_initializes_message_list_if_missing(engine):
    """Sending to a conversation not yet in messages dict should initialize list."""
    fake_conv_id = "ghost-conv"
    msg = Message(
        id="", conversation_id=fake_conv_id,
        sender_id="user1", content="x"
    )
    engine.send_message(msg)
    assert fake_conv_id in engine.messages
    assert len(engine.messages[fake_conv_id]) == 1


# === mark_message_read ===

def test_mark_message_read_sets_is_read_true(engine, sample_conversation):
    msg = Message(
        id="", conversation_id=sample_conversation.id,
        sender_id="user1", content="x"
    )
    sent = engine.send_message(msg)
    result = engine.mark_message_read(sent.id, "user2")
    assert result is True
    assert engine.messages[sample_conversation.id][0].is_read is True


def test_mark_message_read_creates_receipt(engine, sample_conversation):
    msg = Message(
        id="", conversation_id=sample_conversation.id,
        sender_id="user1", content="x"
    )
    sent = engine.send_message(msg)
    engine.mark_message_read(sent.id, "user2")
    key = f"{sent.id}:user2"
    assert key in engine.read_receipts
    assert engine.read_receipts[key].user_id == "user2"


def test_mark_message_read_unknown_id_returns_false(engine):
    assert engine.mark_message_read("nonexistent", "user1") is False


def test_mark_message_read_can_be_called_multiple_times(engine, sample_conversation):
    """Multiple users can mark the same message as read."""
    msg = Message(
        id="", conversation_id=sample_conversation.id,
        sender_id="user1", content="x"
    )
    sent = engine.send_message(msg)
    engine.mark_message_read(sent.id, "user2")
    # Calling again with same user shouldn't fail
    result = engine.mark_message_read(sent.id, "user2")
    assert result is True


# === leave_conversation ===

def test_leave_conversation_removes_user(engine, sample_conversation):
    result = engine.leave_conversation(sample_conversation.id, "user1")
    assert result is True
    assert "user1" not in sample_conversation.participants


def test_leave_conversation_keeps_other_participants(engine, sample_conversation):
    engine.leave_conversation(sample_conversation.id, "user1")
    assert "user2" in sample_conversation.participants


def test_leave_conversation_user_not_participant_returns_false(engine, sample_conversation):
    assert engine.leave_conversation(sample_conversation.id, "user99") is False


def test_leave_conversation_unknown_id_returns_false(engine):
    assert engine.leave_conversation("nonexistent", "user1") is False


# === create_group_chat ===

def test_create_group_chat_generates_id(engine):
    chat = GroupChat(
        id="", name="Dev Team", admin_id="user1",
        participants=["user1", "user2", "user3"]
    )
    result = engine.create_group_chat(chat)
    assert result.id != ""


def test_create_group_chat_sets_created_at(engine):
    chat = GroupChat(
        id="", name="Dev Team", admin_id="user1",
        participants=["user1", "user2"]
    )
    result = engine.create_group_chat(chat)
    assert result.created_at is not None


def test_create_group_chat_preserves_existing_id(engine):
    chat = GroupChat(
        id="custom-group", name="Dev", admin_id="user1",
        participants=["user1", "user2"]
    )
    result = engine.create_group_chat(chat)
    assert result.id == "custom-group"


def test_create_group_chat_with_media_urls(engine):
    chat = GroupChat(
        id="", name="Media Group", admin_id="user1",
        participants=["user1"], media_urls=["img1.jpg", "img2.jpg"]
    )
    result = engine.create_group_chat(chat)
    assert len(result.media_urls) == 2


# === get_user_group_chats (IDOR protection) ===

def test_get_user_group_chats_returns_matching(engine):
    g1 = GroupChat(id="", name="G1", admin_id="u1", participants=["u1", "u2"])
    g2 = GroupChat(id="", name="G2", admin_id="u3", participants=["u3", "u1"])
    g3 = GroupChat(id="", name="G3", admin_id="u4", participants=["u4", "u5"])
    engine.create_group_chat(g1)
    engine.create_group_chat(g2)
    engine.create_group_chat(g3)
    user1_chats = engine.get_user_group_chats("u1")
    assert len(user1_chats) == 2


def test_get_user_group_chats_excludes_non_participant(engine):
    g1 = GroupChat(id="", name="G1", admin_id="u1", participants=["u1", "u2"])
    engine.create_group_chat(g1)
    user3_chats = engine.get_user_group_chats("u3")
    assert user3_chats == []


def test_get_user_group_chats_returns_empty_for_no_chats(engine):
    assert engine.get_user_group_chats("user1") == []


# === get_unread_count ===

def test_get_unread_count_no_conversations(engine):
    result = engine.get_unread_count("user1")
    assert result == {"unread_count": 0}


def test_get_unread_count_zero_when_no_unread(engine, sample_conversation):
    result = engine.get_unread_count("user1")
    assert result["unread_count"] == 0


def test_get_unread_count_aggregates_across_conversations(engine):
    c1 = engine.create_conversation(["user1", "user2"])
    c2 = engine.create_conversation(["user1", "user3"])
    # Send messages from non-participants
    m1 = Message(id="", conversation_id=c1.id, sender_id="x", content="x")
    m2 = Message(id="", conversation_id=c2.id, sender_id="y", content="y")
    engine.send_message(m1)
    engine.send_message(m2)
    result = engine.get_unread_count("user1")
    assert result["unread_count"] == 2


def test_get_unread_count_excludes_non_participant_conversations(engine):
    conv = engine.create_conversation(["user1", "user2"])
    msg = Message(id="", conversation_id=conv.id, sender_id="x", content="x")
    engine.send_message(msg)
    # user3 is not a participant, so unread_count for them is 0
    result = engine.get_unread_count("user3")
    assert result["unread_count"] == 0


# === Integration scenarios ===

def test_full_message_flow(engine, sample_conversation):
    """Send -> Mark Read -> Unread Count workflow."""
    # user1 sends 2 messages
    msg1 = Message(id="", conversation_id=sample_conversation.id, sender_id="user1", content="Hi")
    msg2 = Message(id="", conversation_id=sample_conversation.id, sender_id="user1", content="Hello?")
    s1 = engine.send_message(msg1)
    s2 = engine.send_message(msg2)
    # user2 marks both as read
    engine.mark_message_read(s1.id, "user2")
    engine.mark_message_read(s2.id, "user2")
    # Verify messages exist
    msgs = engine.get_conversation_messages(sample_conversation.id)
    assert len(msgs) == 2
    assert all(m.is_read for m in msgs)


def test_send_message_to_unknown_conversation_creates_list(engine):
    msg = Message(id="", conversation_id="ghost", sender_id="u1", content="x")
    sent = engine.send_message(msg)
    assert sent.id != ""
    assert "ghost" in engine.messages


def test_message_read_receipts_have_correct_keys(engine, sample_conversation):
    msg = Message(id="", conversation_id=sample_conversation.id, sender_id="user1", content="x")
    sent = engine.send_message(msg)
    engine.mark_message_read(sent.id, "user2")
    engine.mark_message_read(sent.id, "user3")
    assert f"{sent.id}:user2" in engine.read_receipts
    assert f"{sent.id}:user3" in engine.read_receipts


def test_leave_then_check_conversations(engine, sample_conversation):
    """After leaving, user1 should no longer see this conversation in their list."""
    engine.leave_conversation(sample_conversation.id, "user1")
    convs = engine.get_user_conversations("user1")
    assert convs == []


def test_send_message_with_special_content(engine, sample_conversation):
    msg = Message(
        id="", conversation_id=sample_conversation.id,
        sender_id="user1", content="🎉 Special chars: <>&\"'"
    )
    sent = engine.send_message(msg)
    assert sent.content == "🎉 Special chars: <>&\"'"
    msgs = engine.get_conversation_messages(sample_conversation.id)
    assert msgs[0].content == "🎉 Special chars: <>&\"'"


def test_multiple_conversations_independent_message_lists(engine):
    c1 = engine.create_conversation(["u1", "u2"])
    c2 = engine.create_conversation(["u3", "u4"])
    msg1 = Message(id="", conversation_id=c1.id, sender_id="u1", content="in c1")
    msg2 = Message(id="", conversation_id=c2.id, sender_id="u3", content="in c2")
    engine.send_message(msg1)
    engine.send_message(msg2)
    assert len(engine.get_conversation_messages(c1.id)) == 1
    assert len(engine.get_conversation_messages(c2.id)) == 1
    assert engine.get_conversation_messages(c1.id)[0].content == "in c1"
    assert engine.get_conversation_messages(c2.id)[0].content == "in c2"
