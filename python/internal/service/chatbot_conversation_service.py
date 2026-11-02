"""Chatbot Conversation Service.

Provides business logic for managing chatbot conversations.
"""

from typing import Optional, List
from uuid import UUID, uuid4
from datetime import datetime

from models.optimized_schema import ChatbotConversation
from internal.database.repositories.repositories import ChatbotConversationRepository


class ChatbotConversationService:
    """Service for managing chatbot conversations."""

    def __init__(self, repo: ChatbotConversationRepository):
        self.repo = repo

    def start_conversation(self, user_id: Optional[UUID], session_id: UUID,
                           status: str = "active") -> ChatbotConversation:
        """Start a new conversation."""
        if not session_id:
            raise ValueError("session_id is required")
        conv = ChatbotConversation(
            user_id=user_id,
            session_id=session_id,
            status=status
        )
        return self.repo.create(conv)

    def get_conversation(self, conv_id: UUID) -> Optional[ChatbotConversation]:
        """Get a conversation by ID."""
        return self.repo.get_by_id(conv_id)

    def get_by_session(self, session_id: UUID) -> List[ChatbotConversation]:
        """Get conversations for a session."""
        return self.repo.get_by_session(session_id)

    def get_by_user(self, user_id: UUID, limit: int = 100) -> List[ChatbotConversation]:
        """Get conversations for a user."""
        return self.repo.get_by_user(user_id, limit)

    def get_active(self, user_id: Optional[UUID] = None, limit: int = 100) -> List[ChatbotConversation]:
        """Get active conversations, optionally filtered by user."""
        return self.repo.get_active(user_id, limit)

    def update_status(self, conv_id: UUID, status: str) -> Optional[ChatbotConversation]:
        """Update conversation status."""
        if not status:
            raise ValueError("status is required")
        return self.repo.update_status(conv_id, status)

    def end_conversation(self, conv_id: UUID) -> Optional[ChatbotConversation]:
        """End a conversation by setting status to 'ended'."""
        return self.update_status(conv_id, "ended")
