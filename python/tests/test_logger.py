"""Unit tests for app.shared.utils.logger (structured logging)."""
import json
import logging
import pytest

from app.shared.utils.logger import (
    RequestIDFilter,
    JSONFormatter,
    setup_logger,
)
from app.shared.context import request_id_var


@pytest.fixture(autouse=True)
def reset_logger():
    """Remove handlers from all loggers between tests to avoid duplicate output."""
    yield
    for name in list(logging.Logger.manager.loggerDict):
        logger = logging.getLogger(name)
        logger.handlers.clear()


@pytest.fixture(autouse=True)
def reset_context():
    """Reset contextvar between tests."""
    token = request_id_var.set("-")
    try:
        yield
    finally:
        request_id_var.reset(token)


# ==== RequestIDFilter ====

def test_filter_attaches_request_id():
    filt = RequestIDFilter()
    record = logging.LogRecord(
        name="test", level=logging.INFO, pathname="x.py", lineno=1,
        msg="m", args=(), exc_info=None
    )
    filt.filter(record)
    assert record.request_id == "-"


def test_filter_with_set_request_id():
    filt = RequestIDFilter()
    record = logging.LogRecord(
        name="test", level=logging.INFO, pathname="x.py", lineno=1,
        msg="m", args=(), exc_info=None
    )
    token = request_id_var.set("test-correlation-id")
    try:
        filt.filter(record)
        assert record.request_id == "test-correlation-id"
    finally:
        request_id_var.reset(token)


def test_filter_returns_true():
    filt = RequestIDFilter()
    record = logging.LogRecord(
        name="test", level=logging.INFO, pathname="x.py", lineno=1,
        msg="m", args=(), exc_info=None
    )
    assert filt.filter(record) is True


# ==== JSONFormatter ====

def test_json_formatter_produces_valid_json():
    formatter = JSONFormatter()
    record = logging.LogRecord(
        name="my_logger", level=logging.INFO, pathname="x.py", lineno=42,
        msg="hello world", args=(), exc_info=None
    )
    output = formatter.format(record)
    parsed = json.loads(output)
    assert isinstance(parsed, dict)


def test_json_formatter_includes_core_fields():
    formatter = JSONFormatter()
    record = logging.LogRecord(
        name="my_logger", level=logging.INFO, pathname="x.py", lineno=42,
        msg="hello", args=(), exc_info=None
    )
    output = formatter.format(record)
    parsed = json.loads(output)
    assert "timestamp" in parsed
    assert parsed["level"] == "INFO"
    assert parsed["logger"] == "my_logger"
    assert parsed["message"] == "hello"
    assert parsed["line"] == 42
    # record.module is the basename of pathname (without extension)
    assert parsed["module"] == "x"


def test_json_formatter_includes_request_id():
    formatter = JSONFormatter()
    record = logging.LogRecord(
        name="test", level=logging.INFO, pathname="x.py", lineno=1,
        msg="m", args=(), exc_info=None
    )
    record.request_id = "test-correlation-id"
    output = formatter.format(record)
    parsed = json.loads(output)
    assert parsed["request_id"] == "test-correlation-id"


def test_json_formatter_request_id_default_dash():
    formatter = JSONFormatter()
    record = logging.LogRecord(
        name="test", level=logging.INFO, pathname="x.py", lineno=1,
        msg="m", args=(), exc_info=None
    )
    output = formatter.format(record)
    parsed = json.loads(output)
    assert parsed["request_id"] == "-"


def test_json_formatter_includes_exception_info():
    formatter = JSONFormatter()
    try:
        raise ValueError("test error")
    except ValueError:
        import sys
        record = logging.LogRecord(
            name="test", level=logging.ERROR, pathname="x.py", lineno=1,
            msg="error occurred", args=(), exc_info=sys.exc_info()
        )
    output = formatter.format(record)
    parsed = json.loads(output)
    assert "exception" in parsed
    assert "ValueError" in parsed["exception"]


def test_json_formatter_includes_extra_attributes():
    """Custom attributes attached via `record.extra = {...}` are included.

    Note: Python logging's `extra={...}` parameter attaches attributes directly
    to the record (e.g., record.count = 42), NOT as `record.extra = {...}`.
    To trigger the source's `if hasattr(record, "extra")` branch, manually set
    record.extra after construction.
    """
    formatter = JSONFormatter()
    record = logging.LogRecord(
        name="test", level=logging.INFO, pathname="x.py", lineno=1,
        msg="event", args=(), exc_info=None
    )
    record.extra = {"count": 5, "user_id": "u1"}
    output = formatter.format(record)
    parsed = json.loads(output)
    assert parsed["count"] == 5
    assert parsed["user_id"] == "u1"


