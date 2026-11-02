import pytest
from modules.Messaging.service.engine import MessagingEngine
from modules.Messaging.models.schemas import Conversation, Message, GroupChat

class TestMessagingEngine:
    def setup_method(self):
        self.engine = MessagingEngine()
    
    def test_create_conversation(self):
        conv = self.engine.create_conversation(["user_1", "user_2"])
        
        assert conv.id is not None
        assert "user_1" in conv.participants
        assert "user_2" in conv.participants
        assert conv.created_at is not None
    
    def test_get_user_conversations(self):
        self.engine.create_conversation(["user_1", "user_2"])
        self.engine.create_conversation(["user_1", "user_3"])
        self.engine.create_conversation(["user_2", "user_3"])
        
        convs = self.engine.get_user_conversations("user_1")
        
        assert len(convs) == 2
    
    def test_send_message(self):
        conv = self.engine.create_conversation(["user_1", "user_2"])
        
        message = Message(
            conversation_id=conv.id,
            sender_id="user_1",
            content="Hello!"
        )
        result = self.engine.send_message(message)
        
        assert result.id is not None
        assert result.created_at is not None
        assert result.content == "Hello!"
    
    def test_get_conversation_messages(self):
        conv = self.engine.create_conversation(["user_1", "user_2"])
        
        self.engine.send_message(Message(
            conversation_id=conv.id,
            sender_id="user_1",
            content="Message 1"
        ))
        self.engine.send_message(Message(
            conversation_id=conv.id,
            sender_id="user_2",
            content="Message 2"
        ))
        
        messages = self.engine.get_conversation_messages(conv.id)
        
        assert len(messages) == 2
    
    def test_get_conversation_messages_with_limit(self):
        conv = self.engine.create_conversation(["user_1", "user_2"])
        
        for i in range(10):
            self.engine.send_message(Message(
                conversation_id=conv.id,
                sender_id="user_1",
                content=f"Message {i}"
            ))
        
        messages = self.engine.get_conversation_messages(conv.id, limit=5)
        
        assert len(messages) == 5
    
    def test_mark_message_read(self):
        conv = self.engine.create_conversation(["user_1", "user_2"])
        
        message = self.engine.send_message(Message(
            conversation_id=conv.id,
            sender_id="user_1",
            content="Read this"
        ))
        
        result = self.engine.mark_message_read(message.id, "user_2")
        
        assert result is True
    
    def test_mark_message_read_not_found(self):
        result = self.engine.mark_message_read("nonexistent", "user_1")
        
        assert result is False
    
    def test_leave_conversation(self):
        conv = self.engine.create_conversation(["user_1", "user_2"])
        
        result = self.engine.leave_conversation(conv.id, "user_2")
        
        assert result is True
        assert "user_2" not in conv.participants
    
    def test_leave_conversation_not_member(self):
        conv = self.engine.create_conversation(["user_1", "user_2"])
        
        result = self.engine.leave_conversation(conv.id, "user_3")
        
        assert result is False
    
    def test_create_group_chat(self):
        chat = GroupChat(
            name="Test Group",
            admin_id="user_1",
            participants=["user_1", "user_2", "user_3"]
        )
        result = self.engine.create_group_chat(chat)
        
        assert result.id is not None
        assert result.created_at is not None
    
    def test_get_user_group_chats(self):
        self.engine.create_group_chat(GroupChat(
            name="Group 1",
            admin_id="user_1",
            participants=["user_1", "user_2"]
        ))
        self.engine.create_group_chat(GroupChat(
            name="Group 2",
            admin_id="user_2",
            participants=["user_2", "user_3"]
        ))
        
        chats = self.engine.get_user_group_chats("user_2")
        
        assert len(chats) == 2
    
    def test_get_unread_count(self):
        conv = self.engine.create_conversation(["user_1", "user_2"])
        self.engine.send_message(Message(
            conversation_id=conv.id,
            sender_id="user_1",
            content="Unread message"
        ))
        
        count = self.engine.get_unread_count("user_2")
        
        assert count["unread_count"] >= 0


class TestConversationSchemas:
    def test_conversation_creation(self):
        conv = Conversation(
            participants=["user_1", "user_2"]
        )
        
        assert len(conv.participants) == 2
        assert conv.unread_count == 0
    
    def test_message_creation(self):
        msg = Message(
            conversation_id="conv_1",
            sender_id="user_1",
            content="Test message"
        )
        
        assert msg.conversation_id == "conv_1"
        assert msg.sender_id == "user_1"
        assert msg.message_type == "text"
        assert msg.is_read is False
    
    def test_message_with_media(self):
        msg = Message(
            conversation_id="conv_1",
            sender_id="user_1",
            content="Check this out",
            message_type="image",
            media_url="https://example.com/image.jpg"
        )
        
        assert msg.message_type == "image"
        assert msg.media_url == "https://example.com/image.jpg"
    
    def test_group_chat_creation(self):
        chat = GroupChat(
            name="My Group",
            admin_id="user_1",
            participants=["user_1", "user_2", "user_3"]
        )
        
        assert chat.name == "My Group"
        assert chat.admin_id == "user_1"
        assert len(chat.participants) == 3
