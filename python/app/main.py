from fastapi import FastAPI, Depends, HTTPException, Request, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from contextlib import asynccontextmanager
from starlette.middleware.base import BaseHTTPMiddleware
import logging
import jwt
import os
from datetime import datetime, timezone
from fastapi.responses import Response, JSONResponse
from prometheus_client import Counter, Histogram, generate_latest, REGISTRY

from app.shared.config.settings import settings
from app.shared.eventbus.eventbus import EventBus
from app.shared.utils.logger import setup_logger
from app.middleware.correlation_id import CorrelationIDMiddleware
from app.modules.ai.services.ai_service import AIService
from app.shared.database import get_db_manager, database_health_check

# Import business metrics to register them
from app import metrics  # noqa: F401

logger = setup_logger("python-monolith", "INFO", "json")

# Prometheus metrics
REQUEST_COUNT = Counter('http_requests_total', 'Total HTTP requests', ['method', 'path', 'status'])
REQUEST_LATENCY = Histogram('http_request_duration_seconds', 'HTTP request latency', ['method', 'path'])
ERROR_COUNT = Counter('http_errors_total', 'Total HTTP errors', ['method', 'path', 'status'])
event_bus = EventBus()
ai_service = AIService(event_bus)

security = HTTPBearer()

async def verify_token(credentials: HTTPAuthorizationCredentials = Depends(security)) -> dict:
    """Verify JWT token from Authorization header."""
    try:
        token = credentials.credentials

        secret_key = os.getenv("JWT_SECRET_KEY")
        if not secret_key:
            raise HTTPException(
                status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
                detail="JWT_SECRET_KEY environment variable not configured"
            )

        payload = jwt.decode(
            token,
            secret_key,
            algorithms=[os.getenv("JWT_ALGORITHM", "HS256")],
            options={"verify_exp": True}
        )

        exp = payload.get("exp")
        if exp and datetime.fromtimestamp(exp, tz=timezone.utc) < datetime.now(timezone.utc):
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="Token has expired"
            )

        return payload
        
    except jwt.ExpiredSignatureError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Token has expired"
        )
    except jwt.InvalidTokenError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Could not validate credentials"
        )


async def get_current_user_id(payload: dict = Depends(verify_token)) -> str:
    """Extract user ID from verified token."""
    user_id = payload.get("sub")
    if not user_id:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid token: missing user ID"
        )
    return user_id


@asynccontextmanager
async def lifespan(app: FastAPI):
    logger.info("Starting Python Modular Monolith...")
    # Initialize unified database
    try:
        from app.shared.database import initialize_database
        await initialize_database()
        logger.info("Database initialized")
    except Exception as e:
        logger.error(f"Failed to initialize database: {e}")
        raise
    
    yield
    
    # Cleanup
    from app.shared.database import close_database
    await close_database()
    logger.info("Database connections closed")
    logger.info("Shutting down Python Modular Monolith...")


app = FastAPI(
    title="Community Python Services",
    description="Modular monolith with AI, Media and Social modules",
    version="1.0.0",
    lifespan=lifespan
)

cors_origins = []
cors_methods = ["GET", "POST", "PUT", "DELETE", "OPTIONS"]
cors_headers = ["Content-Type", "Authorization", "X-Requested-With"]
cors_credentials = True

if hasattr(settings, 'cors') and settings.cors:
    cors_origins = getattr(settings.cors, 'allowed_origins', [])
    cors_methods = getattr(settings.cors, 'allowed_methods', cors_methods)
    cors_headers = getattr(settings.cors, 'allowed_headers', cors_headers)
    cors_credentials = getattr(settings.cors, 'allow_credentials', True)

app.add_middleware(
    CORSMiddleware,
    allow_origins=cors_origins,
    allow_credentials=cors_credentials,
    allow_methods=cors_methods,
    allow_headers=cors_headers,
)

# Add correlation ID to logs
app.add_middleware(CorrelationIDMiddleware)

@app.middleware("http")
async def tracing_middleware(request: Request, call_next):
    tp = request.headers.get("traceparent", "")
    request.state.traceparent = tp
    response = await call_next(request)
    return response

@app.middleware("http")
async def correlation_id_middleware(request: Request, call_next):
    req_id = request.headers.get("X-Request-ID")
    if not req_id:
        import uuid
        req_id = str(uuid.uuid4())
    response = await call_next(request)
    response.headers["X-Request-ID"] = req_id
    request.state.request_id = req_id
    return response

