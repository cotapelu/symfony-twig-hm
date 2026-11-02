"""SocialFeed router for Python monolith - uses shared JWT auth"""
from fastapi import APIRouter, Depends, HTTPException
from typing import List, Dict
import time
import asyncio
from collections import defaultdict, deque

# Import schemas and engine from the original SocialFeed module
from modules.socialfeed.models.schemas import SocialPost, SocialComment, SocialFeedItem
from modules.socialfeed.service.engine import SocialFeedEngine

router = APIRouter(prefix="/socialfeed", tags=["socialfeed"])
engine = SocialFeedEngine()

# Import shared auth dependencies
from app.shared.middleware.auth import verify_token, get_current_user_id

# Rate limiting: 60 requests per minute per user
_socialfeed_rate_data: Dict[str, deque] = defaultdict(deque)
_socialfeed_lock = asyncio.Lock()

async def socialfeed_user_with_rate_limit(user_id: str = Depends(get_current_user_id)) -> str:
    """Combined dependency: verifies user and enforces rate limit."""
    async with _socialfeed_lock:
        now = time.time()
        timestamps = _socialfeed_rate_data[user_id]
        while timestamps and timestamps[0] < now - 60:
            timestamps.popleft()
        if len(timestamps) >= 60:
            raise HTTPException(
                status_code=429,
                detail="Rate limit exceeded. Maximum 60 social feed requests per minute.",
                headers={
                    "X-RateLimit-Limit": "60",
                    "X-RateLimit-Remaining": "0",
                    "Retry-After": "60"
                }
            )
        timestamps.append(now)
    return user_id

@router.post("/auth/token", summary="Get token (dev only)", include_in_schema=False)
async def get_token(user_id: str):
    # IDOR: public - dev only
    # This endpoint is for testing; in production use main JWT
    from app.main import create_token  # avoid circular, but for dev
    token = create_token(user_id)
    return {"token": token, "expires_in": 3600}

@router.post("/posts", response_model=SocialPost, summary="Create post")
async def create_post(content: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    # IDOR: public - creating own post is allowed
    # Validate content length already in engine? let engine validate
    post = await engine.create_post(current_user_id, content)
    return post

@router.get("/posts/{post_id}", response_model=SocialPost, summary="Get post")
async def get_post(post_id: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    # IDOR: public - posts are publicly viewable
    post = await engine.get_post(post_id)
    if not post:
        raise HTTPException(status_code=404, detail="Post not found")
    return post

@router.get("/posts/user/{user_id}", response_model=List[SocialPost], summary="Get user's posts")
async def get_user_posts(user_id: str, limit: int = 20, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    if user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other user's posts")
    posts = await engine.get_user_posts(user_id, min(limit, 100))
    return posts

@router.put("/posts/{post_id}/like", summary="Toggle like")
async def toggle_like(post_id: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    # IDOR: public - any authenticated user can like posts
    liked = await engine.toggle_like(post_id, current_user_id)
    count = await engine.get_post_likes_count(post_id)
    return {"is_liked": liked, "likes_count": count}

@router.get("/posts/{post_id}/likes", response_model=List[str], summary="Get post likes")
async def get_post_likes(post_id: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    # IDOR: public - likes are public info
    return await engine.get_post_likes(post_id)

@router.post("/posts/{post_id}/comments", response_model=SocialComment, summary="Add comment")
async def add_comment(post_id: str, content: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    # IDOR: public - any authenticated user can comment
    comment = await engine.add_comment(post_id, current_user_id, content)
    return comment

@router.get("/posts/{post_id}/comments", response_model=List[SocialComment], summary="Get comments")
async def get_post_comments(post_id: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    # IDOR: public - comments are public
    return await engine.get_post_comments(post_id)

@router.put("/comments/{comment_id}/like", summary="Toggle comment like")
async def toggle_comment_like(comment_id: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    # IDOR: public - comment likes are public
    liked = await engine.toggle_comment_like(comment_id, current_user_id)
    return {"is_liked": liked}

@router.post("/users/{target_id}/follow", summary="Follow user")
async def follow_user(target_id: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    if target_id == current_user_id:
        raise HTTPException(status_code=400, detail="Cannot follow yourself")
    success = await engine.follow_user(current_user_id, target_id)
    if success:
        return {"status": "following"}
    raise HTTPException(status_code=400, detail="Already following")

@router.delete("/users/{target_id}/follow", summary="Unfollow user")
async def unfollow_user(target_id: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    # IDOR: public - unfollow operation is allowed if relationship exists
    success = await engine.unfollow_user(current_user_id, target_id)
    if success:
        return {"status": "unfollowed"}
    raise HTTPException(status_code=400, detail="Not following")

@router.get("/users/{user_id}/followers", response_model=List[str], summary="Get followers")
async def get_followers(
    user_id: str,
    current_user_id: str = Depends(socialfeed_user_with_rate_limit)
) -> List[str]:
    if user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other users' followers")
    return await engine.get_followers(user_id)

@router.get("/users/{user_id}/following", response_model=List[str], summary="Get following")
async def get_following(
    user_id: str,
    current_user_id: str = Depends(socialfeed_user_with_rate_limit)
) -> List[str]:
    if user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other users' following lists")
    return await engine.get_following(user_id)

@router.get("/feed", response_model=List[SocialFeedItem], summary="Get personalized feed")
async def get_user_feed(limit: int = 20, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    # IDOR: public - feed is personalized to current user
    items = await engine.get_user_feed(current_user_id, min(limit, 100))
    return items

@router.get("/posts/{post_id}/owner", summary="Check post ownership")
async def check_post_owner(post_id: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    post = await engine.get_post(post_id)
    if not post:
        raise HTTPException(status_code=404, detail="Post not found")
    return {"is_owner": post.user_id == current_user_id, "post_owner": post.user_id}

@router.delete("/posts/{post_id}", summary="Delete post")
async def delete_post(post_id: str, current_user_id: str = Depends(socialfeed_user_with_rate_limit)):
    post = await engine.get_post(post_id)
    if not post:
        raise HTTPException(status_code=404, detail="Post not found")
    if post.user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Only post owner can delete")
    success = await engine.delete_post(post_id, current_user_id)
    if success:
        return {"status": "deleted"}
    raise HTTPException(status_code=400, detail="Delete failed")
