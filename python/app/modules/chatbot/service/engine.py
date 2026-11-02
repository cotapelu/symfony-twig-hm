"""Chatbot engine - in-memory implementation."""
import uuid
from typing import Dict, List, Any, Optional
from collections import defaultdict
from ..models.schemas import ConversationState


class ChatbotEngine:
    """Simple in-memory chatbot engine."""

    def __init__(self):
        self.conversations: Dict[str, ConversationState] = {}
        self.knowledge_base: List[Dict] = []

    def process_message(self, user_id: str, conversation_id: str, message: str) -> dict:
        """Process a user message and generate a response."""
        # Get or create conversation
        if conversation_id not in self.conversations:
            state = ConversationState(
                id=conversation_id,
                user_id=user_id,
                messages=[]
            )
            self.conversations[conversation_id] = state
        else:
            state = self.conversations[conversation_id]

        # Add user message
        state.messages.append({"role": "user", "content": message})

        # Generate simple response (could be rule-based or call external AI)
        response = self._generate_response(message)

        # Add bot response
        state.messages.append({"role": "assistant", "content": response})

        return {
            "response": response,
            "conversation_id": conversation_id,
            "message_id": str(uuid.uuid4()),
            "success": True
        }

    def _generate_response(self, message: str) -> str:
        """Generate a simple rule-based response."""
        msg_lower = message.lower()
        if any(word in msg_lower for word in ["hello", "hi", "hey"]):
            return "Hello! How can I help you today?"
        if any(word in msg_lower for word in ["help", "support", "issue"]):
            return "I'm here to help. Please describe your issue."
        if any(word in msg_lower for word in ["thank", "thanks"]):
            return "You're welcome!"
        return "I understand. Is there anything else you need?"

    def get_conversation(self, conversation_id: str) -> Optional[ConversationState]:
        return self.conversations.get(conversation_id)

    def add_knowledge_base_article(self, article):
        """Add an article to the knowledge base.
        Accepts either a dict or a Pydantic model with .dict()."""
        if hasattr(article, 'dict'):
            self.knowledge_base.append(article.dict())
        else:
            self.knowledge_base.append(article)
