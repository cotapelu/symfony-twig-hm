from typing import Dict, List, Optional
import uuid
from datetime import datetime
from models.schemas import Conversation, Message, GroupChat, MessageReadReceipt

class MessagingEngine:
    def __init__(self):
        self.conversations: Dict[str, Conversation] = {}
        self.messages: Dict[str, List[Message]] = {}
        self.group_chats: Dict[str, GroupChat] = {}
        self.read_receipts: Dict[str, MessageReadReceipt] = {}

    def create_conversation(self, participants: List[str]) -> Conversation:
        conv = Conversation(
            id=str(uuid.uuid4()),
            participants=participants,
            created_at=datetime.now()
        )
        self.conversations[conv.id] = conv
        self.messages[conv.id] = []
        return conv

    def get_user_conversations(self, user_id: str) -> List[Conversation]:
        # Security: ensure user can only access their own conversations
        if not user_id:
            return []
        return [c for c in self.conversations.values() if user_id in c.participants]

    def get_conversation_messages(self, conversation_id: str, limit: int = 50) -> List[Message]:
        messages = self.messages.get(conversation_id, [])
        return messages[-limit:] if limit > 0 else messages

    def send_message(self, message: Message) -> Message:
        if not message.id:
            message.id = str(uuid.uuid4())
        message.created_at = datetime.now()
        
        if message.conversation_id not in self.messages:
            self.messages[message.conversation_id] = []
        self.messages[message.conversation_id].append(message)
        
        conv = self.conversations.get(message.conversation_id)
        if conv:
            conv.last_message = message.content
            conv.last_message_time = message.created_at
            if message.sender_id not in conv.participants:
                conv.unread_count += 1
        
        return message

    def mark_message_read(self, message_id: str, user_id: str) -> bool:
        for msg_list in self.messages.values():
            for msg in msg_list:
                if msg.id == message_id:
                    msg.is_read = True
                    receipt = MessageReadReceipt(
                        message_id=message_id,
                        user_id=user_id,
                        read_at=datetime.now()
                    )
                    self.read_receipts[f"{message_id}:{user_id}"] = receipt
                    return True
        return False

    def leave_conversation(self, conversation_id: str, user_id: str) -> bool:
        conv = self.conversations.get(conversation_id)
        if conv and user_id in conv.participants:
            conv.participants.remove(user_id)
            return True
        return False

    def create_group_chat(self, chat: GroupChat) -> GroupChat:
        if not chat.id:
            chat.id = str(uuid.uuid4())
        chat.created_at = datetime.now()
        self.group_chats[chat.id] = chat
        return chat

    def get_user_group_chats(self, user_id: str) -> List[GroupChat]:
        return [g for g in self.group_chats.values() if user_id in g.participants]

    def get_unread_count(self, user_id: str) -> Dict:
        count = 0
        for conv in self.conversations.values():
            if user_id in conv.participants:
                count += conv.unread_count
        return {"unread_count": count}


# Alias MessagingService for backward compatibility with tests
MessagingService = MessagingEngine
