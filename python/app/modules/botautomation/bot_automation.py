import openai
from typing import List, Dict, Any, Optional
from datetime import datetime
import json

class BotAutomationService:
    """AI-powered chatbot and automation service using OpenAI GPT"""
    
    def __init__(self, api_key: str):
        self.client = openai.OpenAI(api_key=api_key)
        self.conversations: Dict[str, List[Dict]] = {}
        
    def create_conversation(self, user_id: str, bot_config: Optional[Dict] = None) -> str:
        """Create a new conversation with the bot"""
        conversation_id = f"conv_{datetime.now().timestamp()}"
        system_message = bot_config or {
            "role": "system",
            "content": "You are a helpful AI assistant for a communication platform."
        }
        self.conversations[conversation_id] = [system_message]
        return conversation_id
    
    def send_message(self, *args, **kwargs):
        return self._send_message_impl(*args, **kwargs)
    def _send_message_impl(self, conversation_id: str, user_message: str) -> Dict[str, Any]:
        """Send a message to the bot and get a response"""
        if conversation_id not in self.conversations:
            return {"error": "Conversation not found"}
        
        # Add user message
        self.conversations[conversation_id].append({
            "role": "user",
            "content": user_message
        })
        
        try:
            # Get AI response
            response = self.client.chat.completions.create(
                model="gpt-3.5-turbo",
                messages=self.conversations[conversation_id],
                temperature=0.7,
                max_tokens=500
            )
            
            bot_message = response.choices[0].message.content
            
            # Add bot message to conversation
            self.conversations[conversation_id].append({
                "role": "assistant",
                "content": bot_message
            })
            
            return {
                "message": bot_message,
                "timestamp": datetime.now().isoformat()
            }
            
        except Exception as e:
            return {"error": str(e)}
    
    def classify_intent(self, *args, **kwargs):
        return self._classify_intent_impl(*args, **kwargs)
    def _classify_intent_impl(self, message: str) -> Dict[str, Any]:
        """Classify the intent of a user message"""
        try:
            response = self.client.chat.completions.create(
                model="gpt-3.5-turbo",
                messages=[
                    {
                        "role": "system",
                        "content": "Classify the following message into one of these intents: greeting, question, complaint, request, goodbye. Return only the intent name."
                    },
                    {
                        "role": "user",
                        "content": message
                    }
                ],
                temperature=0.3,
                max_tokens=50
            )
            
            intent = response.choices[0].message.content.strip().lower()
            confidence = 0.8  # Simulated confidence
            
            return {
                "intent": intent,
                "confidence": confidence
            }
            
        except Exception as e:
            return {"error": str(e)}
    
    def extract_entities(self, *args, **kwargs):
        return self._extract_entities_impl(*args, **kwargs)
    def _extract_entities_impl(self, message: str) -> Dict[str, List[str]]:
        """Extract entities (dates, names, locations) from message"""
        entities = {
            "dates": [],
            "names": [],
            "locations": [],
            "numbers": []
        }
        
        # Simple regex-based extraction (in production, use NER)
        import re
        
        # Extract numbers
        numbers = re.findall(r'\b\d+\b', message)
        entities["numbers"] = numbers
        
        # Extract dates (simple pattern)
        dates = re.findall(r'\b\d{4}-\d{2}-\d{2}\b|\b\d{2}/\d{2}/\d{4}\b', message)
        entities["dates"] = dates
        
        return entities
    
    def generate_response(self, intent: str, entities: Dict[str, List[str]], context: Optional[Dict] = None) -> str:
        """Generate a response based on intent and entities"""
        context = context or {}
        
        responses = {
            "greeting": "Hello! How can I help you today?",
            "question": "I'd be happy to answer your question. What would you like to know?",
            "complaint": "I'm sorry to hear you're having trouble. Let me help you resolve this issue.",
            "request": "I can help with that request. Please provide more details.",
            "goodbye": "Goodbye! Have a great day!"
        }
        
        base_response = responses.get(intent, "I'm not sure I understand. Could you please rephrase?")
        
        # Add context if available
        if entities.get("dates"):
            base_response += f" I see you mentioned: {', '.join(entities['dates'])}."
        
        return base_response
    
    def close_conversation(self, conversation_id: str) -> bool:
        """Close and remove a conversation"""
        if conversation_id in self.conversations:
            del self.conversations[conversation_id]
            return True
        return False
    
    def get_conversation_history(self, conversation_id: str) -> Optional[List[Dict]]:
        """Get the full conversation history"""
        return self.conversations.get(conversation_id)
