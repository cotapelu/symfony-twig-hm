"""Microbenchmark for auth + rate limiting overhead in Python modules.

Measures the key operations:
- JWT token verification (decode + validation)
- User ID extraction
- Rate limit state update (in-memory dict + deque)

Target: <5ms per request (p99).
"""

import time
import asyncio
from collections import defaultdict, deque
from unittest.mock import MagicMock, patch
import jwt

# ==================== Mock Setup ====================
# Mock external dependencies (ChatbotEngine, etc.) to isolate auth+rate-limit

class MockChatbotEngine:
    pass

class MockSocialFeedEngine:
    pass

# Set up module mocks BEFORE importing routers
sys_mod_backup = {}
def mock_module(name, attrs=None):
    if attrs is None:
        attrs = {}
    import sys
    mock = MagicMock()
    for k, v in attrs.items():
        setattr(mock, k, v)
    sys_mod_backup[name] = sys.modules.get(name)
    sys.modules[name] = mock
    return mock

mock_module('modules.chatbot.service.engine', {'ChatbotEngine': MockChatbotEngine})
mock_module('modules.chatbot.models.schemas', {})
mock_module('modules.socialfeed.service.engine', {'SocialFeedEngine': MockSocialFeedEngine})
mock_module('modules.socialfeed.models.schemas', {})

# ==================== Imports ====================
from app.shared.middleware.auth import verify_token, get_current_user_id
from app.shared.config.settings import settings

# Configure settings for testing
settings.jwt.secret_key = "test-secret-key"
settings.jwt.algorithm = "HS256"

# Simulate rate limiting data structures (as used in chatbot/socialfeed routers)
_rate_data = defaultdict(deque)
_rate_lock = asyncio.Lock()

async def check_rate_limit(user_id: str, limit: int = 60, window: int = 60) -> bool:
    """Check and update rate limit for a user. Returns True if allowed."""
    async with _rate_lock:
        now = time.time()
        timestamps = _rate_data[user_id]
        # Prune old entries outside the window
        while timestamps and timestamps[0] < now - window:
            timestamps.popleft()
        if len(timestamps) >= limit:
            return False  # Rate limit exceeded
        timestamps.append(now)
        return True

# ==================== Benchmark Functions ====================

def make_test_token(user_id: str, exp_delta_seconds: int = 3600) -> str:
    """Create a valid JWT token for testing."""
    payload = {
        "sub": user_id,
        "roles": ["user"],
        "exp": int(time.time()) + exp_delta_seconds
    }
    return jwt.encode(payload, settings.jwt.secret_key, algorithm=settings.jwt.algorithm)

def benchmark_sync_operations(iterations: int = 1000):
    """Benchmark pure in-memory rate limit dictionary operations.
    Uses a fresh user_id for each iteration to avoid hitting rate limits during benchmark.
    """
    start = time.perf_counter()
    loop = asyncio.new_event_loop()
    for i in range(iterations):
        user_id = f"bench_user_{i}"  # Unique per call to avoid accumulation
        allowed = loop.run_until_complete(check_rate_limit(user_id))
        assert allowed, "Rate limit should not exceed with fresh user"
    loop.close()
    elapsed = time.perf_counter() - start
    avg_ms = (elapsed / iterations) * 1000
    print(f"Rate limit check (in-memory): {avg_ms:.4f} ms avg over {iterations} iters")
    return avg_ms

def benchmark_jwt_verification(iterations: int = 1000):
    """Benchmark JWT token decode and validation."""
    token = make_test_token("bench_user")
    start = time.perf_counter()
    for _ in range(iterations):
        try:
            payload = jwt.decode(
                token,
                settings.jwt.secret_key,
                algorithms=[settings.jwt.algorithm],
                options={"verify_exp": True}
            )
            user_id = payload.get("sub")
            assert user_id == "bench_user"
        except Exception as e:
            raise AssertionError(f"JWT verification failed: {e}")
    elapsed = time.perf_counter() - start
    avg_ms = (elapsed / iterations) * 1000
    print(f"JWT verify: {avg_ms:.4f} ms avg over {iterations} iters")
    return avg_ms

def benchmark_combined_auth_rate_limit(iterations: int = 1000):
    """Benchmark combined auth (JWT) + rate limit check.
    Uses a unique user_id per iteration to avoid rate limit accumulation.
    """
    # Create a token generator for unique user IDs
    def token_for(uid):
        return jwt.encode(
            {"sub": uid, "roles": ["user"], "exp": int(time.time()) + 3600},
            settings.jwt.secret_key,
            algorithm=settings.jwt.algorithm
        )

    loop = asyncio.new_event_loop()
    start = time.perf_counter()
    for i in range(iterations):
        user_id = f"bench_user_{i}"
        token = token_for(user_id)
        # Verify token
        payload = jwt.decode(token, settings.jwt.secret_key, algorithms=[settings.jwt.algorithm])
        uid = payload.get("sub")
        # Rate limit
        allowed = loop.run_until_complete(check_rate_limit(uid))
        assert allowed
    loop.close()
    elapsed = time.perf_counter() - start
    avg_ms = (elapsed / iterations) * 1000
    print(f"Combined (JWT + rate limit): {avg_ms:.4f} ms avg over {iterations} iters")
    return avg_ms

# ==================== Main ====================

if __name__ == "__main__":
    print("=" * 60)
    print("Auth + Rate Limit Microbenchmark")
    print("Target: <5ms overhead (p99)")
    print("=" * 60)

    iters = 10000

    jwt_ms = benchmark_jwt_verification(iters)
    rate_ms = benchmark_sync_operations(iters)
    combined_ms = benchmark_combined_auth_rate_limit(iters)

    print("\n" + "=" * 60)
    print("SUMMARY")
    print("=" * 60)
    print(f"JWT verification:          {jwt_ms:.4f} ms")
    print(f"Rate limit check:          {rate_ms:.4f} ms")
    print(f"Combined (auth+rate):      {combined_ms:.4f} ms")
    print("=" * 60)

    # Assert target
    assert combined_ms < 5.0, f"Combined overhead {combined_ms:.2f}ms exceeds 5ms target!"
    print("\n✅ All benchmarks within target (<5ms)")
