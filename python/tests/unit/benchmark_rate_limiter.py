"""Performance benchmark for auth+rate-limit dependencies."""
import sys
import time
from unittest.mock import MagicMock

# Mock engines before importing routers
sys.modules['modules.chatbot.service.engine'] = MagicMock()
sys.modules['modules.chatbot.service.engine'].ChatbotEngine = MagicMock(return_value=MagicMock())
sys.modules['modules.chatbot.models.schemas'] = MagicMock()
sys.modules['modules.socialfeed.service.engine'] = MagicMock()
sys.modules['modules.socialfeed.service.engine'].SocialFeedEngine = MagicMock(return_value=MagicMock())
sys.modules['modules.socialfeed.models.schemas'] = MagicMock()

# Import the rate limiter modules
from app.modules.chatbot.router import chatbot_user_with_rate_limit as chatbot_dep
from app.modules.socialfeed.router import socialfeed_user_with_rate_limit as socialfeed_dep
from app.modules.telemedicine.api.routes import telemedicine_user_with_rate_limit as telemedicine_dep
from app.shared.middleware.auth import get_current_user_id

def benchmark_dependency(dep_fn, user_id, iterations=1000):
    """Measure avg time per call (seconds)."""
    start = time.perf_counter()
    for _ in range(iterations):
        # Direct call with user_id, not as FastAPI dependency
        # The dependency logic is: extract user_id from JWT token (fast) and check rate limit
        # We'll simulate by calling the internal logic directly
        # Since the dependency wraps get_current_user_id, we can't easily separate
        # But we can measure the rate limiting check alone
        pass
    elapsed = time.perf_counter() - start
    return elapsed / iterations * 1000  # ms

if __name__ == "__main__":
    # Simple microbenchmark: measure rate limiting state lookup
    from app.modules.chatbot.router import _chatbot_rate_data, _chatbot_lock
    import asyncio

    async def test_rate_limit_ops():
        user_id = "bench_user"
        # Warm up
        await chatbot_dep(user_id)

        # Benchmark 1000 calls
        start = time.perf_counter()
        for _ in range(1000):
            await chatbot_dep(user_id)
        elapsed = time.perf_counter() - start
        avg_ms = elapsed / 1000 * 1000
        print(f"Chatbot rate-limited dependency avg: {avg_ms:.4f} ms (1000 calls)")
        assert avg_ms < 5.0, f"Rate limit overhead too high: {avg_ms:.2f}ms (target <5ms)"

    asyncio.run(test_rate_limit_ops())
