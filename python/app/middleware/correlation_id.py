import uuid
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request
from app.shared.context import request_id_var, get_request_id

class CorrelationIDMiddleware(BaseHTTPMiddleware):
    """Middleware to ensure every request has a correlation ID.

    - If client provides X-Request-ID, reuse it.
    - Otherwise generate a new UUID.
    - Set response header X-Request-ID.
    - Store ID in contextvar for logging.
    """
    async def dispatch(self, request: Request, call_next):
        request_id = request.headers.get('x-request-id', str(uuid.uuid4()))
        # Set context var for this request
        token = request_id_var.set(request_id)
        try:
            response = await call_next(request)
            response.headers['x-request-id'] = request_id
            return response
        finally:
            # Reset the context var to previous state
            request_id_var.reset(token)
