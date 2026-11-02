"""SocialFeed repository using unified database models."""
from sqlalchemy import select, update, delete
from sqlalchemy.ext.asyncio import AsyncSession
from typing import List, Optional

from app.shared.database import db
from app.shared.models import (
    SocialPost, SocialComment, SocialPostLike,
    SocialCommentLike, SocialFollow
)


class SocialFeedRepository:
    """Repository for social feed operations using unified models."""

    def __init__(self):
        self.db = db

    async def init_db(self):
        """No need; tables created centrally"""
        pass

    async def get_session(self) -> AsyncSession:
        async with self.db.get_session() as session:
            yield session

    # Post methods
    async def create_post(self, user_id: str, content: str, media_urls: Optional[str] = None) -> SocialPost:
        async with self.get_session() as session:
            post = SocialPost(user_id=user_id, content=content, media_urls=media_urls)
            session.add(post)
            await session.commit()
            await session.refresh(post)
            return post

    async def get_post(self, post_id: str) -> Optional[SocialPost]:
        async with self.get_session() as session:
            result = await session.execute(select(SocialPost).where(SocialPost.id == post_id))
            return result.scalar_one_or_none()

    async def get_user_posts(self, user_id: str, limit: int = 20, offset: int = 0) -> List[SocialPost]:
        async with self.get_session() as session:
            result = await session.execute(
                select(SocialPost)
                .where(SocialPost.user_id == user_id)
                .order_by(SocialPost.created_at.desc())
                .limit(limit)
                .offset(offset)
            )
            return result.scalars().all()

    # Like methods
    async def toggle_post_like(self, post_id: str, user_id: str) -> bool:
        async with self.get_session() as session:
            result = await session.execute(
                select(SocialPostLike).where(
                    (SocialPostLike.post_id == post_id) & (SocialPostLike.user_id == user_id)
                )
            )
            existing = result.scalar_one_or_none()
            if existing:
                await session.delete(existing)
                await session.execute(
                    update(SocialPost).where(SocialPost.id == post_id).values(likes_count=SocialPost.likes_count - 1)
                )
                await session.commit()
                return False
            else:
                like = SocialPostLike(post_id=post_id, user_id=user_id)
                session.add(like)
                await session.execute(
                    update(SocialPost).where(SocialPost.id == post_id).values(likes_count=SocialPost.likes_count + 1)
                )
                await session.commit()
                return True

    async def get_post_likes(self, post_id: str) -> List[str]:
        async with self.get_session() as session:
            result = await session.execute(
                select(SocialPostLike.user_id).where(SocialPostLike.post_id == post_id)
            )
            return [row[0] for row in result.all()]

    # Comment methods
    async def add_comment(self, post_id: str, user_id: str, content: str) -> SocialComment:
        async with self.get_session() as session:
            comment = SocialComment(post_id=post_id, user_id=user_id, content=content)
            session.add(comment)
            await session.execute(
                update(SocialPost).where(SocialPost.id == post_id).values(comments_count=SocialPost.comments_count + 1)
            )
            await session.commit()
            await session.refresh(comment)
            return comment

    async def get_post_comments(self, post_id: str) -> List[SocialComment]:
        async with self.get_session() as session:
            result = await session.execute(
                select(SocialComment)
                .where(SocialComment.post_id == post_id)
                .order_by(SocialComment.created_at)
            )
            return result.scalars().all()

    async def toggle_comment_like(self, comment_id: str, user_id: str) -> bool:
        async with self.get_session() as session:
            result = await session.execute(
                select(SocialCommentLike).where(
                    (SocialCommentLike.comment_id == comment_id) & (SocialCommentLike.user_id == user_id)
                )
            )
            existing = result.scalar_one_or_none()
            if existing:
                await session.delete(existing)
                await session.commit()
                return False
            else:
                like = SocialCommentLike(comment_id=comment_id, user_id=user_id)
                session.add(like)
                await session.commit()
                return True

    # Follow methods
    async def follow_user(self, user_id: str, target_id: str) -> bool:
        if user_id == target_id:
            return False
        async with self.get_session() as session:
            result = await session.execute(
                select(SocialFollow).where(
                    (SocialFollow.follower_id == user_id) & (SocialFollow.following_id == target_id)
                )
            )
            existing = result.scalar_one_or_none()
            if existing:
                return False
            follow = SocialFollow(follower_id=user_id, following_id=target_id)
            session.add(follow)
            await session.commit()
            return True

    async def unfollow_user(self, user_id: str, target_id: str) -> bool:
        async with self.get_session() as session:
            result = await session.execute(
                select(SocialFollow).where(
                    (SocialFollow.follower_id == user_id) & (SocialFollow.following_id == target_id)
                )
            )
            existing = result.scalar_one_or_none()
            if existing:
                await session.delete(existing)
                await session.commit()
                return True
            return False

    async def get_followers(self, user_id: str) -> List[str]:
        async with self.get_session() as session:
            result = await session.execute(
                select(SocialFollow.follower_id).where(SocialFollow.following_id == user_id)
            )
            return [row[0] for row in result.all()]

    async def get_following(self, user_id: str) -> List[str]:
        async with self.get_session() as session:
            result = await session.execute(
                select(SocialFollow.following_id).where(SocialFollow.follower_id == user_id)
            )
            return [row[0] for row in result.all()]

    # Feed generation
    async def get_feed(self, user_id: str, limit: int = 20) -> List[SocialPost]:
        """Get posts from users that the given user follows, plus own posts."""
        async with self.get_session() as session:
            following_result = await session.execute(
                select(SocialFollow.following_id).where(SocialFollow.follower_id == user_id)
            )
            following_ids = [row[0] for row in following_result.all()]
            following_ids.append(user_id)
            if not following_ids:
                return []
            result = await session.execute(
                select(SocialPost)
                .where(SocialPost.user_id.in_(following_ids))
                .order_by(SocialPost.created_at.desc())
                .limit(limit)
            )
            return result.scalars().all()

    async def delete_post(self, post_id: str) -> bool:
        """Delete a post and all associated likes/comments"""
        async with self.get_session() as session:
            await session.execute(delete(SocialPostLike).where(SocialPostLike.post_id == post_id))
            comment_result = await session.execute(select(SocialComment.id).where(SocialComment.post_id == post_id))
            comment_ids = [row[0] for row in comment_result.all()]
            if comment_ids:
                await session.execute(delete(SocialCommentLike).where(SocialCommentLike.comment_id.in_(comment_ids)))
            await session.execute(delete(SocialComment).where(SocialComment.post_id == post_id))
            result = await session.execute(delete(SocialPost).where(SocialPost.id == post_id))
            await session.commit()
            return result.rowcount > 0
