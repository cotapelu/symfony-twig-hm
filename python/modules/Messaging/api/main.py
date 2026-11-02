from fastapi import FastAPI, HTTPException, Depends, Request
from typing import List, Optional
from models.schemas import Conversation, Message, GroupChat
from service.engine import MessagingEngine
from observability.setup import setup_observability, health_checker, CircuitBreaker

app = FastAPI(title="Messaging API", version="1.0.0")
engine = MessagingEngine()

# Setup observability (logging, metrics, health checks)
setup_observability(app)

# Register custom health checks
health_checker.add_check("messaging_engine", lambda: {"status": "healthy", "conversations": len(engine.conversations)})

# Circuit breaker for external service calls (placeholder)
message_circuit = CircuitBreaker(failure_threshold=5, recovery_timeout=60)

# Authentication dependency (mock - use real JWT in production)
async def get_current_user(request: Request):
	authorization = request.headers.get("Authorization")
	if not authorization or not authorization.startswith("Bearer "):
		raise HTTPException(status_code=401, detail="Authentication required")
	token = authorization.split(" ")[1]
	# Mock: decode token and get user_id
	# In production: validate JWT token
	return {"user_id": "user_from_token", "token": token}

@app.post("/api/conversations", response_model=Conversation)
async def create_conversation(request: Request, current_user: dict = Depends(get_current_user)):
	body = await request.json()
	participants = body.get("participants", [])
	
	# Validation
	if len(participants) < 2:
		raise HTTPException(status_code=400, detail="At least 2 participants required")
	
	# Authorization: user can only create conversations they're part of
	if current_user["user_id"] not in participants:
		raise HTTPException(status_code=403, detail="Cannot create conversation without yourself")
	
	return engine.create_conversation(participants)

@app.get("/api/conversations/{user_id}", response_model=List[Conversation])
async def get_user_conversations(user_id: str):
    return engine.get_user_conversations(user_id)

@app.get("/api/conversations/{conversation_id}/messages", response_model=List[Message])
async def get_conversation_messages(conversation_id: str, limit: int = 50):
    return engine.get_conversation_messages(conversation_id, limit)

@app.post("/api/messages", response_model=Message)
async def send_message(message: Message):
    return engine.send_message(message)

@app.put("/api/messages/{message_id}/read")
async def mark_message_read(message_id: str, user_id: str):
    success = engine.mark_message_read(message_id, user_id)
    if success:
        return {"status": "read"}
    raise HTTPException(status_code=404, detail="Message not found")

@app.post("/api/conversations/{conversation_id}/leave")
async def leave_conversation(conversation_id: str, user_id: str):
    success = engine.leave_conversation(conversation_id, user_id)
    if success:
        return {"status": "left"}
    raise HTTPException(status_code=404, detail="Conversation not found")

@app.post("/api/group-chats", response_model=GroupChat)
async def create_group_chat(chat: GroupChat):
    return engine.create_group_chat(chat)

@app.get("/api/group-chats/{user_id}", response_model=List[GroupChat])
async def get_user_group_chats(user_id: str):
    return engine.get_user_group_chats(user_id)

@app.get("/api/unread-count/{user_id}")
async def get_unread_count(user_id: str):
    return engine.get_unread_count(user_id)
