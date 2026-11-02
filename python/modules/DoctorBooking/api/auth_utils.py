# Authentication and Authorization utilities
from fastapi import HTTPException, Request, Depends
from typing import Dict

async def get_current_user(request: Request) -> Dict:
    """Extract user from Authorization header (JWT/Bearer)"""
    authorization = request.headers.get("Authorization")
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Authentication required")
    token = authorization.split(" ")[1]
    # In production: validate JWT token with auth service
    return extract_user_from_token(token)

def extract_user_from_token(token: str) -> Dict:
    """Mock: decode token and extract user info"""
    # Production: use JWT decode to get user_id, role, etc.
    return {
        "user_id": "user_from_token",
        "role": "patient",  # or "doctor", "admin"
        "token": token
    }

def require_patient(user: Dict):
    """Require patient role"""
    if user.get("role") != "patient":
        raise HTTPException(status_code=403, detail="Patient access required")

def require_doctor(user: Dict):
    """Require doctor role"""
    if user.get("role") != "doctor":
        raise HTTPException(status_code=403, detail="Doctor access required")

def require_admin(user: Dict):
    """Require admin role"""
    if user.get("role") != "admin":
        raise HTTPException(status_code=403, detail="Admin access required")

def authorize_user_resource(user: Dict, resource_user_id: str):
    """Check if user can access their own resource"""
    if user.get("user_id") != resource_user_id:
        raise HTTPException(status_code=403, detail="Can only access own resources")
