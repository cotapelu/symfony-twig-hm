"""Pydantic schemas for Chatbot module."""
from pydantic import BaseModel
from typing import Optional, List, Dict, Any


class ChatbotResponse(BaseModel):
    response: str
    conversation_id: str
    message_id: str
    success: bool = True


class KnowledgeBaseArticle(BaseModel):
    id: Optional[str] = None
    title: str
    content: str
    keywords: List[str] = []


class ConversationState(BaseModel):
    id: str
    user_id: str
    status: str = "active"
    messages: List[Dict[str, Any]] = []
