import pytest
from unittest.mock import AsyncMock, MagicMock
from datetime import datetime
from modules.SocialFeed.service.engine import SocialFeedEngine

@pytest.mark.asyncio
async def test_create_post(engine):
    post = MagicMock()
    post.id = "post123"
    post.user_id = "user123"
    post.content = "Test content"
    post.media_urls = None
    post.likes_count = 0
    post.comments_count = 0
    post.created_at = datetime.now()
    
    engine.repo.create_post = AsyncMock(return_value=post)
    
    result = await engine.create_post("user123", "Test content")
    
    assert result is not None
    engine.repo.create_post.assert_called_once_with("user123", "Test content", None)

@pytest.mark.asyncio
async def test_get_post(engine):
    post = MagicMock()
    post.id = "post123"
    
    engine.repo.get_post = AsyncMock(return_value=post)
    
    result = await engine.get_post("post123")
    
    assert result is not None
    engine.repo.get_post.assert_called_once_with("post123")

@pytest.mark.asyncio
async def test_get_post_not_found(engine):
    engine.repo.get_post = AsyncMock(return_value=None)
    
    result = await engine.get_post("nonexistent")
    
    assert result is None

@pytest.mark.asyncio
async def test_get_user_posts(engine):
    post = MagicMock()
    post.id = "post123"
    
    engine.repo.get_user_posts = AsyncMock(return_value=[post])
    
    result = await engine.get_user_posts("user123")
    
    assert len(result) == 1

@pytest.mark.asyncio
async def test_toggle_like(engine):
    engine.repo.toggle_post_like = AsyncMock(return_value=True)
    
    result = await engine.toggle_like("post123", "user123")
    
    assert result is True
    engine.repo.toggle_post_like.assert_called_once_with("post123", "user123")

@pytest.mark.asyncio
async def test_get_post_likes_count(engine):
    engine.repo.get_post_likes = AsyncMock(return_value=["user1", "user2"])
    
    result = await engine.get_post_likes_count("post123")
    
    assert result == 2

@pytest.mark.asyncio
async def test_add_comment(engine):
    comment = MagicMock()
    comment.id = "comment123"
    comment.post_id = "post123"
    comment.user_id = "user123"
    comment.content = "Test comment"
    
    engine.repo.add_comment = AsyncMock(return_value=comment)
    
    result = await engine.add_comment(comment)
    
    assert result is not None
    engine.repo.add_comment.assert_called_once_with("post123", "user123", "Test comment")

@pytest.mark.asyncio
async def test_get_post_comments(engine):
    comment = MagicMock()
    comment.id = "comment123"
    
    engine.repo.get_post_comments = AsyncMock(return_value=[comment])
    
    result = await engine.get_post_comments("post123")
    
    assert len(result) == 1

@pytest.mark.asyncio
async def test_follow_user(engine):
    engine.repo.follow_user = AsyncMock(return_value=True)
    
    result = await engine.follow_user("user123", "target456")
    
    assert result is True
    engine.repo.follow_user.assert_called_once_with("user123", "target456")

@pytest.mark.asyncio
async def test_unfollow_user(engine):
    engine.repo.unfollow_user = AsyncMock(return_value=True)
    
    result = await engine.unfollow_user("user123", "target456")
    
    assert result is True

@pytest.mark.asyncio
async def test_get_followers(engine):
    engine.repo.get_followers = AsyncMock(return_value=["user1", "user2"])
    
    result = await engine.get_followers("user123")
    
    assert len(result) == 2

@pytest.mark.asyncio
async def test_get_following(engine):
    engine.repo.get_following = AsyncMock(return_value=["user3", "user4"])
    
    result = await engine.get_following("user123")
    
    assert len(result) == 2

@pytest.mark.asyncio
async def test_get_user_feed(engine):
    post = MagicMock()
    post.id = "post123"
    post.user_id = "user123"
    post.content = "Test"
    post.media_urls = None
    post.likes_count = 5
    post.comments_count = 2
    post.created_at = datetime.now()
    
    engine.repo.get_feed = AsyncMock(return_value=[post])
    engine.repo.get_post_likes = AsyncMock(return_value=["user123"])
    
    result = await engine.get_user_feed("user123")
    
    assert len(result) == 1
    assert result[0].is_liked is True

@pytest.mark.asyncio
async def test_share_post(engine):
    original = MagicMock()
    original.id = "post123"
    original.content = "Original content"
    
    new_post = MagicMock()
    new_post.id = "post456"
    
    engine.repo.get_post = AsyncMock(return_value=original)
    engine.repo.create_post = AsyncMock(return_value=new_post)
    
    result = await engine.share_post(123, 123)
    
    assert result["post_id"] == "post456"
    assert result["shared_from"] == "post123"

@pytest.mark.asyncio
async def test_share_post_not_found(engine):
    engine.repo.get_post = AsyncMock(return_value=None)
    
    result = await engine.share_post(123, 123)
    
    assert "error" in result

@pytest.mark.asyncio
async def test_delete_post(engine):
    engine.repo.delete_post = AsyncMock(return_value=True)
    
    result = await engine.delete_post(123, 123)
    
    assert result is True
    engine.repo.delete_post.assert_called_once_with("123")

@pytest.mark.asyncio
async def test_get_trending_posts(engine):
    result = await engine.get_trending_posts()
    assert result == []