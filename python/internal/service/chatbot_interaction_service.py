"""Chatbot Interaction Service.

Provides business logic for chatbot interactions (messages) within conversations.
"""

from typing import Optional, List
from uuid import UUID, uuid4
from datetime import datetime

from models.optimized_schema import ChatbotInteraction
from internal.database.repositories.repositories import ChatbotInteractionRepository


class ChatbotInteractionService:
    """Service for managing chatbot interactions."""

    def __init__(self, repo: ChatbotInteractionRepository):
        self.repo = repo

    def create_interaction(self, conversation_id: UUID, user_id: Optional[UUID],
                           role: str, content: str,
                           metadata: Optional[dict] = None) -> ChatbotInteraction:
        """Create a new interaction."""
        if not conversation_id:
            raise ValueError("conversation_id is required")
        if not role:
            raise ValueError("role is required")
        if not content:
            raise ValueError("content is required")
        interaction = ChatbotInteraction(
            conversation_id=conversation_id,
            user_id=user_id,
            role=role,
            content=content,
            meta=metadata or {}
        )
        return self.repo.create(interaction)

    def get_interaction(self, inter_id: UUID) -> Optional[ChatbotInteraction]:
        """Get an interaction by ID."""
        return self.repo.get_by_id(inter_id)

    def get_by_conversation(self, conversation_id: UUID, limit: int = 100) -> List[ChatbotInteraction]:
        """Get interactions for a conversation."""
        return self.repo.get_by_conversation(conversation_id, limit)

    def get_by_user(self, user_id: UUID, limit: int = 100) -> List[ChatbotInteraction]:
        """Get interactions by user."""
        return self.repo.get_by_user(user_id, limit)

    def get_by_role(self, conversation_id: UUID, role: str, limit: int = 100) -> List[ChatbotInteraction]:
        """Get interactions in a conversation filtered by role."""
        return self.repo.get_by_role(conversation_id, role, limit)

    def count_by_conversation(self, conversation_id: UUID) -> int:
        """Count interactions in a conversation."""
        return self.repo.count_by_conversation(conversation_id)
