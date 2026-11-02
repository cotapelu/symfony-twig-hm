"""Social Feed API - Database-backed with async operations"""
from fastapi import FastAPI, HTTPException, Depends
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from typing import List
import logging
import uuid
from datetime import datetime, timedelta
import hashlib
import hmac
import json

from models.schemas import SocialPost, SocialComment, UserFollow, SocialFeedItem
from service.engine import SocialFeedEngine

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="Social Feed API", version="1.0.0")
engine = SocialFeedEngine()
security = HTTPBearer()

SECRET_KEY = "your-secret-key-change-in-production"
TOKEN_VALIDITY = 3600

tokens: dict = {}
token_lock = __import__('threading').Lock()

def create_token(user_id: str) -> str:
    token_id = str(uuid.uuid4())
    expires = datetime.now().timestamp() + TOKEN_VALIDITY
    payload = json.dumps({"user_id": user_id, "expires": expires})
    token = f"{token_id}.{hashlib.sha256((payload + SECRET_KEY).encode()).hexdigest()}"
    with token_lock:
        tokens[token] = {"user_id": user_id, "expires": expires}
    return token

def verify_token(credentials: HTTPAuthorizationCredentials = Depends(security)) -> str:
    token = credentials.credentials
    with token_lock:
        token_data = tokens.get(token)
    if not token_data:
        raise HTTPException(status_code=401, detail="Invalid token")
    if datetime.now().timestamp() > token_data["expires"]:
        with token_lock:
            tokens.pop(token, None)
        raise HTTPException(status_code=401, detail="Token expired")
    return token_data["user_id"]

def validate_user_id(user_id: str) -> None:
    if not user_id or not user_id.strip():
        raise HTTPException(status_code=400, detail="user_id is required")
    if len(user_id) > 100:
        raise HTTPException(status_code=400, detail="user_id too long")

def validate_post_content(content: str) -> None:
    if not content or not content.strip():
        raise HTTPException(status_code=400, detail="Content is required")
    if len(content) > 5000:
        raise HTTPException(status_code=400, detail="Content too long (max 5000 chars)")

def validate_comment_content(content: str) -> None:
    if not content or not content.strip():
        raise HTTPException(status_code=400, detail="Comment content is required")
    if len(content) > 1000:
        raise HTTPException(status_code=400, detail="Comment too long (max 1000 chars)")

@app.on_event("startup")
async def startup_event():
    await engine.init()
    logger.info("SocialFeed engine initialized with database")

@app.post("/api/auth/token")
async def get_token(user_id: str):
    validate_user_id(user_id)
    token = create_token(user_id)
    logger.info(f"Token created for user {user_id}")
    return {"token": token, "expires_in": TOKEN_VALIDITY}

@app.post("/api/posts", response_model=SocialPost)
async def create_post(content: str, current_user: str = Depends(verify_token)):
    validate_post_content(content)
    post = await engine.create_post(current_user, content)
    logger.info(f"User {current_user} created post {post.id}")
    return post

@app.get("/api/posts/{post_id}")
async def get_post(post_id: str, current_user: str = Depends(verify_token)):
    post = await engine.get_post(post_id)
    if post:
        return post
    raise HTTPException(status_code=404, detail="Post not found")

@app.get("/api/posts/user/{user_id}", response_model=List[SocialPost])
async def get_user_posts(user_id: str, limit: int = 20, current_user: str = Depends(verify_token)):
    validate_user_id(user_id)
    if user_id != current_user:
        raise HTTPException(status_code=403, detail="Cannot access other user's posts")
    posts = await engine.get_user_posts(user_id, min(limit, 100))
    return posts

@app.put("/api/posts/{post_id}/like")
async def toggle_like(post_id: str, current_user: str = Depends(verify_token)):
    liked = await engine.toggle_like(post_id, current_user)
    logger.info(f"User {current_user} {'liked' if liked else 'unliked'} post {post_id}")
    count = await engine.get_post_likes_count(post_id)
    return {"is_liked": liked, "likes_count": count}

@app.get("/api/posts/{post_id}/likes")
async def get_post_likes(post_id: str, current_user: str = Depends(verify_token)) -> List[str]:
    return await engine.get_post_likes(post_id)

@app.post("/api/posts/{post_id}/comments", response_model=SocialComment)
async def add_comment(post_id: str, content: str, current_user: str = Depends(verify_token)):
    validate_comment_content(content)
    comment = await engine.add_comment(SocialComment(post_id=post_id, user_id=current_user, content=content))
    logger.info(f"User {current_user} commented on post {post_id}")
    return comment

@app.get("/api/posts/{post_id}/comments", response_model=List[SocialComment])
async def get_post_comments(post_id: str, current_user: str = Depends(verify_token)):
    return await engine.get_post_comments(post_id)

@app.put("/api/comments/{comment_id}/like")
async def toggle_comment_like(comment_id: str, current_user: str = Depends(verify_token)):
    liked = await engine.toggle_comment_like(comment_id, current_user)
    return {"is_liked": liked}

@app.post("/api/users/{target_id}/follow")
async def follow_user(target_id: str, current_user: str = Depends(verify_token)):
    validate_user_id(target_id)
    if target_id == current_user:
        raise HTTPException(status_code=400, detail="Cannot follow yourself")
    success = await engine.follow_user(current_user, target_id)
    if success:
        logger.info(f"User {current_user} followed {target_id}")
        return {"status": "following"}
    raise HTTPException(status_code=400, detail="Already following")

@app.delete("/api/users/{target_id}/follow")
async def unfollow_user(target_id: str, current_user: str = Depends(verify_token)):
    success = await engine.unfollow_user(current_user, target_id)
    if success:
        logger.info(f"User {current_user} unfollowed {target_id}")
        return {"status": "unfollowed"}
    raise HTTPException(status_code=400, detail="Not following")

@app.get("/api/users/{user_id}/followers")
async def get_followers(user_id: str, current_user: str = Depends(verify_token)) -> List[str]:
    validate_user_id(user_id)
    return await engine.get_followers(user_id)

@app.get("/api/users/{user_id}/following")
async def get_following(user_id: str, current_user: str = Depends(verify_token)) -> List[str]:
    validate_user_id(user_id)
    return await engine.get_following(user_id)

@app.get("/api/feed", response_model=List[SocialFeedItem])
async def get_user_feed(limit: int = 20, current_user: str = Depends(verify_token)):
    items = await engine.get_user_feed(current_user, min(limit, 100))
    return items

@app.get("/api/posts/{post_id}/owner")
async def check_post_owner(post_id: str, current_user: str = Depends(verify_token)):
    post = await engine.get_post(post_id)
    if not post:
        raise HTTPException(status_code=404, detail="Post not found")
    return {"is_owner": post.user_id == current_user, "post_owner": post.user_id}

@app.delete("/api/posts/{post_id}")
async def delete_post(post_id: str, current_user: str = Depends(verify_token)):
    post = await engine.get_post(post_id)
    if not post:
        raise HTTPException(status_code=404, detail="Post not found")
    if post.user_id != current_user:
        raise HTTPException(status_code=403, detail="Only post owner can delete")
    success = await engine.delete_post(post_id, current_user)
    if success:
        logger.info(f"User {current_user} deleted post {post_id}")
        return {"status": "deleted"}
    raise HTTPException(status_code=400, detail="Delete failed")
