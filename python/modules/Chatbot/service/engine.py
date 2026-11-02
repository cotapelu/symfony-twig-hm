from typing import List, Dict, Optional
from datetime import datetime

from ..models.schemas import ChatMessage, Conversation, ChatbotResponse, Intent, ConversationContext, KnowledgeBaseArticle
from datetime import datetime
import json
import os
import logging
import re

logger = logging.getLogger(__name__)

class ChatbotEngine:
    def __init__(self, ets_file: str = "/dev/shm/data/chatbot.json"):
        self.intents: Dict[str, Intent] = {}
        self.knowledge_base: List[KnowledgeBaseArticle] = []
        self.conversations: Dict[str, Conversation] = {}
        self.contexts: Dict[str, ConversationContext] = {}
        self.ets_file = ets_file
        self.next_id = 1

        self.ensure_data_directory()
        self._load_default_intents()
        self.restore_from_ets()

    def _load_default_intents(self):
        intents = [
            Intent(
                name="greeting",
                examples=["hello", "hi", "hey", "good morning", "good evening"],
                response_template="Hello! How can I help you today?",
                follow_up_intents=["help", "information"]
            ),
            Intent(
                name="help",
                examples=["help", "what can you do", "assist me", "support"],
                response_template="I can help you with:\n- Ordering food and groceries\n- Booking rides\n- Making payments\n- Answering questions\n- And much more! What would you like help with?",
                follow_up_intents=["order_food", "book_ride", "make_payment"]
            ),
            Intent(
                name="order_food",
                examples=["order food", "I want to eat", "hungry", "food delivery"],
                response_template="Great! I can help you order food. What type of cuisine are you in the mood for?",
                required_entities=["cuisine_type"],
                follow_up_intents=["confirm_order", "cancel"]
            ),
            Intent(
                name="order_grocery",
                examples=["order groceries", "grocery delivery", "supermarket"],
                response_template="I can help you order groceries. What items would you like to add to your cart?",
                follow_up_intents=["checkout"]
            ),
            Intent(
                name="book_ride",
                examples=["book a ride", "taxi", "grab", "driver"],
                response_template="I'll help you book a ride. What's your pickup location?",
                required_entities=["pickup_location"],
                follow_up_intents=["confirm_ride", "cancel_ride"]
            ),
            Intent(
                name="check_status",
                examples=["check status", "where is my order", "track order"],
                response_template="Let me check that for you. Could you provide your order number?",
                required_entities=["order_number"]
            ),
            Intent(
                name="payment",
                examples=["pay", "payment", "transfer money", "top up"],
                response_template="I can help you with payments. What would you like to do?",
                follow_up_intents=["top_up_wallet", "transfer", "bill_payment"]
            ),
            Intent(
                name="complaint",
                examples=["complaint", "problem", "issue", "not satisfied"],
                response_template="I'm sorry to hear that. Could you please describe the issue so I can help resolve it?",
                follow_up_intents=["refund_request", "speak_to_agent"]
            ),
            Intent(
                name="goodbye",
                examples=["bye", "goodbye", "see you", "thank you", "thanks"],
                response_template="Thank you for chatting with me! Have a great day!",
                follow_up_intents=[]
            )
        ]
        for intent in intents:
            self.intents[intent.name] = intent

    def process_message(self, user_id: str, conversation_id: str, message: str) -> ChatbotResponse:
        return self._process_message_impl(user_id, conversation_id, message)

    def _process_message_impl(self, user_id: str, conversation_id: str, message: str) -> ChatbotResponse:
        if conversation_id not in self.conversations:
            self.conversations[conversation_id] = Conversation(id=conversation_id, user_id=user_id, channel="chat", participants=[user_id])
        
        if user_id not in self.contexts:
            self.contexts[user_id] = ConversationContext(user_id=user_id)
        
        ctx = self.contexts[user_id]
        conv = self.conversations[conversation_id]
        
        intent, confidence, entities = self._detect_intent(message)
        
        msg = ChatMessage(
            id=f"msg_{len(conv.messages)}",
            conversation_id=conversation_id,
            sender_type="user",
            content=message,
            intent=intent,
            entities=entities,
            confidence=confidence
        )
        conv.messages.append(msg)
        
        response_text = self._generate_response(intent, ctx, entities)
        
        if intent and intent in self.intents:
            ctx.current_intent = intent
            for entity in entities:
                ctx.slots[entity.name] = entity.value
            if self.intents[intent].follow_up_intents:
                suggestions = self.intents[intent].follow_up_intents[:3]
            else:
                suggestions = ["help", "order food", "check status"]
        else:
            suggestions = ["help", "what can you do"]
        
        ctx.history.append({"role": "user", "content": message, "intent": intent})
        
        response_msg = ChatMessage(
            id=f"msg_{len(conv.messages)}",
            conversation_id=conversation_id,
            sender_type="bot",
            content=response_text,
            intent=intent
        )
        conv.messages.append(response_msg)
        ctx.history.append({"role": "bot", "content": response_text})
        
        conv.updated_at = datetime.now()
        
        # Convert entities list to Entity objects if needed
        entity_objects = []
        for i, ent in enumerate(entities):
            if isinstance(ent, str):
                entity_objects.append(Entity(name=f"entity_{i}", value=ent, confidence=0.8))
            else:
                entity_objects.append(ent)
        
        response = ChatbotResponse(
            message=response_text,
            intent=intent or "unknown",
            entities=entity_objects,
            suggestions=suggestions,
            context_updates={"slots": ctx.slots}
        )
        
        # Persist conversation after each message
        self.persist_to_ets()
        
        return response

    def _detect_intent(self, message: str) -> tuple:
        message_lower = message.lower()
        
        best_intent = "unknown"
        best_confidence = 0.0
        detected_entities = []
        
        for intent_name, intent in self.intents.items():
            for example in intent.examples:
                if example in message_lower:
                    if len(example) / len(message_lower) > best_confidence:
                        best_intent = intent_name
                        best_confidence = len(example) / len(message_lower)
        
        if best_confidence < 0.3:
            best_intent = "help"
            best_confidence = 0.5
        
        return best_intent, best_confidence, detected_entities

    def _generate_response(self, intent: str, ctx: ConversationContext, entities: List) -> str:
        if intent in self.intents:
            template = self.intents[intent].response_template
            slots = ctx.slots
            
            for slot_name, slot_value in slots.items():
                template = template.replace(f"{{{slot_name}}}", slot_value)
            
            return template
        
        return "I'm not sure I understand. Could you please rephrase that?"

    def add_knowledge_base_article(self, article_data: Dict):
        """Add an article to the knowledge base.
        Accepts a dict with keys: id, title, content, keywords, [category], [confidence], [usage_count].
        Maps title->question, content->answer for the KnowledgeBaseArticle model.
        """
        article = KnowledgeBaseArticle(
            id=article_data['id'],
            question=article_data.get('title', ''),
            answer=article_data.get('content', ''),
            category=article_data.get('category', 'general'),
            keywords=article_data.get('keywords', []),
            confidence=article_data.get('confidence', 0.9),
            usage_count=article_data.get('usage_count', 0)
        )
        self.knowledge_base.append(article)

    def search_knowledge_base(self, query: str) -> Optional[str]:
        query_lower = query.lower()
        best_match = None
        best_score = 0
        
        for article in self.knowledge_base:
            score = 0
            for keyword in article.keywords:
                if keyword in query_lower:
                    score += 1
            if article.question.lower() in query_lower:
                score += 3
            if score > best_score and score > 0:
                best_score = score
                best_match = article.answer
        
        return best_match

    def ensure_data_directory(self):
        dir_path = os.path.dirname(self.ets_file)
        if dir_path and dir_path != "." and not os.path.exists(dir_path):
            os.makedirs(dir_path, exist_ok=True)
            logger.info(f"Created data directory: {dir_path}")

    def restore_from_ets(self):
        return self._restore_from_ets_impl()

    def _restore_from_ets_impl(self):
        if not os.path.exists(self.ets_file):
            logger.info("No existing ETS file found, starting fresh")
            return

        try:
            with open(self.ets_file, 'r') as f:
                data = json.load(f)

            if 'conversations' in data:
                for k, v in data['conversations'].items():
                    messages = []
                    for m in v.get('messages', []):
                        msg_data = m if isinstance(m, dict) else {}
                        messages.append(ChatMessage(
                            id=msg_data.get('id', f'msg_{len(messages)}'),
                            conversation_id=v['id'],
                            sender_type=msg_data.get('sender_type', 'unknown'),
                            content=msg_data.get('content', ''),
                            intent=msg_data.get('intent'),
                            created_at=datetime.fromisoformat(msg_data.get('created_at', datetime.now().isoformat()))
                        ))

                    self.conversations[k] = Conversation(
                        id=v['id'],
                        user_id=v['user_id'],
                        channel=v.get('channel', 'chat'),
                        status=v.get('status', 'active'),
                        messages=messages,
                        created_at=datetime.fromisoformat(v.get('created_at', datetime.now().isoformat())),
                        updated_at=datetime.fromisoformat(v.get('updated_at', datetime.now().isoformat()))
                    )

            if 'intents' in data:
                for k, v in data['intents'].items():
                    self.intents[k] = Intent(
                        name=v['name'],
                        examples=v.get('examples', []),
                        response_template=v.get('response_template', ''),
                        required_entities=v.get('required_entities', []),
                        follow_up_intents=v.get('follow_up_intents', [])
                    )

            if 'next_id' in data:
                self.next_id = data['next_id'] + 1

            logger.info(f"Restored {len(self.conversations)} conversations, {len(self.intents)} intents")

        except Exception as e:
            logger.error(f"Error reading ETS file: {e}")

    def persist_to_ets(self):
        return self._persist_to_ets_impl()

    def _persist_to_ets_impl(self):
        data = {
            'conversations': {
                k: {
                    'id': v.id,
                    'user_id': v.user_id,
                    'channel': v.channel,
                    'status': v.status,
                    'messages': [
                        {
                            'id': m.id,
                            'sender_type': m.sender_type,
                            'content': m.content,
                            'intent': m.intent,
                            'created_at': m.created_at.isoformat()
                        } for m in v.messages
                    ],
                    'created_at': v.created_at.isoformat(),
                    'updated_at': v.updated_at.isoformat()
                } for k, v in self.conversations.items()
            },
            'intents': {
                k: {
                    'name': v.name,
                    'examples': v.examples,
                    'response_template': v.response_template,
                    'required_entities': v.required_entities,
                    'follow_up_intents': v.follow_up_intents
                } for k, v in self.intents.items()
            },
            'next_id': self.next_id
        }

        with open(self.ets_file, 'w') as f:
            json.dump(data, f, indent=2)

        logger.info(f"Chatbot data persisted to {self.ets_file}")

    def get_conversation(self, conversation_id: str) -> Optional[Conversation]:
        """Retrieve a conversation by ID."""
        return self.conversations.get(conversation_id)

    def end_conversation(self, conversation_id: str) -> Optional[Dict]:
        """End a conversation (set status to closed)."""
        conv = self.conversations.get(conversation_id)
        if not conv:
            return None
        conv.status = "closed"
        conv.updated_at = datetime.now()
        self.persist_to_ets()
        return {"status": "closed"}

    def terminate(self):
        self.persist_to_ets()
        logger.info("ChatbotEngine terminating")
