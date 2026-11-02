"""
Chatbot Service - Conversational AI service for user interactions
"""
import json
import os
from datetime import datetime, timezone
from typing import List, Dict, Optional, Any
from dataclasses import dataclass, asdict
from enum import Enum
import uuid

from app.validation import Validator, ValidationError


class ConversationStatus(str, Enum):
    ACTIVE = "active"
    ESCALATED = "escalated"
    COMPLETED = "completed"
    WAITING = "waiting"


class MessageRole(str, Enum):
    USER = "user"
    ASSISTANT = "assistant"
    SYSTEM = "system"


class IntentType(str, Enum):
    GREETING = "greeting"
    QUESTION = "question"
    COMPLAINT = "complaint"
    ORDER = "order"
    REFUND = "refund"
    TRACKING = "tracking"
    PAYMENT = "payment"
    SUPPORT = "support"
    UNKNOWN = "unknown"


@dataclass
class ChatMessage:
    role: str
    content: str
    timestamp: datetime
    metadata: Dict[str, Any] = None

    def __post_init__(self):
        if self.metadata is None:
            self.metadata = {}
        if isinstance(self.timestamp, str):
            self.timestamp = datetime.fromisoformat(self.timestamp)


@dataclass
class Conversation:
    id: str
    user_id: str
    status: str
    messages: List[ChatMessage]
    current_intent: Optional[str] = None
    context: Dict[str, Any] = None
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None

    def __post_init__(self):
        if self.context is None:
            self.context = {}
        if self.created_at is None:
            self.created_at = datetime.now(timezone.utc)
        if self.updated_at is None:
            self.updated_at = datetime.now(timezone.utc)
        if isinstance(self.created_at, str):
            self.created_at = datetime.fromisoformat(self.created_at)
        if isinstance(self.updated_at, str):
            self.updated_at = datetime.fromisoformat(self.updated_at)


@dataclass
class ChatbotResponse:
    message: str
    intent: str
    confidence: float
    suggested_actions: List[str] = None
    metadata: Dict[str, Any] = None

    def __post_init__(self):
        if self.suggested_actions is None:
            self.suggested_actions = []
        if self.metadata is None:
            self.metadata = {}


