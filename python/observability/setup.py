"""
Observability Setup Helper
Easily add observability to any FastAPI application
"""

from fastapi import FastAPI
from .middleware import ObservabilityMiddleware, setup_logging, get_logger
from .health import create_health_endpoints, health_checker
from .resilience import CircuitBreaker, Bulkhead, with_timeout, retry_with_backoff
from .metrics_exporter import format_prometheus_metrics

def setup_observability(
    app: FastAPI,
    enable_logging: bool = True,
    enable_metrics: bool = True,
    enable_health: bool = True,
    log_level: int = None
) -> None:
    """
    Configure observability stack for a FastAPI application

    Args:
        app: FastAPI application instance
        enable_logging: Setup JSON structured logging
        enable_metrics: Add request metrics collection
        enable_health: Add /health endpoints
        log_level: Logging level (logging.INFO, etc.)
    """
    if enable_logging:
        from logging import INFO, WARNING, DEBUG, ERROR
        level = log_level if log_level is not None else INFO
        setup_logging(level=level)

    if enable_metrics:
        # Add observability middleware (includes correlation ID, metrics, logging)
        app.add_middleware(ObservabilityMiddleware)

    if enable_health:
        # Register health check endpoints
        create_health_endpoints(app)

    # Register a sample logger for this app
    logger = get_logger(app.title)
    logger.info(
        "Observability setup complete",
        extra_fields={
            "component": "observability",
            "action": "setup",
            "app": app.title,
        }
    )

__all__ = [
    "setup_observability",
    "ObservabilityMiddleware",
    "setup_logging",
    "get_logger",
    "health_checker",
    "CircuitBreaker",
    "Bulkhead",
    "with_timeout",
    "retry_with_backoff",
    "format_prometheus_metrics",
]
