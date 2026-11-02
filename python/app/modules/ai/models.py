"""
AI module - Unified database integration
Uses shared DatabaseManager and models from app.shared.models
"""
from datetime import datetime, timedelta
from typing import List, Optional
import sqlalchemy as sa
import json
import hashlib

from app.shared.database import db
from app.shared.models import (
    Conversation, ConversationSummary,
    GeneratedContentLog, ModerationCache,
    ConversationType, ContentSource
)


class AIRepository:
    """
    AI repository using unified schema.
    Conversation history stored as unified conversations (type='ai').
    Summaries stored in ConversationSummary.
    Generated content in GeneratedContentLog with source='ai_generation'.
    Moderation cache shared.
    """
    def __init__(self):
        self.db = db

    async def init_db(self):
        """Database initialization handled centrally by main.py"""
        pass

    async def get_session(self):
        async with self.db.get_session() as session:
            yield session

    # Conversation history (AI)
    async def save_conversation_history(self, user_id: str, title: str, messages: List[str]) -> Conversation:
        async for session in self.get_session():
            conversation = Conversation(
                user_id=user_id,
                bot_id=None,
                type=ConversationType.AI,
                title=title,
                messages=json.dumps(messages[-20:]),
                status='completed'
            )
            session.add(conversation)
            await session.commit()
            await session.refresh(conversation)
            return conversation

    async def get_conversation_history(self, user_id: str, limit: int = 50) -> List[Conversation]:
        async for session in self.get_session():
            result = await session.execute(
                sa.select(Conversation)
                .where(Conversation.user_id == user_id)
                .where(Conversation.type == ConversationType.AI)
                .order_by(Conversation.created_at.desc())
                .limit(limit)
            )
            return result.scalars().all()

    # Summaries
    async def save_summary(self, conversation_id: str, summary_text: str, key_points: List[str], action_items: List[str]) -> ConversationSummary:
        async for session in self.get_session():
            summary = ConversationSummary(
                conversation_id=conversation_id,
                summary_text=summary_text,
                key_points=json.dumps(key_points),
                action_items=json.dumps(action_items),
                participant_count=2
            )
            session.add(summary)
            await session.commit()
            await session.refresh(summary)
            return summary

    # Generated content
    async def save_generated_content(self, user_id: str, content_type: str, prompt: str, generated: str) -> GeneratedContentLog:
        async for session in self.get_session():
            content = GeneratedContentLog(
                user_id=user_id,
                content_type=content_type,
                prompt=prompt,
                generated_text=generated,
                source=ContentSource.AI_GENERATION
            )
            session.add(content)
            await session.commit()
            await session.refresh(content)
            return content

    # Moderation
    async def get_moderation_result(self, content_hash: str) -> Optional[ModerationCache]:
        async for session in self.get_session():
            result = await session.execute(
                sa.select(ModerationCache)
                .where(ModerationCache.content_hash == content_hash)
                .where(ModerationCache.expires_at > datetime.utcnow())
            )
            return result.scalar_one_or_none()

    async def cache_moderation_result(self, content_hash: str, is_violation: bool, severity: float,
                                      violations: List[str], action: str) -> ModerationCache:
        async for session in self.get_session():
            existing = await self.get_moderation_result(content_hash)
            if existing:
                existing.is_violation = is_violation
                existing.severity = severity
                existing.violations = json.dumps(violations)
                existing.action = action
                existing.expires_at = datetime.utcnow() + timedelta(hours=24)
                await session.commit()
                return existing

            cache = ModerationCache(
                content_hash=content_hash,
                is_violation=is_violation,
                severity=severity,
                violations=json.dumps(violations),
                action=action,
                expires_at=datetime.utcnow() + timedelta(hours=24),
                content_type='text'
            )
            session.add(cache)
            await session.commit()
            await session.refresh(cache)
            return cache

    async def cleanup_expired_cache(self):
        async for session in self.get_session():
            await session.execute(
                sa.delete(ModerationCache).where(ModerationCache.expires_at < datetime.utcnow())
            )
            await session.commit()
