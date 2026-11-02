"""Unit tests for app.middleware.rate_limit (DoS prevention).

Tests sliding window rate limiter (60 requests per minute per user).
"""
import pytest
import time
import asyncio
from unittest.mock import patch, AsyncMock
from fastapi import HTTPException

from app.middleware.rate_limit import ai_user_with_rate_limit, clear_rate_limit


@pytest.fixture(autouse=True)
def reset_rate_limit():
    """Clear rate limit data between tests."""
    clear_rate_limit()
    yield
    clear_rate_limit()


@pytest.mark.asyncio
async def test_rate_limit_single_request_passes():
    """First request from user should pass."""
    with patch("app.middleware.rate_limit.get_current_user_id", return_value="user-1"):
        result = await ai_user_with_rate_limit(user_id="user-1")
        assert result == "user-1"


@pytest.mark.asyncio
async def test_rate_limit_60_requests_pass():
    """60 requests in 60 seconds should all pass."""
    with patch("app.middleware.rate_limit.get_current_user_id", return_value="user-1"):
        for i in range(60):
            result = await ai_user_with_rate_limit(user_id="user-1")
            assert result == "user-1"


@pytest.mark.asyncio
async def test_rate_limit_61st_request_raises_429():
    """61st request within 60 seconds should raise 429."""
    with patch("app.middleware.rate_limit.get_current_user_id", return_value="user-1"):
        for i in range(60):
            await ai_user_with_rate_limit(user_id="user-1")
        # 61st request should fail
        with pytest.raises(HTTPException) as exc_info:
            await ai_user_with_rate_limit(user_id="user-1")
        assert exc_info.value.status_code == 429


@pytest.mark.asyncio
async def test_rate_limit_429_response_headers():
    """429 response includes X-RateLimit headers + Retry-After."""
    with patch("app.middleware.rate_limit.get_current_user_id", return_value="user-1"):
        for i in range(60):
            await ai_user_with_rate_limit(user_id="user-1")
        with pytest.raises(HTTPException) as exc_info:
            await ai_user_with_rate_limit(user_id="user-1")
        assert exc_info.value.headers is not None
        assert exc_info.value.headers["X-RateLimit-Limit"] == "60"
        assert exc_info.value.headers["X-RateLimit-Remaining"] == "0"
        assert exc_info.value.headers["Retry-After"] == "60"


@pytest.mark.asyncio
async def test_rate_limit_per_user_isolation():
    """Different users have independent rate limit buckets."""
    with patch("app.middleware.rate_limit.get_current_user_id") as mock_get_user:
        # user-1 makes 60 requests
        mock_get_user.return_value = "user-1"
        for i in range(60):
            await ai_user_with_rate_limit(user_id="user-1")
        # user-2's first request should still pass
        mock_get_user.return_value = "user-2"
        result = await ai_user_with_rate_limit(user_id="user-2")
        assert result == "user-2"


@pytest.mark.asyncio
async def test_rate_limit_window_slides():
    """Requests outside the 60-second window don't count."""
    real_time = time.time
    with patch("app.middleware.rate_limit.get_current_user_id", return_value="user-1"):
        # Make 60 requests
        for i in range(60):
            await ai_user_with_rate_limit(user_id="user-1")
        # Advance time by 61 seconds (using time.time mock)
        future_time = real_time() + 61
        with patch("app.middleware.rate_limit.time.time", return_value=future_time):
            # The 61st request should now pass (old timestamps expired)
            result = await ai_user_with_rate_limit(user_id="user-1")
            assert result == "user-1"


@pytest.mark.asyncio
async def test_rate_limit_clear_resets_state():
    """clear_rate_limit() resets the state for all users."""
    with patch("app.middleware.rate_limit.get_current_user_id", return_value="user-1"):
        for i in range(60):
            await ai_user_with_rate_limit(user_id="user-1")
        # Clear state
        clear_rate_limit()
        # New request should pass
        result = await ai_user_with_rate_limit(user_id="user-1")
        assert result == "user-1"


@pytest.mark.asyncio
async def test_rate_limit_distinct_users_dont_share_bucket():
    """100 different users should each be able to make 1 request without blocking each other."""
    with patch("app.middleware.rate_limit.get_current_user_id") as mock_get_user:
        for i in range(100):
            mock_get_user.return_value = f"user-{i}"
            result = await ai_user_with_rate_limit(user_id=f"user-{i}")
            assert result == f"user-{i}"


@pytest.mark.asyncio
async def test_rate_limit_returns_user_id():
    """Function returns the user_id on success."""
    with patch("app.middleware.rate_limit.get_current_user_id", return_value="specific-user-id"):
        result = await ai_user_with_rate_limit(user_id="specific-user-id")
        assert result == "specific-user-id"
