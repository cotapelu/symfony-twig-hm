from fastapi import APIRouter, HTTPException, Depends
from typing import List, Dict, Any, Optional
from pydantic import BaseModel
import time
import asyncio
from collections import defaultdict, deque
import sqlalchemy as sa

from ..services.ai_service import AIService
from app.shared.middleware.auth import get_current_user_id
from app.shared.database import db
from app.shared.models import Conversation

router = APIRouter(prefix="/api/ai", tags=["ai"])

# Simple in-memory rate limiter: 60 requests per minute per user
_ai_rate_data: Dict[str, deque] = defaultdict(deque)
_rate_lock = asyncio.Lock()

async def ai_user_with_rate_limit(user_id: str = Depends(get_current_user_id)) -> str:
    """Combined dependency: verifies user and enforces rate limit."""
    async with _rate_lock:
        now = time.time()
        timestamps = _ai_rate_data[user_id]
        # Remove old entries outside 60s window
        while timestamps and timestamps[0] < now - 60:
            timestamps.popleft()
        if len(timestamps) >= 60:
            raise HTTPException(
                status_code=429,
                detail="Rate limit exceeded. Maximum 60 AI requests per minute.",
                headers={
                    "X-RateLimit-Limit": "60",
                    "X-RateLimit-Remaining": "0",
                    "Retry-After": "60"
                }
            )
        timestamps.append(now)
    return user_id

ai_service = AIService()


class SmartRepliesRequest(BaseModel):
    conversation_context: List[Dict[str, Any]]
    last_message: str


class TranslationRequest(BaseModel):
    text: str
    source_language: str
    target_language: str


class ContentRequest(BaseModel):
    content: str
    content_type: str = "text"


@router.post("/smart-replies")
async def get_smart_replies(
    request: SmartRepliesRequest,
    current_user_id: str = Depends(ai_user_with_rate_limit)
) -> List[str]:
    """Generate smart replies for a conversation"""
    # IDOR: public - smart replies generation is self-contained
    replies = ai_service.generate_smart_replies(
        context=request.conversation_context,
        last_message=request.last_message,
        user_id=current_user_id
    )
    return replies


@router.post("/sentiment")
async def analyze_sentiment(
    request: ContentRequest,
    current_user_id: str = Depends(ai_user_with_rate_limit)
) -> Dict[str, Any]:
    """Analyze sentiment of text"""
    # IDOR: public - sentiment analysis is self-contained
    result = await ai_service.analyze_sentiment(request.content)
    return result


@router.post("/translate")
async def translate_text(
    request: TranslationRequest,
    current_user_id: str = Depends(ai_user_with_rate_limit)
) -> Dict[str, Any]:
    """Translate text between languages"""
    # IDOR: public - translation is self-contained
    result = ai_service.translate_text(
        text=request.text,
        source_lang=request.source_language,
        target_lang=request.target_language
    )
    return result


@router.post("/moderate")
async def moderate_content(
    request: ContentRequest,
    current_user_id: str = Depends(ai_user_with_rate_limit)
) -> Dict[str, Any]:
    """Moderate content for violations"""
    # IDOR: public - moderation is self-contained
    result = await ai_service.moderate_content(request.content, request.content_type)
    return result


@router.get("/summary/{conversation_id}")
async def get_conversation_summary(
    conversation_id: str,
    current_user_id: str = Depends(ai_user_with_rate_limit)
) -> Dict[str, Any]:
    """Generate summary of conversation (IDOR protected)"""
    # Verify that the conversation belongs to the current user
    async with db.get_session() as session:
        result = await session.execute(
            sa.select(Conversation).where(Conversation.id == conversation_id)
        )
        conv = result.scalar_one_or_none()
        if not conv or conv.user_id != current_user_id:
            raise HTTPException(status_code=403, detail="Not authorized to access this conversation")
    summary = ai_service.generate_summary(conversation_id)
    return summary


@router.post("/generate")
async def generate_content(
    request: Dict[str, Any],
    current_user_id: str = Depends(ai_user_with_rate_limit)
) -> Dict[str, Any]:
    """Generate AI content"""
    # IDOR: public - generation is self-contained
    content_type = request.get("content_type", "text")
    prompt = request.get("prompt", "")
    
    result = ai_service.generate_content(content_type, prompt, user_id=current_user_id)
    return result
