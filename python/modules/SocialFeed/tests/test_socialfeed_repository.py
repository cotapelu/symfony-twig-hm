import pytest
from unittest.mock import AsyncMock, MagicMock
from modules.SocialFeed.db_models import SocialFeedRepository
from app.shared.models import SocialPost, SocialComment, SocialPostLike, SocialCommentLike, SocialFollow

def make_post(**kwargs):
    defaults = dict(id="p1", user_id="u1", content="Hello", media_urls=None,
                    likes_count=0, comments_count=0)
    defaults.update(kwargs)
    return SocialPost(**defaults)

def make_comment(**kwargs):
    defaults = dict(id="c1", post_id="p1", user_id="u1", content="Nice")
    defaults.update(kwargs)
    return SocialComment(**defaults)

def make_post_like(**kwargs):
    return SocialPostLike(**kwargs)

def make_comment_like(**kwargs):
    return SocialCommentLike(**kwargs)

def make_follow(**kwargs):
    return SocialFollow(**kwargs)

def result_mock(scalar=None, all_rows=None, scalars_all=None, rowcount=0):
    m = MagicMock()
    m.scalar_one_or_none.return_value = scalar
    if all_rows is not None:
        m.all.return_value = all_rows
    else:
        m.all.return_value = []
    if scalars_all is not None:
        m.scalars.return_value.all.return_value = scalars_all
    m.rowcount = rowcount
    return m

def make_repo(mock_session):
    """Return a SocialFeedRepository with its db replaced by a mock configured to use mock_session."""
    mock_db = MagicMock()
    mock_db.get_session.return_value.__aenter__ = AsyncMock(return_value=mock_session)
    mock_db.get_session.return_value.__aexit__ = AsyncMock(return_value=None)
    repo = SocialFeedRepository()
    repo.db = mock_db
    return repo

# create_post
@pytest.mark.asyncio
async def test_create_post_success():
    mock_session = AsyncMock()
    mock_session.add = MagicMock()
    mock_session.commit = AsyncMock()
    mock_session.refresh = AsyncMock()
    repo = make_repo(mock_session)
    result = await repo.create_post("u1", "Test content", None)
    assert result.user_id == "u1"
    assert result.content == "Test content"
    mock_session.add.assert_called_once()
    mock_session.commit.assert_called_once()
    mock_session.refresh.assert_called_once()

# get_post
@pytest.mark.asyncio
async def test_get_post_found():
    mock_session = AsyncMock()
    post = make_post(id="p1")
    mock_session.execute.return_value = result_mock(scalar=post)
    repo = make_repo(mock_session)
    result = await repo.get_post("p1")
    assert result is post

@pytest.mark.asyncio
async def test_get_post_not_found():
    mock_session = AsyncMock()
    mock_session.execute.return_value = result_mock(scalar=None)
    repo = make_repo(mock_session)
    result = await repo.get_post("p1")
    assert result is None

# get_user_posts
@pytest.mark.asyncio
async def test_get_user_posts():
    mock_session = AsyncMock()
    posts = [make_post(id=f"p{i}") for i in range(3)]
    mock_session.execute.return_value = result_mock(scalars_all=posts)
    repo = make_repo(mock_session)
    result = await repo.get_user_posts("u1")
    assert len(result) == 3

# toggle_post_like
@pytest.mark.asyncio
async def test_toggle_post_like_add():
    mock_session = AsyncMock()
    mock_session.execute.side_effect = [
        result_mock(scalar=None),  # select existing like
        None,                     # update likes count
    ]
    repo = make_repo(mock_session)
    result = await repo.toggle_post_like("p1", "u1")
    assert result is True
    mock_session.add.assert_called_once()

@pytest.mark.asyncio
async def test_toggle_post_like_remove():
    mock_session = AsyncMock()
    existing_like = make_post_like(post_id="p1", user_id="u1")
    mock_session.execute.side_effect = [
        result_mock(scalar=[existing_like]),  # select -> found
        None,                                 # delete like
        None,                                 # update count
    ]
    mock_session.delete = AsyncMock()
    repo = make_repo(mock_session)
    result = await repo.toggle_post_like("p1", "u1")
    assert result is False
    mock_session.delete.assert_called_once()

# get_post_likes
@pytest.mark.asyncio
async def test_get_post_likes():
    mock_session = AsyncMock()
    mock_result = result_mock(all_rows=[("u1",), ("u2",)])
    mock_session.execute.return_value = mock_result
    repo = make_repo(mock_session)
    result = await repo.get_post_likes("p1")
    assert result == ["u1", "u2"]

# add_comment
@pytest.mark.asyncio
async def test_add_comment():
    mock_session = AsyncMock()
    mock_session.add = MagicMock()
    comment = make_comment(id="c1", post_id="p1", user_id="u1")
    mock_session.execute.return_value = result_mock(scalar=comment)  # after refresh? Actually they refresh comment.
    # We'll just check that add called and result returned
    repo = make_repo(mock_session)
    result = await repo.add_comment("p1", "u1", "Great!")
    assert result.content == "Great!"
    mock_session.add.assert_called_once()

