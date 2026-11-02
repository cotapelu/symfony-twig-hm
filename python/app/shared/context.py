from contextvars import ContextVar

# Global ContextVar for request-scoped correlation ID
request_id_var: ContextVar[str] = ContextVar('request_id', default='-')

def get_request_id() -> str:
    return request_id_var.get()
