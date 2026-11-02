"""
Rate limiting dependency for AI endpoints.
Sliding window: 60 requests per minute per user.
"""

import time
import asyncio
from collections import defaultdict, deque
from fastapi import Depends, HTTPException, status
from typing import Dict, Deque

from app.shared.middleware.auth import get_current_user_id

# Module-level storage (safe for single-process dev)
_rate_data: Dict[str, Deque[float]] = defaultdict(deque)
_lock = asyncio.Lock()

def clear_rate_limit():
    """Clear rate limit data (for testing)."""
    _rate_data.clear()

async def ai_user_with_rate_limit(user_id: str = Depends(get_current_user_id)) -> str:
    """Dependency that enforces rate limit and returns user_id."""
    async with _lock:
        now = time.time()
        timestamps = _rate_data[user_id]
        # Remove entries older than 60 seconds
        while timestamps and timestamps[0] < now - 60:
            timestamps.popleft()
        if len(timestamps) >= 60:
            raise HTTPException(
                status_code=status.HTTP_429_TOO_MANY_REQUESTS,
                detail="Rate limit exceeded. Maximum 60 AI requests per minute.",
                headers={
                    "X-RateLimit-Limit": "60",
                    "X-RateLimit-Remaining": "0",
                    "Retry-After": "60"
                }
            )
        timestamps.append(now)
    return user_id
