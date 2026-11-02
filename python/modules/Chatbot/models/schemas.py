from pydantic import BaseModel
from typing import List, Dict, Optional
from datetime import datetime, timezone

class ChatMessage(BaseModel):
    id: str
    conversation_id: str
    sender_type: str
    content: str
    intent: Optional[str] = None
    entities: Optional[List] = None
    sentiment: Optional[str] = None
    confidence: float = 1.0
    created_at: datetime = datetime.now(timezone.utc)

class Conversation(BaseModel):
    id: str
    user_id: str
    channel: str
    participants: List[str] = []
    status: str = "active"
    context: Dict = {}
    messages: List[ChatMessage] = []
    created_at: datetime = datetime.now(timezone.utc)
    updated_at: datetime = datetime.now(timezone.utc)
    id: str
    user_id: str
    channel: str
    status: str = "active"
    context: Dict = {}
    messages: List[ChatMessage] = []
    created_at: datetime = datetime.now(timezone.utc)
    updated_at: datetime = datetime.now(timezone.utc)

class Intent(BaseModel):
    name: str
    examples: List[str]
    response_template: str
    required_entities: List[str] = []
    follow_up_intents: List[str] = []

class Entity(BaseModel):
    name: str
    value: str
    confidence: float

class ChatbotResponse(BaseModel):
    message: str
    intent: str
    entities: List[Entity] = []
    suggestions: List[str] = []
    context_updates: Dict = {}
    action: Optional[str] = None

class ConversationContext(BaseModel):
    user_id: str
    current_intent: Optional[str] = None
    slots: Dict[str, str] = {}
    history: List[Dict] = []
    preferences: Dict = {}
    sentiment_trend: str = "neutral"

class KnowledgeBaseArticle(BaseModel):
    id: str
    question: str
    answer: str
    category: str
    keywords: List[str] = []
    confidence: float = 0.9
    usage_count: int = 0
