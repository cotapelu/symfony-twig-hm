from pydantic import BaseModel
from typing import List, Optional
from datetime import datetime

class SocialPost(BaseModel):
    id: str = ""
    user_id: str
    content: str
    media_urls: List[str] = []
    likes_count: int = 0
    comments_count: int = 0
    shares_count: int = 0
    is_public: bool = True
    created_at: Optional[datetime] = None

class SocialComment(BaseModel):
    id: str = ""
    post_id: str
    user_id: str
    content: str
    likes_count: int = 0
    created_at: Optional[datetime] = None

class UserFollow(BaseModel):
    follower_id: str
    following_id: str
    created_at: Optional[datetime] = None

class SocialFeedItem(BaseModel):
    post_id: str
    user_id: str
    username: str
    user_avatar: str = ""
    content: str
    media_urls: List[str] = []
    likes_count: int = 0
    is_liked: bool = False
    comments_count: int = 0
    created_at: Optional[datetime] = None
