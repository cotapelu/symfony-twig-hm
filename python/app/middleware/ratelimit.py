"""Rate limiting middleware for FastAPI"""
from fastapi import Request, status
from fastapi.responses import JSONResponse
from starlette.middleware.base import BaseHTTPMiddleware
from collections import defaultdict
import time
from typing import Dict, Tuple

class RateLimiter:
    def __init__(self, max_requests: int = 100, window_seconds: int = 60):
        self.max_requests = max_requests
        self.window_seconds = window_seconds
        self.requests: Dict[str, list] = defaultdict(list)
    
    def is_allowed(self, client_ip: str) -> Tuple[bool, int]:
        now = time.time()
        window_start = now - self.window_seconds
        
        self.requests[client_ip] = [
            req_time for req_time in self.requests[client_ip]
            if req_time > window_start
        ]
        
        if len(self.requests[client_ip]) >= self.max_requests:
            return False, len(self.requests[client_ip])
        
        self.requests[client_ip].append(now)
        return True, len(self.requests[client_ip])

class RateLimitMiddleware(BaseHTTPMiddleware):
    def __init__(self, app, max_requests: int = 100, window_seconds: int = 60):
        super().__init__(app)
        self.limiter = RateLimiter(max_requests, window_seconds)
    
    async def dispatch(self, request: Request, call_next):
        client_ip = request.client.host
        allowed, count = self.limiter.is_allowed(client_ip)
        
        if not allowed:
            return JSONResponse(
                status_code=status.HTTP_429_TOO_MANY_REQUESTS,
                content={
                    "error": "rate_limit_exceeded",
                    "message": "Too many requests",
                    "retry_after": self.limiter.window_seconds
                }
            )
        
        response = await call_next(request)
        response.headers["X-RateLimit-Remaining"] = str(self.limiter.max_requests - count)
        return response
