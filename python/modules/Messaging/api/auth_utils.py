# Auth utilities for microservices
from fastapi import HTTPException, Request, Depends
from typing import Dict

# Mock authentication - replace with real JWT/OAuth in production
async def get_current_user(request: Request) -> Dict:
    """Extract user from Authorization header"""
    authorization = request.headers.get("Authorization")
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Authentication required")
    token = authorization.split(" ")[1]
    # In production: validate JWT token with your auth service
    # For now, decode mock token or extract user_id from token payload
    return {
        "user_id": extract_user_id_from_token(token),
        "token": token,
        "role": extract_role_from_token(token)  # patient, doctor, admin, etc.
    }

def extract_user_id_from_token(token: str) -> str:
    """Mock: extract user_id from token - replace with real JWT decode"""
    # In production: decode JWT and get 'sub' or 'user_id' claim
    return "user_from_token"

def extract_role_from_token(token: str) -> str:
    """Mock: extract role from token - replace with real JWT decode"""
    # In production: decode JWT and get 'role' claim
    return "user"

def require_role(user: Dict, allowed_roles: list):
    """Check if user has required role"""
    if user.get("role") not in allowed_roles:
        raise HTTPException(status_code=403, detail=f"Access denied. Required roles: {allowed_roles}")

def authorize_resource_access(user: Dict, resource_owner_id: str, resource_type: str = "resource"):
    """Check if user can access/modify resource"""
    if user.get("user_id") != resource_owner_id:
        raise HTTPException(status_code=403, detail=f"Not authorized to access this {resource_type}")