# get_post_comments
@pytest.mark.asyncio
async def test_get_post_comments():
    mock_session = AsyncMock()
    comments = [make_comment(id=f"c{i}") for i in range(2)]
    mock_session.execute.return_value = result_mock(scalars_all=comments)
    repo = make_repo(mock_session)
    result = await repo.get_post_comments("p1")
    assert len(result) == 2

# toggle_comment_like
@pytest.mark.asyncio
async def test_toggle_comment_like_add():
    mock_session = AsyncMock()
    mock_session.execute.return_value = result_mock(scalar=None)
    repo = make_repo(mock_session)
    result = await repo.toggle_comment_like("c1", "u1")
    assert result is True
    mock_session.add.assert_called_once()

@pytest.mark.asyncio
async def test_toggle_comment_like_remove():
    mock_session = AsyncMock()
    existing_like = make_comment_like(comment_id="c1", user_id="u1")
    mock_session.execute.side_effect = [
        result_mock(scalar=[existing_like]),
        None,
    ]
    mock_session.delete = AsyncMock()
    repo = make_repo(mock_session)
    result = await repo.toggle_comment_like("c1", "u1")
    assert result is False
    mock_session.delete.assert_called_once()

# follow_user
@pytest.mark.asyncio
async def test_follow_user_success():
    mock_session = AsyncMock()
    mock_session.execute.return_value = result_mock(scalar=None)
    mock_session.commit = AsyncMock()
    repo = make_repo(mock_session)
    result = await repo.follow_user("u1", "u2")
    assert result is True
    mock_session.add.assert_called_once()
    mock_session.commit.assert_called_once()

@pytest.mark.asyncio
async def test_follow_user_self():
    mock_session = AsyncMock()
    repo = make_repo(mock_session)
    result = await repo.follow_user("u1", "u1")
    assert result is False
    mock_session.add.assert_not_called()

@pytest.mark.asyncio
async def test_follow_user_already_following():
    mock_session = AsyncMock()
    existing = make_follow(follower_id="u1", following_id="u2")
    mock_session.execute.return_value = result_mock(scalar=[existing])
    repo = make_repo(mock_session)
    result = await repo.follow_user("u1", "u2")
    assert result is False
    mock_session.add.assert_not_called()

# unfollow_user
@pytest.mark.asyncio
async def test_unfollow_user_success():
    mock_session = AsyncMock()
    existing = make_follow(follower_id="u1", following_id="u2")
    mock_session.execute.side_effect = [
        result_mock(scalar=[existing]),
        None,
    ]
    mock_session.delete = AsyncMock()
    repo = make_repo(mock_session)
    result = await repo.unfollow_user("u1", "u2")
    assert result is True
    mock_session.delete.assert_called_once()

@pytest.mark.asyncio
async def test_unfollow_user_not_following():
    mock_session = AsyncMock()
    mock_session.execute.return_value = result_mock(scalar=None)
    repo = make_repo(mock_session)
    result = await repo.unfollow_user("u1", "u2")
    assert result is False
    mock_session.delete.assert_not_called()

# get_followers
@pytest.mark.asyncio
async def test_get_followers():
    mock_session = AsyncMock()
    mock_result = result_mock(all_rows=[("u2",), ("u3",)])
    mock_session.execute.return_value = mock_result
    repo = make_repo(mock_session)
    result = await repo.get_followers("u1")
    assert result == ["u2", "u3"]

# get_following
@pytest.mark.asyncio
async def test_get_following():
    mock_session = AsyncMock()
    mock_result = result_mock(all_rows=[("u2",), ("u3",)])
    mock_session.execute.return_value = mock_result
    repo = make_repo(mock_session)
    result = await repo.get_following("u1")
    assert result == ["u2", "u3"]

# get_feed
@pytest.mark.asyncio
async def test_get_feed():
    mock_session = AsyncMock()
    following_result = result_mock(all_rows=[("u2",), ("u3",)])
    posts = [make_post(id="p1", user_id="u2"), make_post(id="p2", user_id="u3")]
    posts_result = result_mock(scalars_all=posts)
    mock_session.execute.side_effect = [following_result, posts_result]
    repo = make_repo(mock_session)
    result = await repo.get_feed("u1", limit=10)
    assert len(result) == 2

@pytest.mark.asyncio
async def test_get_feed_no_following():
    mock_session = AsyncMock()
    following_result = result_mock(all_rows=[])
    posts_result = result_mock(scalars_all=[])
    mock_session.execute.side_effect = [following_result, posts_result]
    repo = make_repo(mock_session)
    result = await repo.get_feed("u1")
    assert result == []

# delete_post
@pytest.mark.asyncio
async def test_delete_post_success():
    mock_session = AsyncMock()
    execute_result = MagicMock()
    execute_result.rowcount = 1
    mock_session.execute.return_value = execute_result
    mock_session.commit = AsyncMock()
    repo = make_repo(mock_session)
    result = await repo.delete_post("p1")
    assert result is True
    mock_session.commit.assert_called_once()

@pytest.mark.asyncio
async def test_delete_post_not_found():
    mock_session = AsyncMock()
    execute_result = MagicMock()
    execute_result.rowcount = 0
    mock_session.execute.return_value = execute_result
    mock_session.commit = AsyncMock()
    repo = make_repo(mock_session)
    result = await repo.delete_post("p1")
    assert result is False
