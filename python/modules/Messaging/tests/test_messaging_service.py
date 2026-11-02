import pytest
from datetime import datetime
from modules.Messaging.service.engine import MessagingEngine
from modules.Messaging.models.schemas import Conversation, Message, GroupChat

@pytest.fixture
def engine():
    return MessagingEngine()

def test_init(engine):
    assert engine is not None
    assert engine.conversations == {}
    assert engine.messages == {}

def test_create_conversation(engine):
    conv = engine.create_conversation(["user1", "user2"])
    
    assert conv is not None
    assert conv.id in engine.conversations
    assert "user1" in conv.participants
    assert "user2" in conv.participants

def test_get_user_conversations(engine):
    conv = engine.create_conversation(["user1", "user2"])
    
    conversations = engine.get_user_conversations("user1")
    assert len(conversations) == 1
    assert conversations[0].id == conv.id

def test_get_user_conversations_unauthorized(engine):
    engine.create_conversation(["user1", "user2"])
    
    conversations = engine.get_user_conversations("user3")
    assert len(conversations) == 0

def test_get_user_conversations_empty_user(engine):
    conversations = engine.get_user_conversations(None)
    assert len(conversations) == 0

def test_send_message(engine):
    conv = engine.create_conversation(["user1", "user2"])
    
    message = Message(
        id="",
        conversation_id=conv.id,
        sender_id="user1",
        content="Hello"
    )
    
    sent = engine.send_message(message)
    
    assert sent is not None
    assert conv.id in engine.messages
    assert len(engine.messages[conv.id]) == 1

def test_get_conversation_messages(engine):
    conv = engine.create_conversation(["user1", "user2"])
    
    message1 = Message(id="", conversation_id=conv.id, sender_id="user1", content="Hi")
    message2 = Message(id="", conversation_id=conv.id, sender_id="user2", content="Hello")
    
    engine.send_message(message1)
    engine.send_message(message2)
    
    messages = engine.get_conversation_messages(conv.id)
    assert len(messages) == 2

def test_get_conversation_messages_limit(engine):
    conv = engine.create_conversation(["user1", "user2"])
    
    for i in range(20):
        message = Message(id="", conversation_id=conv.id, sender_id="user1", content=f"Msg {i}")
        engine.send_message(message)
    
    messages = engine.get_conversation_messages(conv.id, limit=5)
    assert len(messages) == 5

def test_mark_message_read(engine):
    conv = engine.create_conversation(["user1", "user2"])
    
    message = Message(id="", conversation_id=conv.id, sender_id="user1", content="Test")
    sent = engine.send_message(message)
    
    result = engine.mark_message_read(sent.id, "user1")
    assert result is True

def test_mark_message_read_not_found(engine):
    result = engine.mark_message_read("nonexistent", "user1")
    assert result is False

def test_leave_conversation(engine):
    conv = engine.create_conversation(["user1", "user2"])
    
    result = engine.leave_conversation(conv.id, "user1")
    assert result is True
    assert "user1" not in conv.participants

def test_leave_conversation_not_participant(engine):
    conv = engine.create_conversation(["user1", "user2"])
    
    result = engine.leave_conversation(conv.id, "user3")
    assert result is False

def test_create_group_chat(engine):
    chat = GroupChat(id="", name="Test Group", admin_id="user1", participants=["user1", "user2"])
    
    created = engine.create_group_chat(chat)
    
    assert created is not None
    assert created.id in engine.group_chats

def test_get_user_group_chats(engine):
    chat = GroupChat(id="", name="Test Group", admin_id="user1", participants=["user1", "user2"])
    engine.create_group_chat(chat)
    
    chats = engine.get_user_group_chats("user1")
    assert len(chats) == 1

def test_get_unread_count(engine):
    conv = engine.create_conversation(["user1", "user2"])
    
    count = engine.get_unread_count("user1")
    assert "unread_count" in count

def test_get_unread_count_multiple_conversations(engine):
    conv1 = engine.create_conversation(["user1", "user2"])
    conv2 = engine.create_conversation(["user1", "user3"])
    
    count = engine.get_unread_count("user1")
    assert count["unread_count"] >= 0
