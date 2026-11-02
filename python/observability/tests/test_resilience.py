"""Unit tests for resilience patterns (GOAL.md Section 4.4)"""
import asyncio
import time
import pytest
from unittest.mock import AsyncMock, MagicMock
from python.observability.resilience import (
    CircuitBreaker, CircuitState, retry_with_backoff,
    with_timeout, Bulkhead, Fallback
)


class TestCircuitBreaker:
    @pytest.mark.asyncio
    async def test_success_closes_breaker(self):
        cb = CircuitBreaker(failure_threshold=1)
        async def ok_func():
            return "ok"
        result = await cb.execute(ok_func)
        assert result == "ok"
        assert cb.state == CircuitState.CLOSED
        assert cb.failure_count == 0

    @pytest.mark.asyncio
    async def test_failure_opens_breaker(self):
        cb = CircuitBreaker(failure_threshold=2, recovery_timeout=0.1)
        async def fail_func():
            raise ValueError("fail")
        with pytest.raises(ValueError):
            await cb.execute(fail_func)
        assert cb.failure_count == 1
        with pytest.raises(ValueError):
            await cb.execute(fail_func)
        assert cb.state == CircuitState.OPEN
        assert cb.failure_count == 2

    @pytest.mark.asyncio
    async def test_open_breaker_fails_fast(self):
        cb = CircuitBreaker(failure_threshold=1, recovery_timeout=1)
        cb.state = CircuitState.OPEN
        cb.last_failure_time = time.time()
        async def ok_func():
            return "ok"
        with pytest.raises(Exception) as excinfo:
            await cb.execute(ok_func)
        assert "Circuit breaker OPEN" in str(excinfo.value)

    @pytest.mark.asyncio
    async def test_half_open_allows_limited_calls(self):
        cb = CircuitBreaker(failure_threshold=1, recovery_timeout=0, half_open_max_calls=1)
        cb.state = CircuitState.HALF_OPEN
        cb.half_open_calls = 0
        async def succeed():
            return "ok"
        result = await cb.execute(succeed)
        assert result == "ok"
        assert cb.state == CircuitState.CLOSED

    @pytest.mark.asyncio
    async def test_half_open_failure_reopens(self):
        cb = CircuitBreaker(failure_threshold=1, recovery_timeout=0, half_open_max_calls=1)
        cb.state = CircuitState.HALF_OPEN
        cb.half_open_calls = 0
        async def fail_func():
            raise ValueError("fail")
        with pytest.raises(ValueError):
            await cb.execute(fail_func)
        # After failure in HALF_OPEN, breaker opens again
        assert cb.state == CircuitState.OPEN


@pytest.mark.asyncio
async def test_retry_with_backoff_success_after_failures():
    attempts = 0

    async def flaky():
        nonlocal attempts
        attempts += 1
        if attempts < 3:
            raise ValueError("temp failure")
        return "success"

    result = await retry_with_backoff(flaky, max_retries=3, base_delay=0.01)
    assert result == "success"
    assert attempts == 3

@pytest.mark.asyncio
async def test_retry_with_backoff_exhausts_retries():
    attempts = 0

    async def always_fail():
        nonlocal attempts
        attempts += 1
        raise ValueError("persistent failure")

    with pytest.raises(ValueError):
        await retry_with_backoff(always_fail, max_retries=2, base_delay=0.01)
    # initial + 2 retries = 3 attempts
    assert attempts == 3

def test_with_timeout_success():
    @with_timeout(timeout=1)
    async def quick():
        return "done"
    result = asyncio.run(quick())
    assert result == "done"

def test_with_timeout_raises_on_timeout():
    @with_timeout(timeout=0.1, raise_on_timeout=True)
    async def slow():
        await asyncio.sleep(1)
        return "late"
    with pytest.raises(TimeoutError):
        asyncio.run(slow())

def test_with_timeout_returns_none_on_timeout_when_not_raised():
    @with_timeout(timeout=0.1, raise_on_timeout=False)
    async def slow():
        await asyncio.sleep(1)
        return "late"
    result = asyncio.run(slow())
    assert result is None

class TestBulkhead:
    @pytest.mark.asyncio
    async def test_concurrency_limit(self):
        bulk = Bulkhead(max_concurrent=2)
        execution_order = []

        async def worker(name):
            async with bulk.semaphore:
                execution_order.append(f"start-{name}")
                await asyncio.sleep(0.1)
                execution_order.append(f"end-{name}")
            return name

        tasks = [worker("a"), worker("b"), worker("c")]
        results = await asyncio.gather(*tasks, return_exceptions=True)
        assert len(results) == 3

class TestFallback:
    @pytest.mark.asyncio
    async def test_fallback_on_exception(self):
        async def fail():
            raise ValueError("fail")
        result = await Fallback.with_fallback(fail, lambda: "default")
        assert result == "default"

    @pytest.mark.asyncio
    async def test_fallback_returns_success_when_no_exception(self):
        async def succeed():
            return "ok"
        result = await Fallback.with_fallback(succeed, lambda: "default")
        assert result == "ok"
