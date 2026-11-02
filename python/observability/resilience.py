"""
Resilience Patterns
Per GOAL.md Section 4.4: Retry, Timeout, Circuit Breaker, Bulkhead, Fallback
"""

import asyncio
import time
import logging
from typing import Callable, TypeVar, Any, Dict, Optional
from dataclasses import dataclass, field
from enum import Enum
import random

T = TypeVar('T')

class CircuitState(Enum):
    CLOSED = "closed"
    OPEN = "open"
    HALF_OPEN = "half_open"

def circuit_breaker(
    failure_threshold: int = 5,
    recovery_timeout: float = 60.0,
    half_open_max_calls: int = 3
) -> Callable[[Callable[..., T]], Callable[..., T]]:
    """
    Circuit Breaker decorator for async functions
    Prevents cascading failures by failing fast when downstream service is unhealthy
    """
    def decorator(func: Callable[..., T]) -> Callable[..., T]:
        cb = CircuitBreaker(
            failure_threshold=failure_threshold,
            recovery_timeout=recovery_timeout,
            half_open_max_calls=half_open_max_calls
        )
        from functools import wraps
        @wraps(func)
        async def wrapper(*args, **kwargs) -> T:
            return await cb.execute(func, *args, **kwargs)
        # expose the underlying breaker for testing/inspection if needed
        wrapper.circuit_breaker = cb  # type: ignore[attr-defined]
        return wrapper
    return decorator

@dataclass
class CircuitBreaker:
    """
    Circuit Breaker implementation
    Prevents cascading failures by failing fast when downstream service is unhealthy
    """
    failure_threshold: int = 5
    recovery_timeout: float = 60.0  # seconds
    half_open_max_calls: int = 3

    state: CircuitState = field(default=CircuitState.CLOSED, init=False)
    failure_count: int = field(default=0, init=False)
    last_failure_time: Optional[float] = field(default=None, init=False)
    half_open_calls: int = field(default=0, init=False)

    async def execute(self, func: Callable[..., T], *args, **kwargs) -> T:
        """Execute function with circuit breaker protection"""
        if self.state == CircuitState.OPEN:
            if time.time() - self.last_failure_time >= self.recovery_timeout:
                self.state = CircuitState.HALF_OPEN
                self.half_open_calls = 0
            else:
                raise Exception(f"Circuit breaker OPEN - service unavailable (last failure {time.time() - self.last_failure_time:.1f}s ago)")

        try:
            result = await func(*args, **kwargs) if asyncio.iscoroutinefunction(func) else func(*args, **kwargs)
            # Success - reset failure count if in HALF_OPEN
            if self.state == CircuitState.HALF_OPEN:
                self.state = CircuitState.CLOSED
                self.failure_count = 0
                self.half_open_calls = 0
            elif self.state == CircuitState.CLOSED:
                self.failure_count = max(0, self.failure_count - 1)  # Gradually reduce
            return result
        except Exception as e:
            self.failure_count += 1
            self.last_failure_time = time.time()
            if self.failure_count >= self.failure_threshold:
                self.state = CircuitState.OPEN
            raise e

async def retry_with_backoff(
    func: Callable[..., T],
    *args,
    max_retries: int = 3,
    base_delay: float = 0.1,
    max_delay: float = 10.0,
    jitter: bool = True,
    retry_on: tuple = (Exception,),
    **kwargs
) -> T:
    """
    Retry with exponential backoff and jitter
    GOAL.md: "Retry: exp backoff+jitter, max 3-5"
    """
    last_exception = None

    for attempt in range(max_retries + 1):
        try:
            return await func(*args, **kwargs) if asyncio.iscoroutinefunction(func) else func(*args, **kwargs)
        except retry_on as e:
            last_exception = e
            if attempt == max_retries:
                break

            # Exponential backoff with jitter
            delay = min(base_delay * (2 ** attempt), max_delay)
            if jitter:
                delay = delay * (0.5 + random.random())

            await asyncio.sleep(delay)

    raise last_exception


def retry_with_backoff_sync(
    func: Callable[..., T],
    *args,
    max_retries: int = 3,
    base_delay: float = 0.1,
    max_delay: float = 10.0,
    jitter: bool = True,
    retry_on: tuple = (Exception,),
    **kwargs
) -> T:
    """
    Synchronous version of retry with exponential backoff and jitter.
    """
    last_exception = None
    for attempt in range(max_retries + 1):
        try:
            return func(*args, **kwargs)
        except retry_on as e:
            last_exception = e
            if attempt == max_retries:
                break
            delay = min(base_delay * (2 ** attempt), max_delay)
            if jitter:
                delay = delay * (0.5 + random.random())
            time.sleep(delay)
    raise last_exception

def with_timeout(
    timeout: float = 10.0,
    raise_on_timeout: bool = True
) -> Callable[[Callable[..., T]], Callable[..., T]]:
    """
    Timeout decorator for async functions
    GOAL.md: "Timeout: all I/O (10s default)"
    """
    def decorator(func: Callable[..., T]) -> Callable[..., T]:
        async def wrapper(*args, **kwargs) -> T:
            try:
                return await asyncio.wait_for(
                    func(*args, **kwargs) if asyncio.iscoroutinefunction(func) else func(*args, **kwargs),
                    timeout=timeout
                )
            except asyncio.TimeoutError:
                if raise_on_timeout:
                    raise TimeoutError(f"Operation timed out after {timeout}s")
                return None  # Or appropriate default value
        return wrapper
    return decorator

class Bulkhead:
    """
    Bulkhead pattern: isolate resource pools
    Prevents one service from exhausting all resources
    """
    def __init__(self, max_concurrent: int = 10):
        self.semaphore = asyncio.Semaphore(max_concurrent)
        self.max_concurrent = max_concurrent

    async def execute(self, func: Callable[..., T], *args, **kwargs) -> T:
        """Execute function with bulkhead limit"""
        async with self.semaphore:
            return await func(*args, **kwargs) if asyncio.iscoroutinefunction(func) else func(*args, **kwargs)

class Fallback:
    """
    Fallback pattern: provide degraded response when primary fails
    """
    @staticmethod
    async def with_fallback(
        primary: Callable[..., T],
        fallback: Callable[..., T],
        *args, **kwargs
    ) -> T:
        """Try primary, on failure use fallback"""
        try:
            return await primary(*args, **kwargs) if asyncio.iscoroutinefunction(primary) else primary(*args, **kwargs)
        except Exception as e:
            # Log fallback activation
            logger = logging.getLogger("resilience.fallback")
            logger.warning(f"Primary failed, using fallback: {e}")
            return await fallback(*args, **kwargs) if asyncio.iscoroutinefunction(fallback) else fallback(*args, **kwargs)


def bulkhead(max_concurrent: int = 10) -> Callable[[Callable[..., T]], Callable[..., T]]:
    """
    Bulkhead decorator for async functions
    Limits the number of concurrent executions to protect resources.
    """
    def decorator(func: Callable[..., T]) -> Callable[..., T]:
        bulk = Bulkhead(max_concurrent)
        from functools import wraps
        @wraps(func)
        async def wrapper(*args, **kwargs) -> T:
            return await bulk.execute(func, *args, **kwargs)
        return wrapper
    return decorator
