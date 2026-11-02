from fastapi import APIRouter, HTTPException, Depends
from typing import List, Dict, Any, Optional
from pydantic import BaseModel

from ..bot_service import BotAutomationService
from app.shared.middleware.auth import get_current_user_id

router = APIRouter(prefix="/api/bots", tags=["bots"])

bot_service = BotAutomationService()


class ChatMessage(BaseModel):
    message: str
    user_id: Optional[str] = None
    context: Optional[Dict[str, Any]] = None


@router.get("")
async def get_bots(
    limit: int = 20,
    offset: int = 0,
    current_user_id: str = Depends(get_current_user_id)
) -> List[Dict[str, Any]]:
    """Get all available bots"""
    # IDOR: public - bots directory is public information
    return bot_service.list_bots(limit, offset)


@router.get("/{bot_id}")
async def get_bot(
    bot_id: str,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict[str, Any]:
    """Get bot details"""
    # IDOR: public - bot details are public information
    bot = bot_service.get_bot(bot_id)
    if not bot:
        raise HTTPException(status_code=404, detail="Bot not found")
    return bot


@router.post("/{bot_id}/chat")
async def chat_with_bot(
    bot_id: str,
    message: ChatMessage,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict[str, Any]:
    """Send message to bot and get response (IDOR protected)"""
    # Ensure the user_id in body matches authenticated user (if provided)
    if message.user_id and message.user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot send messages on behalf of another user")
    # Use authenticated user if not provided
    user_id = message.user_id or current_user_id
    response = bot_service.process_message(
        bot_id=bot_id,
        message=message.message,
        user_id=user_id,
        context=message.context
    )
    return response


@router.post("/{bot_id}/train")
async def train_bot(
    bot_id: str,
    training_data: Dict[str, Any],
    current_user_id: str = Depends(get_current_user_id)
) -> Dict[str, str]:
    """Train bot with new data (IDOR protected)"""
    bot = bot_service.get_bot(bot_id)
    if not bot:
        raise HTTPException(status_code=404, detail="Bot not found")
    # Check ownership: bot has an owner field
    owner_id = bot.get("owner_id") or bot.get("user_id")
    if owner_id != current_user_id:
        raise HTTPException(status_code=403, detail="Only bot owner can train this bot")
    success = bot_service.train_bot(bot_id, training_data)
    if not success:
        raise HTTPException(status_code=400, detail="Bot training failed")
    return {"message": "Bot trained successfully"}


@router.post("/{bot_id}/feedback")
async def submit_bot_feedback(
    bot_id: str,
    feedback: Dict[str, Any],
    current_user_id: str = Depends(get_current_user_id)
) -> Dict[str, str]:
    """Submit feedback for bot response (IDOR protected)"""
    # IDOR: public - any authenticated user can submit feedback
    bot_service.record_feedback(
        bot_id=bot_id,
        message_id=feedback.get("message_id") or "",
        rating=feedback.get("rating") or 0,
        feedback_text=feedback.get("feedback_text")
    )
    return {"message": "Feedback recorded successfully"}


@router.get("/{bot_id}/analytics")
async def get_bot_analytics(
    bot_id: str,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict[str, Any]:
    """Get bot usage analytics (IDOR protected)"""
    bot = bot_service.get_bot(bot_id)
    if not bot:
        raise HTTPException(status_code=404, detail="Bot not found")
    owner_id = bot.get("owner_id") or bot.get("user_id")
    if owner_id != current_user_id:
        raise HTTPException(status_code=403, detail="Only bot owner can view analytics")
    return bot_service.get_analytics(bot_id)