@app.middleware("http")
async def prometheus_middleware(request: Request, call_next):
    import time
    start_time = time.time()
    response = await call_next(request)
    latency = time.time() - start_time
    method = request.method
    path = request.url.path
    status = response.status_code

    REQUEST_COUNT.labels(method=method, path=path, status=str(status)).inc()
    REQUEST_LATENCY.labels(method=method, path=path).observe(latency)
    if status >= 400:
        ERROR_COUNT.labels(method=method, path=path, status=str(status)).inc()
    return response

# Include routers: SocialFeed (optional), Chatbot, AI (Telemedicine optional)
has_socialfeed = False
try:
    from app.modules.socialfeed.router import router as socialfeed_router
    has_socialfeed = True
except Exception as e:
    logger.warning(f"SocialFeed module disabled: {e}")

from app.modules.chatbot.router import router as chatbot_router
from app.modules.ai.api.routes import router as ai_router

if has_socialfeed:
    app.include_router(socialfeed_router)
app.include_router(chatbot_router)
app.include_router(ai_router)

# Ecommerce module
try:
    from app.modules.ecommerce.api.routes import router as ecommerce_router
    app.include_router(ecommerce_router)
    logger.info("Ecommerce module enabled")
except Exception as e:
    logger.warning(f"Ecommerce module disabled: {e}")

# Optional: Telemedicine (may require DB configuration)
try:
    from app.modules.telemedicine.api.routes import router as telemedicine_router
    app.include_router(telemedicine_router)
except Exception as e:
    logger.warning(f"Telemedicine module disabled: {e}")


@app.get("/")
async def root():
    return {
        "service": "Community Python Monolith",
        "status": "running",
        "version": "1.0.0",
        "modules": ["ai", "media", "socialfeed", "chatbot", "telemedicine"]
    }


@app.get("/health")
async def health():
    # Check database connectivity
    db_status = "unknown"
    try:
        health_results = await database_health_check()
        # health_results is dict of schema -> {status: bool, ...}
        all_healthy = all(h["status"] for h in health_results.values())
        db_status = "healthy" if all_healthy else "degraded"
    except Exception as e:
        logger.error(f"Health check database error: {e}")
        db_status = "unhealthy"

    return {
        "status": "healthy" if db_status == "healthy" else "degraded",
        "service": "python-monolith",
        "version": "1.0.0",
        "timestamp": datetime.utcnow().isoformat(),
        "checks": {
            "database": db_status,
            "event_bus": "ready",
            "ai_service": "ready"
        },
        "database_schemas": await database_health_check() if db_status != "unhealthy" else {}
    }


@app.get("/health/ready")
async def health_ready():
    db_ready = True
    try:
        health_results = await database_health_check()
        # health_results is dict of schema -> {status: bool, ...}
        db_ready = all(h["status"] for h in health_results.values())
    except Exception as e:
        logger.error(f"Readiness check database error: {e}")
        db_ready = False

    if not db_ready:
        return JSONResponse(
            status_code=503,
            content={
                "status": "not_ready",
                "service": "python-monolith",
                "timestamp": datetime.utcnow().isoformat(),
                "checks": {
                    "database": "unavailable",
                    "event_bus": "ready"
                },
                "database_schemas": await database_health_check() if False else {}
            }
        )

    return {
        "status": "ready",
        "service": "python-monolith",
        "timestamp": datetime.utcnow().isoformat(),
        "checks": {
            "database": "connected",
            "event_bus": "ready"
        },
        "database_schemas": await database_health_check()
    }


@app.get("/health/live")
async def health_live():
    return {
        "status": "alive",
        "service": "python-monolith",
        "timestamp": datetime.utcnow().isoformat()
    }

@app.get("/health/database")
async def health_database():
    """Detailed database health check per schema."""
    try:
        results = await database_health_check()
        # results: dict of schema -> {status: bool, ...}
        all_healthy = all(h["status"] for h in results.values())
        return {
            "status": "healthy" if all_healthy else "degraded",
            "timestamp": datetime.utcnow().isoformat(),
            "schemas": results
        }
    except Exception as e:
        logger.error(f"Database health check failed: {e}")
        return JSONResponse(
            status_code=503,
            content={
                "status": "unhealthy",
                "timestamp": datetime.utcnow().isoformat(),
                "error": str(e)
            }
        )

@app.get("/metrics")
async def metrics_endpoint():
    return Response(content=generate_latest(REGISTRY), media_type="text/plain; version=0.0.4")


@app.get("/api/protected")
async def protected_endpoint(user_id: str = Depends(get_current_user_id)):
    return {
        "message": "This is a protected endpoint",
        "user_id": user_id
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "app.main:app",
        host=settings.server.host if hasattr(settings, 'server') and settings.server else "127.0.0.1",
        port=settings.server.port if hasattr(settings, 'server') and settings.server else 8000,
        workers=settings.server.workers if hasattr(settings, 'server') and settings.server else 4,
        log_level="info"
    )
