from fastapi import Security, HTTPException, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
import jwt
from datetime import datetime, timedelta
from typing import Optional

from app.shared.config.settings import settings

security = HTTPBearer()

async def verify_token(credentials: HTTPAuthorizationCredentials = Security(security)) -> dict:
    """
    Verify JWT token from Authorization header.
    
    Args:
        credentials: HTTPAuthorizationCredentials from Bearer token
        
    Returns:
        dict: Decoded token payload
        
    Raises:
        HTTPException: If token is invalid or expired
    """
    try:
        token = credentials.credentials
        payload = jwt.decode(
            token,
            settings.jwt.secret_key,
            algorithms=[settings.jwt.algorithm]
        )
        
        # Check expiration
        exp = payload.get("exp")
        if exp and datetime.fromtimestamp(exp) < datetime.utcnow():
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="Token has expired",
                headers={"WWW-Authenticate": "Bearer"},
            )
        
        return payload
        
    except jwt.ExpiredSignatureError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Token has expired",
            headers={"WWW-Authenticate": "Bearer"},
        )
    except jwt.InvalidTokenError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Could not validate credentials",
            headers={"WWW-Authenticate": "Bearer"},
        )


def create_token(data: dict, expires_delta: Optional[timedelta] = None) -> str:
    return _create_token_impl(data, expires_delta)

def _create_token_impl(data: dict, expires_delta: Optional[timedelta] = None) -> str:
    """
    Create JWT token from payload.
    
    Args:
        data: Payload data to encode
        expires_delta: Optional expiration time delta
        
    Returns:
        str: Encoded JWT token
    """
    to_encode = data.copy()
    
    if expires_delta:
        expire = datetime.utcnow() + expires_delta
        to_encode["exp"] = expire
    elif "exp" not in to_encode:
        expire = datetime.utcnow() + timedelta(minutes=settings.jwt.access_token_expire_minutes)
        to_encode["exp"] = expire
    encoded_jwt = jwt.encode(
        to_encode,
        settings.jwt.secret_key,
        algorithm=settings.jwt.algorithm
    )
    
    return encoded_jwt


async def get_current_user_id(payload: dict = Security(verify_token)) -> str:
    """
    Extract user ID from verified token payload.
    
    Args:
        payload: Decoded token payload from verify_token
        
    Returns:
        str: User ID from token
    """
    user_id = payload.get("sub")
    if not user_id:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid token: missing user ID",
        )
    return user_id


async def get_current_user(payload: dict = Security(verify_token)) -> dict:
    """
    Extract full user data from verified token payload.
    
    Args:
        payload: Decoded token payload from verify_token
        
    Returns:
        dict: User data from token
    """
    return {
        "id": payload.get("sub"),
        "email": payload.get("email"),
        "roles": payload.get("roles", []),
    }
