from fastapi import FastAPI, Depends, HTTPException, Request
from typing import Dict, Optional
from models.schemas import ChatMessage, Conversation, ChatbotResponse
from service.engine import ChatbotEngine

app = FastAPI(title="Chatbot API", version="1.0.0")
engine = ChatbotEngine()

# Mock authentication dependency (in production, use proper JWT/OAuth)
async def get_current_user(request: Request):
	authorization = request.headers.get("Authorization")
	if not authorization or not authorization.startswith("Bearer "):
		raise HTTPException(status_code=401, detail="Invalid authentication")
	# In production, validate token and extract user_id
	token = authorization.split(" ")[1]
	# Mock: extract user_id from token or return test user
	return {"user_id": "test_user", "token": token}

@app.post("/api/chat/{user_id}/{conversation_id}", response_model=ChatbotResponse)
async def chat(user_id: str, conversation_id: str, request: Request, current_user: Dict = Depends(get_current_user)):
	# Authorization check
	if current_user["user_id"] != user_id:
		raise HTTPException(status_code=403, detail="Not authorized to access this conversation")
	
	message = request.json.get("message", "")
	# Input validation
	if not message or not isinstance(message, str):
		raise HTTPException(status_code=400, detail="Message is required")
	if len(message) < 1:
		raise HTTPException(status_code=400, detail="Message cannot be empty")
	if len(message) > 2000:
		raise HTTPException(status_code=400, detail="Message is too long")
	
	response = engine.process_message(user_id, conversation_id, message)
	return response

@app.post("/api/chat/{user_id}", response_model=ChatbotResponse)
async def chat_new(user_id: str, request: Dict):
    message = request.get("message", "")
    import uuid
    conversation_id = str(uuid.uuid4())
    response = engine.process_message(user_id, conversation_id, message)
    return response

@app.get("/api/chat/{conversation_id}")
async def get_conversation(conversation_id: str):
    conv = engine.conversations.get(conversation_id)
    if not conv:
        return {"error": "Conversation not found"}
    return {
        "id": conv.id,
        "user_id": conv.user_id,
        "status": conv.status,
        "messages": [
            {"sender_type": m.sender_type, "content": m.content, "intent": m.intent}
            for m in conv.messages
        ]
    }

@app.post("/api/chat/{conversation_id}/end")
async def end_conversation(conversation_id: str):
    if conversation_id in engine.conversations:
        engine.conversations[conversation_id].status = "closed"
        return {"status": "closed"}
    return {"error": "Conversation not found"}

@app.post("/api/knowledge-base")
async def add_knowledge_base(article: Dict):
    from models.schemas import KnowledgeBaseArticle
    kb_article = KnowledgeBaseArticle(**article)
    engine.add_knowledge_base_article(kb_article)
    return {"status": "added"}
