"""Comprehensive unit tests for SocialFeedRepository.

Target: Bring SocialFeed module coverage above 80%.
"""
import pytest
from unittest.mock import AsyncMock, MagicMock
from datetime import datetime, timezone

from modules.SocialFeed.db_models import SocialFeedRepository
from app.shared.models import (
    SocialPost, SocialComment, SocialPostLike,
    SocialCommentLike, SocialFollow
)


def make_mock_result(**kwargs):
    """Factory for mock SQLAlchemy Result."""
    result = MagicMock()
    if 'scalar_one_or_none' in kwargs:
        result.scalar_one_or_none = MagicMock(return_value=kwargs['scalar_one_or_none'])
    if 'scalars_all' in kwargs:
        scalars_mock = MagicMock()
        scalars_mock.all = MagicMock(return_value=kwargs['scalars_all'])
        result.scalars = MagicMock(return_value=scalars_mock)
    if 'all' in kwargs:
        result.all = MagicMock(return_value=kwargs['all'])
    if 'rowcount' in kwargs:
        result.rowcount = kwargs['rowcount']
    return result


@pytest.fixture
def repo():
    """Provide a SocialFeedRepository with a mocked session."""
    r = SocialFeedRepository()
    session = MagicMock()
    session.add = MagicMock()
    session.delete = AsyncMock()
    session.commit = AsyncMock()
    session.refresh = AsyncMock()
    session.execute = AsyncMock()
    # Context manager yielding the session
    cm = MagicMock()
    cm.__aenter__ = AsyncMock(return_value=session)
    cm.__aexit__ = AsyncMock(return_value=None)
    r.get_session = MagicMock(return_value=cm)
    return r, session


# ==================== Post methods ====================

async def test_create_post(repo):
    r, session = repo
    user_id = "user1"
    content = "Hello world"
    media_urls = "http://example.com/image.jpg"
    result = await r.create_post(user_id, content, media_urls)
    # Should add a SocialPost, commit, refresh, and return it
    session.add.assert_called_once()
    session.commit.assert_awaited_once()
    session.refresh.assert_awaited_once()
    assert isinstance(result, SocialPost)
    assert result.user_id == user_id
    assert result.content == content
    assert result.media_urls == media_urls

async def test_get_post_found(repo):
    r, session = repo
    now = datetime.now(timezone.utc)
    post = SocialPost(id="p1", user_id="user1", content="Test", created_at=now, updated_at=now)
    session.execute.return_value = make_mock_result(scalar_one_or_none=post)
    result = await r.get_post("p1")
    assert result == post

async def test_get_post_not_found(repo):
    r, session = repo
    session.execute.return_value = make_mock_result(scalar_one_or_none=None)
    result = await r.get_post("nonexistent")
    assert result is None

async def test_get_user_posts(repo):
    r, session = repo
    now = datetime.now(timezone.utc)
    posts = [
        SocialPost(id="p1", user_id="user1", content="First", created_at=now, updated_at=now),
        SocialPost(id="p2", user_id="user1", content="Second", created_at=now, updated_at=now)
    ]
    session.execute.return_value = make_mock_result(scalars_all=posts)
    result = await r.get_user_posts("user1")
    assert result == posts

# ==================== Like methods ====================

async def test_toggle_post_like_like(repo):
    r, session = repo
    # No existing like
    session.execute.side_effect = [
        make_mock_result(scalar_one_or_none=None),  # select like
        make_mock_result(rowcount=1)                # update likes_count
    ]
    result = await r.toggle_post_like("post1", "user1")
    assert result is True
    session.add.assert_called_once()  # new like added
    session.delete.assert_not_called()

async def test_toggle_post_like_unlike(repo):
    r, session = repo
    like_obj = SocialPostLike(post_id="post1", user_id="user1")
    session.execute.side_effect = [
        make_mock_result(scalar_one_or_none=like_obj),  # select like exists
        make_mock_result(rowcount=1)                     # update likes_count decrement
    ]
    result = await r.toggle_post_like("post1", "user1")
    assert result is False
    session.delete.assert_awaited_once()  # existing like deleted

async def test_get_post_likes(repo):
    r, session = repo
    rows = [("user1",), ("user2",)]
    session.execute.return_value = make_mock_result(all=rows)
    result = await r.get_post_likes("post1")
    assert result == ["user1", "user2"]

# ==================== Comment methods ====================

async def test_add_comment(repo):
    r, session = repo
    # No existing comment to like
    session.execute.return_value = make_mock_result(rowcount=1)  # update post comments_count
    result = await r.add_comment("post1", "user1", "Nice!")
    session.add.assert_called_once()
    session.commit.assert_awaited()
    session.refresh.assert_awaited()
    assert isinstance(result, SocialComment)
    assert result.post_id == "post1"
    assert result.content == "Nice!"

async def test_get_post_comments(repo):
    r, session = repo
    now = datetime.now(timezone.utc)
    comments = [
        SocialComment(id="c1", post_id="post1", user_id="user1", content="First", created_at=now),
        SocialComment(id="c2", post_id="post1", user_id="user2", content="Second", created_at=now)
    ]
    session.execute.return_value = make_mock_result(scalars_all=comments)
    result = await r.get_post_comments("post1")
    assert result == comments

