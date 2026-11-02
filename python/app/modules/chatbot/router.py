"""Chatbot router for Python monolith - uses shared JWT auth"""
from fastapi import APIRouter, Depends, HTTPException, Request
from typing import Dict
import time
import asyncio
from collections import defaultdict, deque
from .service.engine import ChatbotEngine
from .models.schemas import ChatbotResponse, KnowledgeBaseArticle

router = APIRouter(prefix="/chatbot", tags=["chatbot"])
engine = ChatbotEngine()

# Import shared auth dependencies
from app.shared.middleware.auth import verify_token, get_current_user_id

# Rate limiting: 60 requests per minute per user
_chatbot_rate_data: Dict[str, deque] = defaultdict(deque)
_chatbot_lock = asyncio.Lock()

async def chatbot_user_with_rate_limit(user_id: str = Depends(get_current_user_id)) -> str:
    """Combined dependency: verifies user and enforces rate limit."""
    async with _chatbot_lock:
        now = time.time()
        timestamps = _chatbot_rate_data[user_id]
        while timestamps and timestamps[0] < now - 60:
            timestamps.popleft()
        if len(timestamps) >= 60:
            raise HTTPException(
                status_code=429,
                detail="Rate limit exceeded. Maximum 60 chatbot requests per minute.",
                headers={
                    "X-RateLimit-Limit": "60",
                    "X-RateLimit-Remaining": "0",
                    "Retry-After": "60"
                }
            )
        timestamps.append(now)
    return user_id

# NOTE: Route order matters! More specific paths must be defined BEFORE generic ones
# to avoid FastAPI matching the wrong endpoint.
# Order: end_conversation (fixed trailing segment) -> chat (two dynamic) -> chat_new -> get_conversation

@router.post("/chat/{conversation_id}/end")
async def end_conversation(conversation_id: str, current_user_id: str = Depends(chatbot_user_with_rate_limit)):
    conv = engine.conversations.get(conversation_id)
    if not conv:
        raise HTTPException(status_code=404, detail="Conversation not found")
    if conv.user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Access denied")
    conv.status = "closed"
    return {"status": "closed"}

@router.post("/chat/{user_id}/{conversation_id}", response_model=ChatbotResponse)
async def chat(user_id: str, conversation_id: str, request: Request, current_user_id: str = Depends(chatbot_user_with_rate_limit)):
    if current_user_id != user_id:
        raise HTTPException(status_code=403, detail="Not authorized")
    try:
        body = await request.json()
    except:
        body = {}
    message = body.get("message", "")
    if not message or not isinstance(message, str):
        raise HTTPException(status_code=400, detail="Message is required")
    if len(message) < 1:
        raise HTTPException(status_code=400, detail="Message cannot be empty")
    if len(message) > 2000:
        raise HTTPException(status_code=400, detail="Message is too long")
    response = engine.process_message(user_id, conversation_id, message)
    return response

@router.post("/chat/{user_id}")
async def chat_new(
    user_id: str,
    request: Dict,
    current_user_id: str = Depends(chatbot_user_with_rate_limit)
):
    """Start new chat conversation (IDOR protected)"""
    if user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other users' chat")
    message = request.get("message", "")
    if not message or not isinstance(message, str):
        raise HTTPException(status_code=400, detail="Message is required")
    if len(message) < 1:
        raise HTTPException(status_code=400, detail="Message cannot be empty")
    if len(message) > 2000:
        raise HTTPException(status_code=400, detail="Message is too long")
    import uuid
    conversation_id = str(uuid.uuid4())
    response = engine.process_message(user_id, conversation_id, message)
    return response

@router.get("/chat/{conversation_id}")
async def get_conversation(conversation_id: str, current_user_id: str = Depends(chatbot_user_with_rate_limit)):
    conv = engine.conversations.get(conversation_id)
    if not conv:
        raise HTTPException(status_code=404, detail="Conversation not found")
    # Ownership: conversation belongs to user?
    if conv.user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Access denied")
    return {
        "id": conv.id,
        "user_id": conv.user_id,
        "status": conv.status,
        "messages": [
            {"sender_type": m.sender_type, "content": m.content, "intent": m.intent}
            for m in conv.messages
        ]
    }

@router.post("/knowledge-base")
async def add_knowledge_base(article: Dict, current_user_id: str = Depends(chatbot_user_with_rate_limit)):
    # IDOR: public - any authenticated user can contribute to knowledge base
    kb_article = KnowledgeBaseArticle(**article)
    engine.add_knowledge_base_article(kb_article)
    return {"status": "added"}