class ChatbotService:
    """
    Service for managing chatbot conversations and responses
    """

    def __init__(self, storage_path: str = "/dev/shm/data/chatbot.json"):
        self.storage_path = storage_path
        self.conversations: Dict[str, Conversation] = {}
        self.intent_patterns: Dict[str, List[str]] = {
            IntentType.GREETING.value: ["hello", "hi", "hey", "good morning", "good afternoon"],
            IntentType.QUESTION.value: ["what", "how", "when", "where", "why", "which", "?"],
            IntentType.COMPLAINT.value: ["complaint", "problem", "issue", "dissatisfied", "angry"],
            IntentType.ORDER.value: ["order", "buy", "purchase", "order status"],
            IntentType.REFUND.value: ["refund", "return", "money back", "cancel"],
            IntentType.TRACKING.value: ["track", "shipping", "delivery", "where is"],
            IntentType.PAYMENT.value: ["payment", "pay", "invoice", "bill"],
            IntentType.SUPPORT.value: ["help", "support", "assist", "need help"],
        }
        self.response_templates: Dict[str, Dict[str, str]] = {
            IntentType.GREETING.value: {
                "default": "Hello! How can I help you today?",
                "returning": "Welcome back! How can I assist you?"
            },
            IntentType.QUESTION.value: {
                "default": "That's a great question. Let me help you with that.",
            },
            IntentType.COMPLAINT.value: {
                "default": "I'm sorry to hear about your experience. Let me escalate this to our support team.",
            },
            IntentType.ORDER.value: {
                "default": "I can help you with your order. Could you please provide your order ID?",
            },
            IntentType.REFUND.value: {
                "default": "I understand you'd like a refund. Let me process that for you.",
            },
            IntentType.TRACKING.value: {
                "default": "I can help you track your order. Please provide your tracking number.",
            },
            IntentType.PAYMENT.value: {
                "default": "I can help you with your payment. What specific issue are you facing?",
            },
            IntentType.SUPPORT.value: {
                "default": "I'm here to help! What do you need assistance with?",
            },
        }

        self.ensure_storage_directory()
        self.load_from_storage()

    def ensure_storage_directory(self):
        dir_path = os.path.dirname(self.storage_path)
        if dir_path and dir_path != "." and not os.path.exists(dir_path):
            os.makedirs(dir_path, exist_ok=True)

    def load_from_storage(self):
        return self._load_from_storage_impl()

    def _load_from_storage_impl(self):
        if not os.path.exists(self.storage_path):
            return
        try:
            with open(self.storage_path, 'r') as f:
                data = json.load(f)
                if 'conversations' in data:
                    for conv_id, conv_data in data['conversations'].items():
                        conv_data['created_at'] = datetime.fromisoformat(conv_data['created_at']) if conv_data.get('created_at') else datetime.now(timezone.utc)
                        conv_data['updated_at'] = datetime.fromisoformat(conv_data['updated_at']) if conv_data.get('updated_at') else datetime.now(timezone.utc)
                        conv_data['messages'] = [
                            ChatMessage(
                                role=m['role'],
                                content=m['content'],
                                timestamp=datetime.fromisoformat(m['timestamp']) if isinstance(m.get('timestamp'), str) else datetime.now(timezone.utc),
                                metadata=m.get('metadata', {})
                            ) for m in conv_data.get('messages', [])
                        ]
                        self.conversations[conv_id] = Conversation(**conv_data)
        except Exception as e:
            print(f"Failed to load chatbot data: {e}")

    def save_to_storage(self):
        return self._save_to_storage_impl()

    def _save_to_storage_impl(self):
        try:
            data = {
                'conversations': {
                    k: {
                        **asdict(v),
                        'created_at': v.created_at.isoformat() if v.created_at else None,
                        'updated_at': v.updated_at.isoformat() if v.updated_at else None,
                        'messages': [
                            {
                                'role': m.role,
                                'content': m.content,
                                'timestamp': m.timestamp.isoformat() if isinstance(m.timestamp, datetime) else m.timestamp,
                                'metadata': m.metadata
                            } for m in v.messages
                        ]
                    } for k, v in self.conversations.items()
                }
            }
            with open(self.storage_path, 'w') as f:
                json.dump(data, f, indent=2)
        except Exception as e:
            print(f"Failed to save chatbot data: {e}")

    def create_conversation(self, user_id: str) -> Conversation:
        """Create a new conversation"""
        conversation = Conversation(
            id=str(uuid.uuid4()),
            user_id=user_id,
            status=ConversationStatus.ACTIVE.value,
            messages=[],
            created_at=datetime.now(timezone.utc),
            updated_at=datetime.now(timezone.utc)
        )

        self.conversations[conversation.id] = conversation
        self.save_to_storage()

        return conversation

    def get_conversation(self, conversation_id: str) -> Optional[Conversation]:
        """Get conversation by ID"""
        return self.conversations.get(conversation_id)

    def get_user_conversations(self, user_id: str, limit: int = 20) -> List[Conversation]:
        """Get all conversations for a user"""
        user_convs = [
            c for c in self.conversations.values()
            if c.user_id == user_id
        ]
        user_convs.sort(key=lambda c: c.updated_at, reverse=True)
        return user_convs[:limit]

    def _detect_intent(self, message: str) -> tuple[str, float]:
        """Detect intent from user message"""
        message_lower = message.lower()
        intent_scores = {}

        for intent, patterns in self.intent_patterns.items():
            score = 0
            for pattern in patterns:
                if pattern in message_lower:
                    score += 1
            if score > 0:
                intent_scores[intent] = score

        if not intent_scores:
            return IntentType.UNKNOWN.value, 0.0

        # Get the intent with highest score
        max_score = max(intent_scores.values())
        candidates = [intent for intent, score in intent_scores.items() if score == max_score]
        # Define tie-breaking priority for ambiguous cases
        priority_order = [
            IntentType.TRACKING.value,
            IntentType.ORDER.value,
            IntentType.REFUND.value,
            IntentType.PAYMENT.value,
            IntentType.COMPLAINT.value,
            IntentType.SUPPORT.value,
            IntentType.QUESTION.value,
            IntentType.GREETING.value,
        ]
        max_intent = None
        for p in priority_order:
            if p in candidates:
                max_intent = p
                break
        if not max_intent:
            max_intent = candidates[0]
        # Confidence based on score relative to pattern count for that intent
        confidence = min(max_score / len(self.intent_patterns.get(max_intent, [1])), 1.0)
        return max_intent, confidence

    def _generate_response(self, intent: str, context: Dict[str, Any]) -> str:
        """Generate response based on intent"""
        templates = self.response_templates.get(intent, {})

        if not templates:
            return "I'm not sure how to help with that. Could you please rephrase?"

        # Get default response
        return templates.get("default", "I understand. Let me help you with that.")

    def send_message(
        self,
        conversation_id: str,
        user_message: str,
        context: Optional[Dict[str, Any]] = None
    ) -> ChatbotResponse:
        return self._send_message_impl(conversation_id, user_message, context)

    def _send_message_impl(
        self,
        conversation_id: str,
        user_message: str,
        context: Optional[Dict[str, Any]] = None
    ) -> ChatbotResponse:
        """Process user message and generate response"""
        conversation = self.conversations.get(conversation_id)
        if not conversation:
            raise ValueError(f"Conversation not found: {conversation_id}")

        # Add user message
        user_msg = ChatMessage(
            role=MessageRole.USER.value,
            content=user_message,
            timestamp=datetime.now(timezone.utc)
        )
        conversation.messages.append(user_msg)

        # Detect intent
        intent, confidence = self._detect_intent(user_message)
        conversation.current_intent = intent

        # Update context
        if context:
            conversation.context.update(context)

        # Generate response
        response_text = self._generate_response(intent, conversation.context)

        # Add assistant message
        assistant_msg = ChatMessage(
            role=MessageRole.ASSISTANT.value,
            content=response_text,
            timestamp=datetime.now(timezone.utc),
            metadata={"intent": intent, "confidence": confidence}
        )
        conversation.messages.append(assistant_msg)

        # Update conversation
        conversation.updated_at = datetime.now(timezone.utc)
        self.save_to_storage()

        # Determine suggested actions
        suggested_actions = self._get_suggested_actions(intent)

        return ChatbotResponse(
            message=response_text,
            intent=intent,
            confidence=confidence,
            suggested_actions=suggested_actions
        )

    def _get_suggested_actions(self, intent: str) -> List[str]:
        """Get suggested actions based on intent"""
        suggestions = {
            IntentType.ORDER.value: ["Check order status", "View order history", "Track shipment"],
            IntentType.REFUND.value: ["Start refund process", "View refund policy", "Check refund status"],
            IntentType.TRACKING.value: ["Enter tracking number", "View all orders", "Contact support"],
            IntentType.PAYMENT.value: ["View invoices", "Payment methods", "Transaction history"],
            IntentType.SUPPORT.value: ["Contact human agent", "View FAQ", "Submit ticket"],
        }
        return suggestions.get(intent, [])

    def escalate_conversation(self, conversation_id: str, reason: str) -> Optional[Conversation]:
        """Escalate conversation to human agent"""
        conversation = self.conversations.get(conversation_id)
        if not conversation:
            return None

        conversation.status = ConversationStatus.ESCALATED.value
        conversation.context['escalation_reason'] = reason
        conversation.updated_at = datetime.now(timezone.utc)
        self.save_to_storage()

        return conversation

    def close_conversation(self, conversation_id: str) -> Optional[Conversation]:
        """Close a conversation"""
        conversation = self.conversations.get(conversation_id)
        if not conversation:
            return None

        conversation.status = ConversationStatus.COMPLETED.value
        conversation.updated_at = datetime.now(timezone.utc)
        self.save_to_storage()

        return conversation

    def get_conversation_history(
        self,
        conversation_id: str,
        limit: int = 50
    ) -> List[ChatMessage]:
        """Get conversation history"""
        conversation = self.conversations.get(conversation_id)
        if not conversation:
            return []

        return conversation.messages[-limit:]

    def get_chatbot_statistics(self) -> Dict[str, Any]:
        return self._get_chatbot_statistics_impl()

    def _get_chatbot_statistics_impl(self) -> Dict[str, Any]:
        """Get chatbot statistics"""
        total_conversations = len(self.conversations)
        active_conversations = len([
            c for c in self.conversations.values()
            if c.status == ConversationStatus.ACTIVE.value
        ])
        escalated_conversations = len([
            c for c in self.conversations.values()
            if c.status == ConversationStatus.ESCALATED.value
        ])

        intent_counts = {}
        for conv in self.conversations.values():
            if conv.current_intent:
                intent_counts[conv.current_intent] = intent_counts.get(conv.current_intent, 0) + 1

        total_messages = sum(len(c.messages) for c in self.conversations.values())

        return {
            "total_conversations": total_conversations,
            "active_conversations": active_conversations,
            "escalated_conversations": escalated_conversations,
            "total_messages": total_messages,
            "intent_distribution": intent_counts
        }