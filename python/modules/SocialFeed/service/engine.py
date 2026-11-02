"""SocialFeedEngine - Database-backed implementation using SQLAlchemy"""
from typing import List, Optional
from datetime import datetime
import json
from ..db_models import SocialFeedRepository, SocialPost, SocialComment
from ..models.schemas import SocialFeedItem
import uuid

class SocialFeedEngine:
    def __init__(self):
        self.repo = SocialFeedRepository()
        # Initialize database tables; ideally called from lifespan
        # We'll call init_db synchronously? It's async, so we need event loop.
        # We'll provide an async init method.

    async def init(self):
        await self.repo.init_db()

    # Post operations
    def create_post(self, user_id: str, content: str, media_urls: Optional[str] = None) -> SocialPost:
        return self.repo.create_post(user_id, content, media_urls)  # sync wrapper for tests

    def get_post(self, post_id: str) -> Optional[SocialPost]:
        return self.repo.get_post(post_id)  # sync wrapper

    def get_user_posts(self, user_id: str, limit: int = 20, offset: int = 0) -> List[SocialPost]:
        return self.repo.get_user_posts(user_id, limit, offset)  # sync wrapper

    # Like operations
    async def toggle_like(self, post_id: str, user_id: str) -> bool:
        return await self.repo.toggle_post_like(post_id, user_id)

    async def get_post_likes_count(self, post_id: str) -> int:
        likes = await self.repo.get_post_likes(post_id)
        return len(likes)

    async def get_post_likes(self, post_id: str) -> List[str]:
        return await self.repo.get_post_likes(post_id)

    # Comment operations
    async def add_comment(self, comment: SocialComment) -> SocialComment:
        # comment is expected to have post_id, user_id, content
        return await self.repo.add_comment(comment.post_id, comment.user_id, comment.content)

    async def get_post_comments(self, post_id: str) -> List[SocialComment]:
        return await self.repo.get_post_comments(post_id)

    async def toggle_comment_like(self, comment_id: str, user_id: str) -> bool:
        return await self.repo.toggle_comment_like(comment_id, user_id)

    # Follow operations
    async def follow_user(self, user_id: str, target_id: str) -> bool:
        return await self.repo.follow_user(user_id, target_id)

    async def unfollow_user(self, user_id: str, target_id: str) -> bool:
        return await self.repo.unfollow_user(user_id, target_id)

    async def get_followers(self, user_id: str) -> List[str]:
        return await self.repo.get_followers(user_id)

    async def get_following(self, user_id: str) -> List[str]:
        return await self.repo.get_following(user_id)

    # Feed generation
    async def get_user_feed(self, user_id: str, limit: int = 20) -> List[SocialFeedItem]:
        posts = await self.repo.get_feed(user_id, limit)
        # Convert to SocialFeedItem (schema)
        items = []
        for post in posts:
            likes = await self.repo.get_post_likes(post.id)
            is_liked = user_id in likes
            media_urls = []
            if post.media_urls:
                try:
                    media_urls = json.loads(post.media_urls) if isinstance(post.media_urls, str) else post.media_urls
                except Exception:
                    media_urls = []
            items.append(SocialFeedItem(
                post_id=post.id,
                user_id=post.user_id,
                username=post.user_id,  # placeholder; could join with user table
                content=post.content,
                media_urls=media_urls,
                likes_count=post.likes_count,
                is_liked=is_liked,
                comments_count=post.comments_count,
                created_at=post.created_at
            ))
        return items

    # Convenience methods for backward compatibility
    async def create_post_sync_wrapper(self, user_id: int, content: str) -> SocialPost:
        return await self.create_post(str(user_id), content)

    async def get_feed_sync_wrapper(self, user_id: int) -> List[SocialFeedItem]:
        return await self.get_user_feed(str(user_id))

    async def like_post(self, user_id: int, post_id: int) -> bool:
        return await self.toggle_like(str(post_id), str(user_id))

    async def comment_on_post(self, user_id: int, post_id: int, content: str) -> SocialComment:
        return await self.add_comment(SocialComment(post_id=str(post_id), user_id=str(user_id), content=content))

    async def share_post(self, user_id: int, post_id: int) -> dict:
        original = await self.get_post(str(post_id))
        if original:
            new_post = await self.create_post(str(user_id), f"Shared: {original.content}")
            return {'post_id': new_post.id, 'shared_from': original.id}
        return {'error': 'Post not found'}

    async def delete_post(self, user_id: int, post_id: int) -> bool:
        return await self.repo.delete_post(str(post_id))

    async def get_trending_posts(self, limit: int = 10) -> List[SocialPost]:
        # Not implemented in repo; could be done with order by likes_count
        # For now, return empty list
        return []

# Alias for compatibility
SocialFeedManager = SocialFeedEngine
