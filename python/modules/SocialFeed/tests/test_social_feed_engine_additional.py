import pytest
from unittest.mock import AsyncMock, MagicMock
from datetime import datetime
from modules.SocialFeed.service.engine import SocialFeedEngine
from modules.SocialFeed.db_models import SocialComment

@pytest.mark.asyncio
async def test_init_success(engine):
    # engine fixture has e.repo = AsyncMock()
    await engine.init()
    engine.repo.init_db.assert_awaited_once()

@pytest.mark.asyncio
async def test_create_post_sync_wrapper(engine):
    post = MagicMock()
    post.id = "p1"
    engine.repo.create_post = AsyncMock(return_value=post)
    result = await engine.create_post_sync_wrapper(123, "content")
    assert result == post
    engine.repo.create_post.assert_awaited_once_with("123", "content", None)

@pytest.mark.asyncio
async def test_get_feed_sync_wrapper(engine):
    engine.get_user_feed = AsyncMock(return_value=[{"dummy": "data"}])
    result = await engine.get_feed_sync_wrapper(123)
    assert result == [{"dummy": "data"}]
    engine.get_user_feed.assert_awaited_once_with("123")

@pytest.mark.asyncio
async def test_like_post_wrapper(engine):
    engine.toggle_like = AsyncMock(return_value=True)
    result = await engine.like_post(123, 456)
    assert result is True
    engine.toggle_like.assert_awaited_once_with("456", "123")

@pytest.mark.asyncio
async def test_comment_on_post_wrapper(engine):
    comment = MagicMock()
    comment.id = "c1"
    engine.add_comment = AsyncMock(return_value=comment)
    result = await engine.comment_on_post(123, 456, "comment")
    engine.add_comment.assert_awaited_once()
    arg = engine.add_comment.call_args[0][0]
    assert isinstance(arg, SocialComment)
    assert arg.post_id == "456"
    assert arg.user_id == "123"
    assert arg.content == "comment"
    assert result == comment

@pytest.mark.asyncio
async def test_get_user_feed_malformed_media(engine):
    post = MagicMock()
    post.id = "p1"
    post.user_id = "u1"
    post.content = "test"
    post.media_urls = "{ invalid json"
    post.likes_count = 0
    post.comments_count = 0
    post.created_at = datetime.now()
    engine.repo.get_feed = AsyncMock(return_value=[post])
    engine.repo.get_post_likes = AsyncMock(return_value=[])
    result = await engine.get_user_feed("u1")
    assert len(result) == 1
    # malformed JSON should be caught and media_urls become empty list
    assert result[0].media_urls == []

@pytest.mark.asyncio
async def test_get_user_feed_media_as_list(engine):
    post = MagicMock()
    post.id = "p1"
    post.user_id = "u1"
    post.content = "test"
    post.media_urls = ["url1", "url2"]  # already a list, not a string
    post.likes_count = 0
    post.comments_count = 0
    post.created_at = datetime.now()
    engine.repo.get_feed = AsyncMock(return_value=[post])
    engine.repo.get_post_likes = AsyncMock(return_value=[])
    result = await engine.get_user_feed("u1")
    assert len(result) == 1
    assert result[0].media_urls == ["url1", "url2"]