def test_json_formatter_no_exception_when_no_exc_info():
    formatter = JSONFormatter()
    record = logging.LogRecord(
        name="test", level=logging.INFO, pathname="x.py", lineno=1,
        msg="m", args=(), exc_info=None
    )
    output = formatter.format(record)
    parsed = json.loads(output)
    assert "exception" not in parsed


def test_json_formatter_timestamp_is_iso_string():
    formatter = JSONFormatter()
    record = logging.LogRecord(
        name="test", level=logging.INFO, pathname="x.py", lineno=1,
        msg="m", args=(), exc_info=None
    )
    output = formatter.format(record)
    parsed = json.loads(output)
    # ISO format ends with +00:00 or similar
    assert "T" in parsed["timestamp"]


# ==== setup_logger ====

def test_setup_logger_returns_logger():
    logger = setup_logger("test_logger_1")
    assert isinstance(logger, logging.Logger)


def test_setup_logger_uses_correct_level():
    logger = setup_logger("test_logger_2", level="DEBUG")
    assert logger.level == logging.DEBUG


def test_setup_logger_default_level_is_info():
    logger = setup_logger("test_logger_3")
    assert logger.level == logging.INFO


def test_setup_logger_invalid_level_raises_attribute_error():
    """Documented source quirk: setup_logger does not validate the level argument.

    Per Code Preservation rule, the source is left intact. Passing an invalid level
    string causes AttributeError from `getattr(logging, 'INVALID')`.
    """
    import pytest
    with pytest.raises(AttributeError):
        setup_logger("test_logger_invalid", level="INVALID")


def test_setup_logger_json_format():
    logger = setup_logger("test_logger_5", format_type="json")
    assert len(logger.handlers) == 1
    handler = logger.handlers[0]
    assert isinstance(handler.formatter, JSONFormatter)


def test_setup_logger_plain_format():
    logger = setup_logger("test_logger_6", format_type="plain")
    assert len(logger.handlers) == 1
    handler = logger.handlers[0]
    assert not isinstance(handler.formatter, JSONFormatter)


def test_setup_logger_default_format_is_json():
    logger = setup_logger("test_logger_7")
    handler = logger.handlers[0]
    assert isinstance(handler.formatter, JSONFormatter)


def test_setup_logger_attaches_request_id_filter():
    logger = setup_logger("test_logger_8")
    handler = logger.handlers[0]
    # Filter should be a RequestIDFilter instance
    assert any(isinstance(f, RequestIDFilter) for f in handler.filters)


def test_setup_logger_handler_outputs_to_stdout():
    logger = setup_logger("test_logger_9")
    handler = logger.handlers[0]
    import sys
    assert isinstance(handler, logging.StreamHandler)
    assert handler.stream == sys.stdout


def test_setup_logger_idempotent_setup():
    """Multiple setup calls add multiple handlers."""
    logger = setup_logger("test_logger_10")
    setup_logger("test_logger_10")
    assert len(logger.handlers) == 2


def test_setup_logger_logs_message():
    """End-to-end: setup, log a message, verify no exception."""
    logger = setup_logger("test_logger_11")
    # Should not raise
    logger.info("test message")
    logger.warning("warning message")
    logger.error("error message")


# ==== Integration scenarios ====

def test_json_log_end_to_end():
    """Setup logger, log a message, verify JSON output structure."""
    logger = setup_logger("integration_test")
    handler = logger.handlers[0]

    # Capture output
    import io
    captured = io.StringIO()
    handler.stream = captured

    logger.info("test event")

    output = captured.getvalue().strip()
    parsed = json.loads(output)
    assert parsed["message"] == "test event"
    assert parsed["level"] == "INFO"
    # NOTE: Python logging's `extra={...}` parameter attaches attributes directly
    # to the record (e.g., record.count = 42), NOT as `record.extra = {...}`.
    # The source JSONFormatter checks `hasattr(record, "extra")` which is False,
    # so logger.info(..., extra={"count": 42}) does NOT include count in output.
    # This is a source bug (per Code Preservation, source left intact).


def test_json_log_includes_request_id_from_context():
    logger = setup_logger("integration_test_2")
    handler = logger.handlers[0]
    import io
    captured = io.StringIO()
    handler.stream = captured

    token = request_id_var.set("trace-123")
    try:
        logger.info("event with trace")
        parsed = json.loads(captured.getvalue().strip())
        assert parsed["request_id"] == "trace-123"
    finally:
        request_id_var.reset(token)
