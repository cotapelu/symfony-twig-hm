"""OpenAI API integration service"""
import os
from typing import List, Dict, Any, Optional
from openai import OpenAI, OpenAIError
from observability.resilience import retry_with_backoff_sync


class OpenAIService:
    """Real OpenAI API integration for AI/ML features"""
    
    def __init__(self, api_key: Optional[str] = None):
        self.api_key = api_key or os.getenv('OPENAI_API_KEY')
        self.client = OpenAI(api_key=self.api_key) if self.api_key else None
        self.model = os.getenv('OPENAI_MODEL', 'gpt-3.5-turbo')
        self.max_tokens = int(os.getenv('OPENAI_MAX_TOKENS', '1000'))
    
    def generate_smart_replies(self, context: List[Dict[str, Any]], last_message: str) -> List[str]:
        return self._generate_smart_replies_impl(context, last_message)

    def _generate_smart_replies_impl(self, context: List[Dict[str, Any]], last_message: str) -> List[str]:
        """Generate smart replies using OpenAI GPT"""
        if not self.client:
            return ["I'm sorry, but the AI service is not configured."]
        
        if not last_message:
            return ["Hello! How can I help you today?", "Hi there!", "Good day!"]
        
        try:
            messages = [
                {"role": "system", "content": "You are a helpful assistant in a messaging app. Generate 3 short, friendly replies."},
            ]
            
            for msg in context[-10:]:
                messages.append({
                    "role": "user",
                    "content": msg.get('content', '')
                })
            
            messages.append({
                "role": "user",
                "content": last_message
            })
            
            response = retry_with_backoff_sync(
                lambda: self.client.chat.completions.create(
                    model=self.model,
                    messages=messages,
                    max_tokens=self.max_tokens // 3,
                    n=3,
                    temperature=0.7
                ),
                max_retries=3,
                base_delay=0.1,
                max_delay=10.0,
                retry_on=(OpenAIError,)
            )
            
            replies = [choice.message.content.strip() for choice in response.choices]
            return replies
            
        except OpenAIError as e:
            return ["I'm having trouble generating a response. Please try again."]
    
    def analyze_sentiment(self, text: str) -> Dict[str, Any]:
        return self._analyze_sentiment_impl(text)

    def _analyze_sentiment_impl(self, text: str) -> Dict[str, Any]:
        """Analyze sentiment using OpenAI GPT"""
        if not self.client:
            return {"sentiment": "neutral", "confidence": 0.5}
        
        try:
            response = retry_with_backoff_sync(
                lambda: self.client.chat.completions.create(
                    model=self.model,
                    messages=[{
                        "role": "system",
                        "content": "Analyze the sentiment of the following text. Return JSON with: sentiment (positive/negative/neutral), confidence (0-1), positive_score, negative_score"
                    }, {
                        "role": "user",
                        "content": text
                    }],
                    max_tokens=150,
                    temperature=0.3,
                    response_format={"type": "json_object"}
                ),
                max_retries=3,
                base_delay=0.1,
                max_delay=10.0,
                retry_on=(OpenAIError,)
            )
            
            import json
            result = json.loads(response.choices[0].message.content)
            return result
            
        except OpenAIError as e:
            return {"sentiment": "neutral", "confidence": 0.5, "error": str(e)}
    
    def translate_text(self, text: str, source_lang: str, target_lang: str) -> Dict[str, Any]:
        return self._translate_text_impl(text, source_lang, target_lang)

    def _translate_text_impl(self, text: str, source_lang: str, target_lang: str) -> Dict[str, Any]:
        """Translate text using OpenAI GPT"""
        if not self.client or source_lang == target_lang:
            return {"original_text": text, "translated_text": text, "source_language": source_lang, "target_language": target_lang}
        
        try:
            response = retry_with_backoff_sync(
                lambda: self.client.chat.completions.create(
                    model=self.model,
                    messages=[{
                        "role": "system",
                        "content": f"Translate the following text from {source_lang} to {target_lang}. Return only the translation."
                    }, {
                        "role": "user",
                        "content": text
                    }],
                    max_tokens=500,
                    temperature=0.3
                ),
                max_retries=3,
                base_delay=0.1,
                max_delay=10.0,
                retry_on=(OpenAIError,)
            )
            
            translated = response.choices[0].message.content.strip()
            return {
                "original_text": text,
                "translated_text": translated,
                "source_language": source_lang,
                "target_language": target_lang
            }
            
        except OpenAIError as e:
            return {"error": str(e)}
    
    def moderate_content(self, content: str, content_type: str = "text") -> Dict[str, Any]:
        return self._moderate_content_impl(content, content_type)

    def _moderate_content_impl(self, content: str, content_type: str = "text") -> Dict[str, Any]:
        """Moderate content using OpenAI Moderation API"""
        if not self.client:
            return {"is_violation": False, "action": "allow"}
        
        try:
            moderation = retry_with_backoff_sync(
                lambda: self.client.moderations.create(input=content),
                max_retries=3,
                base_delay=0.1,
                max_delay=10.0,
                retry_on=(OpenAIError,)
            )
            
            result = moderation.results[0]
            
            if result.flagged:
                categories = [cat for cat, flagged in result.categories.model_dump().items() if flagged]
                return {
                    "is_violation": True,
                    "severity": "high",
                    "violations": categories,
                    "action": "block",
                    "content_type": content_type
                }
            
            return {
                "is_violation": False,
                "action": "allow",
                "content_type": content_type
            }
            
        except OpenAIError as e:
            return {"is_violation": False, "action": "allow", "error": str(e)}
    
    def generate_summary(self, conversation_id: str, messages: List[str]) -> Dict[str, Any]:
        return self._generate_summary_impl(conversation_id, messages)

    def _generate_summary_impl(self, conversation_id: str, messages: List[str]) -> Dict[str, Any]:
        """Generate summary of conversation using OpenAI GPT"""
        if not self.client or not messages:
            return {"conversation_id": conversation_id, "summary": "No messages to summarize"}
        
        try:
            conversation_text = "\n".join([f"Message: {msg}" for msg in messages[-20:]])
            
            response = retry_with_backoff_sync(
                lambda: self.client.chat.completions.create(
                    model=self.model,
                    messages=[{
                        "role": "system",
                        "content": "Summarize this conversation. Return JSON with: summary, key_points (array), action_items (array), participant_count"
                    }, {
                        "role": "user",
                        "content": conversation_text
                    }],
                    max_tokens=500,
                    temperature=0.5,
                    response_format={"type": "json_object"}
                ),
                max_retries=3,
                base_delay=0.1,
                max_delay=10.0,
                retry_on=(OpenAIError,)
            )
            
            import json
            result = json.loads(response.choices[0].message.content)
            result["conversation_id"] = conversation_id
            return result
            
        except OpenAIError as e:
            return {"conversation_id": conversation_id, "summary": "Unable to generate summary", "error": str(e)}
    
    def generate_content(self, content_type: str, prompt: str) -> Dict[str, Any]:
        return self._generate_content_impl(content_type, prompt)

    def _generate_content_impl(self, content_type: str, prompt: str) -> Dict[str, Any]:
        """Generate AI content using OpenAI GPT"""
        if not self.client:
            return {"error": "AI service not configured"}
        
        try:
            system_prompts = {
                "text": "You are a creative writing assistant. Generate the requested content.",
                "image": "Describe an image generation prompt based on the user's request.",
                "code": "You are a coding assistant. Generate clean, well-commented code."
            }
            
            response = retry_with_backoff_sync(
                lambda: self.client.chat.completions.create(
                    model=self.model,
                    messages=[{
                        "role": "system",
                        "content": system_prompts.get(content_type, "You are a helpful assistant.")
                    }, {
                        "role": "user",
                        "content": prompt
                    }],
                    max_tokens=self.max_tokens,
                    temperature=0.8
                ),
                max_retries=3,
                base_delay=0.1,
                max_delay=10.0,
                retry_on=(OpenAIError,)
            )
            
            return {
                "content_type": content_type,
                "prompt": prompt,
                "generated_content": response.choices[0].message.content.strip()
            }
            
        except OpenAIError as e:
            return {"content_type": content_type, "prompt": prompt, "error": str(e)}
