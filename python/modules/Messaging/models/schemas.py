from pydantic import BaseModel
from typing import List, Optional
from datetime import datetime

class Conversation(BaseModel):
    id: str = ""
    participants: List[str]
    last_message: str = ""
    last_message_time: Optional[datetime] = None
    unread_count: int = 0
    created_at: Optional[datetime] = None

class Message(BaseModel):
    id: str = ""
    conversation_id: str
    sender_id: str
    content: str
    message_type: str = "text"
    media_url: str = None
    is_read: bool = False
    created_at: Optional[datetime] = None

class MessageReadReceipt(BaseModel):
    message_id: str
    user_id: str
    read_at: Optional[datetime] = None

class GroupChat(BaseModel):
    id: str = ""
    name: str
    admin_id: str
    participants: List[str]
    media_urls: List[str] = []
    created_at: Optional[datetime] = None
