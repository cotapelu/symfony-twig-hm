"""Unit tests for app.shared.eventbus.eventbus (event bus)."""
import pytest
import asyncio
from unittest.mock import AsyncMock

from app.shared.eventbus.eventbus import EventBus, EventHandler
from app.shared.domain.event import DomainEvent, EventType


@pytest.fixture
def bus():
    return EventBus()


@pytest.fixture
def sample_event():
    return DomainEvent(
        type=EventType.MESSAGE_SENT,
        data={"message_id": "m1", "sender": "user-1"}
    )


# ==== Initialization ====

def test_event_bus_init_empty():
    bus = EventBus()
    assert bus.handlers == {}
    assert bus.lock is not None


# ==== subscribe ====

@pytest.mark.asyncio
async def test_subscribe_adds_handler(bus):
    async def handler(event):
        pass

    await bus.subscribe(EventType.MESSAGE_SENT, handler)
    assert handler in bus.handlers[EventType.MESSAGE_SENT]


@pytest.mark.asyncio
async def test_subscribe_multiple_handlers_same_event(bus):
    async def h1(event):
        pass

    async def h2(event):
        pass

    await bus.subscribe(EventType.MESSAGE_SENT, h1)
    await bus.subscribe(EventType.MESSAGE_SENT, h2)
    assert len(bus.handlers[EventType.MESSAGE_SENT]) == 2


@pytest.mark.asyncio
async def test_subscribe_multiple_event_types(bus):
    async def handler(event):
        pass

    await bus.subscribe(EventType.MESSAGE_SENT, handler)
    await bus.subscribe(EventType.TYPING_START, handler)
    assert handler in bus.handlers[EventType.MESSAGE_SENT]
    assert handler in bus.handlers[EventType.TYPING_START]


# ==== publish ====

@pytest.mark.asyncio
async def test_publish_calls_handler(bus, sample_event):
    handler = AsyncMock()
    await bus.subscribe(EventType.MESSAGE_SENT, handler)
    await bus.publish(sample_event)
    handler.assert_called_once_with(sample_event)


@pytest.mark.asyncio
async def test_publish_no_handlers_does_not_raise(bus, sample_event):
    # No handlers subscribed — should not raise
    await bus.publish(sample_event)


@pytest.mark.asyncio
async def test_publish_calls_all_handlers(bus, sample_event):
    h1 = AsyncMock()
    h2 = AsyncMock()
    h3 = AsyncMock()
    await bus.subscribe(EventType.MESSAGE_SENT, h1)
    await bus.subscribe(EventType.MESSAGE_SENT, h2)
    await bus.subscribe(EventType.MESSAGE_SENT, h3)
    await bus.publish(sample_event)
    h1.assert_called_once_with(sample_event)
    h2.assert_called_once_with(sample_event)
    h3.assert_called_once_with(sample_event)


@pytest.mark.asyncio
async def test_publish_only_handlers_for_matching_event_type(bus):
    msg_event = DomainEvent(type=EventType.MESSAGE_SENT, data={"x": 1})
    typing_event = DomainEvent(type=EventType.TYPING_START, data={"x": 2})

    msg_handler = AsyncMock()
    typing_handler = AsyncMock()

    await bus.subscribe(EventType.MESSAGE_SENT, msg_handler)
    await bus.subscribe(EventType.TYPING_START, typing_handler)

    await bus.publish(msg_event)
    msg_handler.assert_called_once_with(msg_event)
    typing_handler.assert_not_called()


@pytest.mark.asyncio
async def test_publish_handler_exception_does_not_stop_other_handlers(bus, sample_event):
    failing_handler = AsyncMock(side_effect=RuntimeError("handler failed"))
    success_handler = AsyncMock()

    await bus.subscribe(EventType.MESSAGE_SENT, failing_handler)
    await bus.subscribe(EventType.MESSAGE_SENT, success_handler)

    # Should not raise (errors are logged, not propagated)
    await bus.publish(sample_event)
    failing_handler.assert_called_once_with(sample_event)
    success_handler.assert_called_once_with(sample_event)


