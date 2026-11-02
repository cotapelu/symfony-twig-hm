"""AI Service using unified database models."""
import os
import hashlib
import json
import asyncio
from typing import List, Dict, Any, Optional
from datetime import datetime, timedelta

try:
    from .openai_service import OpenAIService
    OPENAI_AVAILABLE = True
except ImportError:
    OPENAI_AVAILABLE = False

from app.shared.database import db
from app.shared.models import (
    Conversation, ConversationType, MLInference, MLModel
)

from sqlalchemy import select


class AIService:
    """AI/ML services with unified database persistence."""

    def __init__(self, event_bus=None):
        self.event_bus = event_bus
        self.use_openai = OPENAI_AVAILABLE and bool(os.getenv('OPENAI_API_KEY'))
        self.openai_client = None

        if self.use_openai and OPENAI_AVAILABLE:
            try:
                self.openai_client = OpenAIService()
            except Exception:
                self.openai_client = None

        self.db = db

    async def init_db(self):
        pass

    async def generate_smart_replies(self, context: List[Dict[str, Any]], last_message: str, user_id: str = None) -> List[str]:
        """Generate smart replies based on context and last message."""
        if not last_message:
            return ["Hello! How can I help you today?", "Hi there!", "Good day!"]
        try:
            if user_id:
                history = await self._get_recent_history(user_id)
                if history:
                    context = history + context[-10:]
        except Exception as e:
            print(f"Error loading conversation history: {e}")

        if self.use_openai and self.openai_client:
            return await self.openai_client.generate_smart_replies(context, last_message)

        last_message_lower = last_message.lower()
        if any(word in last_message_lower for word in ["question", "how", "what", "why"]):
            return ["That's a great question!", "Let me think about that...", "I'm not entirely sure, but..."]
        if any(word in last_message_lower for word in ["thank", "thanks", "appreciate"]):
            return ["You're welcome!", "Happy to help!", "No problem at all!"]
        return ["That's interesting!", "I understand.", "Could you tell me more?", "Sounds good to me!"]

    async def _get_recent_history(self, user_id: str, limit: int = 5) -> List[Dict[str, Any]]:
        async with self.db.get_session() as session:
            result = await session.execute(
                select(Conversation)
                .where(Conversation.user_id == user_id)
                .where(Conversation.type == ConversationType.CHATBOT)
                .order_by(Conversation.started_at.desc())
                .limit(1)
            )
            conversation = result.scalar_one_or_none()
            if not conversation or not conversation.messages:
                return []
            import json
            messages = json.loads(conversation.messages)
            # Return last N messages (each is {role, content, timestamp})
            return messages[-limit:] if messages else []

    async def analyze_sentiment(self, text: str) -> Dict[str, Any]:
        content_hash = hashlib.sha256(text.encode()).hexdigest()
        if self.use_openai and self.openai_client:
            return self.openai_client.analyze_sentiment(text)
        text_lower = text.lower()
        pos = sum(1 for w in ["good", "great", "awesome", "love", "happy"] if w in text_lower)
        neg = sum(1 for w in ["bad", "terrible", "hate", "sad"] if w in text_lower)
        total = pos + neg
        if total == 0:
            sentiment = "neutral"
            confidence = 0.5
        elif pos > neg:
            sentiment = "positive"
            confidence = pos / total
        else:
            sentiment = "negative"
            confidence = neg / total
        result = {"sentiment": sentiment, "confidence": confidence}
        try:
            await self._cache_inference(content_hash, "sentiment", text, result)
        except:
            pass
        return result

    def translate_text(self, text: str, source_lang: str, target_lang: str) -> Dict[str, Any]:
        if source_lang == target_lang:
            return {"original_text": text, "translated_text": text, "source_language": source_lang, "target_language": target_lang}
        if self.use_openai and self.openai_client:
            return self.openai_client.translate_text(text, source_lang, target_lang)
        translations = {
            "en": {"es": "Hola", "fr": "Bonjour", "de": "Hallo", "vi": "Xin chào"},
            "es": {"en": "Hello", "fr": "Bonjour", "de": "Hallo"},
        }
        if source_lang in translations and target_lang in translations[source_lang]:
            translated = translations[source_lang][target_lang]
        else:
            translated = f"[{target_lang}] {text}"
        return {"original_text": text, "translated_text": translated, "source_language": source_lang, "target_language": target_lang}

    async def moderate_content(self, content: str, content_type: str = "text") -> Dict[str, Any]:
        content_hash = hashlib.sha256(content.encode()).hexdigest()
        try:
            cached = await self._get_cached_inference(content_hash, model_type="moderation")
            if cached:
                import json
                return {
                    "is_violation": json.loads(cached.prediction).get("is_violation", False),
                    "severity": json.loads(cached.prediction).get("severity", 0.0),
                    "violations": json.loads(cached.prediction).get("violations", []),
                    "action": json.loads(cached.prediction).get("action", "allow"),
                    "content_type": content_type
                }
        except:
            pass
        if self.use_openai and self.openai_client:
            result = self.openai_client.moderate_content(content, content_type)
        else:
            prohibited = ["spam", "abuse", "hate", "violence"]
            violations = [w for w in prohibited if w in content.lower()]
            is_violation = len(violations) > 0
            severity = len(violations) / len(prohibited)
            action = "block" if severity > 0.5 else "flag" if severity > 0.3 else "allow"
            result = {"is_violation": is_violation, "severity": severity, "violations": violations, "action": action, "content_type": content_type}
        try:
            await self._cache_inference(content_hash, "moderation", content, result)
        except:
            pass
        return result

    def generate_content(self, content_type: str, prompt: str, user_id: Optional[str] = None) -> Dict[str, Any]:
        if self.use_openai and self.openai_client:
            result = self.openai_client.generate_content(content_type, prompt)
        else:
            if content_type == "text":
                generated = f"Generated text based on: {prompt}"
            elif content_type == "image":
                generated = f"[Generated image prompt: {prompt}]"
            elif content_type == "code":
                generated = f"# Generated code\nprint('Hello from: {prompt}')"
            else:
                generated = f"Generated {content_type} content"
            result = {"content_type": content_type, "prompt": prompt, "generated_content": generated}
        if user_id:
            try:
                loop = asyncio.get_event_loop()
                loop.run_until_complete(self._log_generated_content(user_id, content_type, prompt, result.get("generated_content", "")))
            except:
                pass
        return result

    def generate_summary(self, conversation_id: str, messages: Optional[List[str]] = None, user_id: Optional[str] = None) -> Dict[str, Any]:
        if self.use_openai and self.openai_client and messages:
            return self.openai_client.generate_summary(conversation_id, messages)
        return {
            "conversation_id": conversation_id,
            "summary": "Mock conversation summary",
            "key_points": ["Point 1", "Point 2", "Point 3"],
            "action_items": [],
            "participant_count": 2
        }

    async def _get_cached_inference(self, content_hash: str, model_type: str = None):
        return None

    async def _cache_inference(self, content_hash: str, model_type: str, input_data: Any, prediction: Dict[str, Any]):
        async with self.db.get_session() as session:
            import json
            inference = MLInference(
                model_id=None,
                user_id=None,
                input_data=json.dumps({"hash": content_hash, "input": input_data}),
                prediction=json.dumps(prediction),
                confidence=None,
                latency_ms=None
            )
            session.add(inference)
            await session.commit()

    async def _log_generated_content(self, user_id: str, content_type: str, prompt: str, generated: str):
        async with self.db.get_session() as session:
            import json
            inference = MLInference(
                model_id=None,
                user_id=user_id,
                input_data=json.dumps({"prompt": prompt, "type": content_type}),
                prediction=json.dumps({"generated": generated}),
                confidence=None,
                latency_ms=None
            )
            session.add(inference)
            await session.commit()


AIServiceV2 = AIService