async def test_toggle_comment_like_toggle_on(repo):
    r, session = repo
    # No existing like
    session.execute.return_value = make_mock_result(scalar_one_or_none=None)
    result = await r.toggle_comment_like("comment1", "user1")
    assert result is True
    session.add.assert_called_once()
    session.delete.assert_not_called()

async def test_toggle_comment_like_toggle_off(repo):
    r, session = repo
    like_obj = SocialCommentLike(comment_id="comment1", user_id="user1")
    session.execute.return_value = make_mock_result(scalar_one_or_none=like_obj)
    result = await r.toggle_comment_like("comment1", "user1")
    assert result is False
    session.delete.assert_awaited_once()

# ==================== Follow methods ====================

async def test_follow_user_self_returns_false(repo):
    r, session = repo
    result = await r.follow_user("user1", "user1")
    assert result is False
    session.execute.assert_not_called()

async def test_follow_user_already_following(repo):
    r, session = repo
    follow = SocialFollow(follower_id="user1", following_id="user2")
    session.execute.return_value = make_mock_result(scalar_one_or_none=follow)
    result = await r.follow_user("user1", "user2")
    assert result is False
    session.add.assert_not_called()

async def test_follow_user_success(repo):
    r, session = repo
    session.execute.return_value = make_mock_result(scalar_one_or_none=None)
    result = await r.follow_user("user1", "user2")
    assert result is True
    session.add.assert_called_once()
    session.commit.assert_awaited()

async def test_unfollow_user_success(repo):
    r, session = repo
    follow = SocialFollow(follower_id="user1", following_id="user2")
    session.execute.return_value = make_mock_result(scalar_one_or_none=follow)
    result = await r.unfollow_user("user1", "user2")
    assert result is True
    session.delete.assert_awaited_once()

async def test_unfollow_user_not_following(repo):
    r, session = repo
    session.execute.return_value = make_mock_result(scalar_one_or_none=None)
    result = await r.unfollow_user("user1", "user2")
    assert result is False
    session.delete.assert_not_called()

async def test_get_followers(repo):
    r, session = repo
    rows = [("user1",), ("user2",)]
    session.execute.return_value = make_mock_result(all=rows)
    result = await r.get_followers("target")
    assert result == ["user1", "user2"]

async def test_get_following(repo):
    r, session = repo
    rows = [("user2",), ("user3",)]
    session.execute.return_value = make_mock_result(all=rows)
    result = await r.get_following("user1")
    assert result == ["user2", "user3"]

# ==================== Feed generation ====================

async def test_get_feed_no_following(repo):
    r, session = repo
    # Following query returns empty; after appending user_id, posts query returns empty
    session.execute.side_effect = [
        make_mock_result(all=[]),                # following query
        make_mock_result(scalars_all=[])         # posts query
    ]
    result = await r.get_feed("user1")
    assert result == []

async def test_get_feed_with_following(repo):
    r, session = repo
    # First call: following_ids including own user_id
    session.execute.side_effect = [
        make_mock_result(all=[("user2",), ("user3",)]),  # following query result
        make_mock_result(scalars_all=[])                 # posts query (empty list)
    ]
    result = await r.get_feed("user1")
    assert result == []

async def test_get_feed_with_posts(repo):
    r, session = repo
    now = datetime.now(timezone.utc)
    posts = [
        SocialPost(id="p1", user_id="user2", content="Post1", created_at=now, updated_at=now),
        SocialPost(id="p2", user_id="user3", content="Post2", created_at=now, updated_at=now)
    ]
    session.execute.side_effect = [
        make_mock_result(all=[("user2",), ("user3",)]),  # following query
        make_mock_result(scalars_all=posts)              # posts query
    ]
    result = await r.get_feed("user1")
    assert result == posts

# ==================== Delete post ====================

async def test_delete_post_success(repo):
    r, session = repo
    # Sequence: delete post likes, select comment ids, delete comment likes, delete comments, delete post
    session.execute.side_effect = [
        make_mock_result(rowcount=1),   # delete SocialPostLike
        make_mock_result(all=[("c1",), ("c2",)]),  # comment IDs
        make_mock_result(rowcount=2),   # delete SocialCommentLike
        make_mock_result(rowcount=2),   # delete SocialComment
        make_mock_result(rowcount=1)    # delete SocialPost
    ]
    result = await r.delete_post("post1")
    assert result is True

async def test_delete_post_not_found(repo):
    r, session = repo
    # No post deleted (rowcount 0)
    session.execute.side_effect = [
        make_mock_result(rowcount=0),   # delete SocialPostLike (0)
        make_mock_result(all=[]),       # comment ids empty
        make_mock_result(rowcount=0),   # delete SocialComment
        make_mock_result(rowcount=0)    # delete SocialPost
    ]
    result = await r.delete_post("missing")
    assert result is False

async def test_init_db(repo):
    # init_db is a no‑op; ensure it doesn't raise
    r, _ = repo
    await r.init_db()  # should simply pass
