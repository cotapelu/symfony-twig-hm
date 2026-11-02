"""
Observability Middleware for FastAPI
Provides: Correlation ID, Structured Logging, Metrics, Request Tracing
"""

import json
import logging
import time
import uuid
from contextvars import ContextVar
from typing import Callable, Dict, Any

from fastapi import Request, Response
from fastapi.responses import JSONResponse
from starlette.middleware.base import BaseHTTPMiddleware

# Context variables for correlation
correlation_id_var: ContextVar[str] = ContextVar('correlation_id', default='')
request_start_var: ContextVar[float] = ContextVar('request_start', default=0.0)

# Structured logging setup
class JsonFormatter(logging.Formatter):
    """Format logs as JSON"""
    def format(self, record):
        log_object = {
            "timestamp": self.formatTime(record, self.datefmt),
            "level": record.levelname,
            "logger": record.name,
            "message": record.getMessage(),
            "module": record.module,
            "function": record.funcName,
            "line": record.lineno,
        }
        # Add correlation ID if available
        try:
            cid = correlation_id_var.get()
            if cid:
                log_object["correlation_id"] = cid
        except LookupError:
            pass
        # Add extra fields
        if hasattr(record, 'extra_fields'):
            log_object.update(record.extra_fields)
        # Add exception info if present
        if record.exc_info:
            log_object["exception"] = self.formatException(record.exc_info)
        return json.dumps(log_object)

def setup_logging(level=logging.INFO):
    """Configure root logger with JSON formatting"""
    root_logger = logging.getLogger()
    root_logger.setLevel(level)

    # Remove existing handlers
    root_logger.handlers.clear()

    handler = logging.StreamHandler()
    handler.setFormatter(JsonFormatter())
    root_logger.addHandler(handler)

    # Reduce noise from uvicorn
    logging.getLogger("uvicorn").setLevel(logging.WARNING)
    logging.getLogger("uvicorn.access").setLevel(logging.WARNING)

def get_logger(name: str) -> logging.Logger:
    """Get a logger with the given name"""
    return logging.getLogger(name)

# Metrics collection (simple in-memory, can be replaced with Prometheus client)
class MetricsCollector:
    """Collect application metrics"""
    def __init__(self):
        self.request_count = 0
        self.error_count = 0
        self.response_times: list[float] = []
        self.status_codes: Dict[int, int] = {}

    def record_request(self, status_code: int, duration: float):
        self.request_count += 1
        self.response_times.append(duration)
        self.status_codes[status_code] = self.status_codes.get(status_code, 0) + 1
        if status_code >= 400:
            self.error_count += 1

    def get_metrics(self) -> Dict[str, Any]:
        """Return current metrics snapshot"""
        if self.response_times:
            sorted_times = sorted(self.response_times)
            p50 = sorted_times[int(len(sorted_times) * 0.5)]
            p99 = sorted_times[int(len(sorted_times) * 0.99)] if len(sorted_times) >= 100 else sorted_times[-1]
        else:
            p50 = p99 = 0.0
        return {
            "total_requests": self.request_count,
            "error_count": self.error_count,
            "error_rate": self.error_count / self.request_count if self.request_count > 0 else 0.0,
            "p50_response_time": p50,
            "p99_response_time": p99,
            "status_codes": self.status_codes.copy(),
        }

# Global metrics instance (per-process)
metrics = MetricsCollector()

class ObservabilityMiddleware(BaseHTTPMiddleware):
    """
    FastAPI middleware that adds:
    - Correlation ID to each request
    - Request timing
    - Structured logging
    - Metrics recording
    """
    async def dispatch(self, request: Request, call_next: Callable) -> Response:
        # Generate new correlation ID
        correlation_id = str(uuid.uuid4())
        correlation_id_var.set(correlation_id)
        request_start_var.set(time.time())

        # Add logger with correlation context
        logger = get_logger("api.request")

        try:
            # Log request
            logger.info(
                "Request started",
                extra_fields={
                    "method": request.method,
                    "url": str(request.url),
                    "client": request.client.host if request.client else None,
                    "user_agent": request.headers.get("user-agent", ""),
                }
            )

            response = await call_next(request)

            # Calculate duration
            duration = time.time() - request_start_var.get()

            # Record metrics
            metrics.record_request(response.status_code, duration)

            # Add correlation ID header
            response.headers["X-Correlation-ID"] = correlation_id

            # Log response
            logger.info(
                "Request completed",
                extra_fields={
                    "status_code": response.status_code,
                    "duration_ms": duration * 1000,
                }
            )

            return response

        except Exception as exc:
            duration = time.time() - request_start_var.get()
            logger.error(
                "Request failed",
                extra_fields={
                    "error": str(exc),
                    "duration_ms": duration * 1000,
                },
                exc_info=True
            )
            metrics.error_count += 1
            raise

def get_correlation_id() -> str:
    """Get current correlation ID from context"""
    return correlation_id_var.get()

def get_request_duration() -> float:
    """Get current request duration in seconds"""
    return time.time() - request_start_var.get()