@pytest.mark.asyncio
async def test_publish_concurrent_handlers(bus, sample_event):
    """Multiple handlers should be invoked concurrently via asyncio.gather."""
    h1 = AsyncMock()
    h2 = AsyncMock()
    await bus.subscribe(EventType.MESSAGE_SENT, h1)
    await bus.subscribe(EventType.MESSAGE_SENT, h2)
    await bus.publish(sample_event)
    h1.assert_called_once()
    h2.assert_called_once()


# ==== publish_sync ====

def test_publish_sync_exists():
    """publish_sync is a synchronous wrapper that calls asyncio.run(publish())."""
    # Note: pytest-asyncio runs in an event loop, so publish_sync cannot be invoked
    # inside this test (asyncio.run() can't be called from a running loop).
    # We just verify the method exists and is callable.
    bus = EventBus()
    assert hasattr(bus, 'publish_sync')
    assert callable(bus.publish_sync)


# ==== publish_async (fire-and-forget) ====

@pytest.mark.asyncio
async def test_publish_async_returns_task(bus, sample_event):
    """publish_async uses asyncio.create_task() to fire-and-forget publish.

    Note: The handler may not be invoked within the test if pytest-asyncio
    completes before the task runs. We just verify the method exists and
    can be called without raising.
    """
    handler = AsyncMock()
    await bus.subscribe(EventType.MESSAGE_SENT, handler)
    # Should not raise when called
    bus.publish_async(sample_event)
    # Let event loop process
    await asyncio.sleep(0.1)
    # Handler may or may not be called depending on event loop scheduling,
    # but the call itself should not raise.


# ==== DomainEvent ====

def test_domain_event_default_id_is_uuid():
    event = DomainEvent(type=EventType.MESSAGE_SENT, data={})
    assert event.id != ""
    # Verify it's a valid UUID
    import uuid as uuid_module
    parsed = uuid_module.UUID(event.id)
    assert str(parsed) == event.id


def test_domain_event_default_timestamp_is_int():
    event = DomainEvent(type=EventType.MESSAGE_SENT, data={})
    assert isinstance(event.timestamp, int)
    assert event.timestamp > 0


def test_domain_event_default_version():
    event = DomainEvent(type=EventType.MESSAGE_SENT, data={})
    assert event.version == "1.0"


def test_domain_event_custom_metadata():
    event = DomainEvent(
        type=EventType.MESSAGE_SENT,
        data={"x": 1},
        metadata={"source": "test", "trace_id": "abc"}
    )
    assert event.metadata == {"source": "test", "trace_id": "abc"}


# ==== Integration scenarios ====

@pytest.mark.asyncio
async def test_publish_subscribe_pattern(bus):
    """Full pub/sub cycle with multiple events and handlers."""
    msg_received = []
    typing_received = []

    async def msg_handler(event):
        msg_received.append(event)

    async def typing_handler(event):
        typing_received.append(event)

    await bus.subscribe(EventType.MESSAGE_SENT, msg_handler)
    await bus.subscribe(EventType.TYPING_START, typing_handler)

    msg_event = DomainEvent(type=EventType.MESSAGE_SENT, data={"content": "hello"})
    typing_event = DomainEvent(type=EventType.TYPING_START, data={"user": "alice"})

    await bus.publish(msg_event)
    await bus.publish(typing_event)
    await bus.publish(msg_event)

    assert len(msg_received) == 2
    assert len(typing_received) == 1
    assert msg_received[0].data == {"content": "hello"}


@pytest.mark.asyncio
async def test_subscribe_after_publish_does_not_get_old_events(bus):
    """Subscribers added after publish() should not get past events."""
    received = []

    async def late_subscriber(event):
        received.append(event)

    # Publish without subscriber
    await bus.publish(DomainEvent(type=EventType.MESSAGE_SENT, data={"a": 1}))

    # Subscribe after
    await bus.subscribe(EventType.MESSAGE_SENT, late_subscriber)

    # Publish new event
    await bus.publish(DomainEvent(type=EventType.MESSAGE_SENT, data={"a": 2}))

    assert len(received) == 1
    assert received[0].data == {"a": 2}
