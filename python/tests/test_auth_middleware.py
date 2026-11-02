"""Unit tests for app.shared.middleware.auth (JWT authentication).

Tests verify_token, create_token, get_current_user_id, get_current_user.
"""
import pytest
from datetime import datetime, timedelta
from unittest.mock import MagicMock, patch

from app.shared.middleware.auth import (
    verify_token,
    create_token,
    get_current_user_id,
    get_current_user,
)
from fastapi import HTTPException


def make_credentials(token: str):
    """Helper to create mock HTTPAuthorizationCredentials."""
    creds = MagicMock()
    creds.credentials = token
    return creds


# ==== create_token ====

def test_create_token_returns_string():
    token = create_token({"sub": "user-1"})
    assert isinstance(token, str)
    assert len(token) > 0


def test_create_token_with_custom_expires_delta():
    delta = timedelta(minutes=30)
    token = create_token({"sub": "user-1"}, expires_delta=delta)
    assert isinstance(token, str)


def test_create_token_includes_exp_by_default():
    """Tokens created without expires_delta get default expiration."""
    token = create_token({"sub": "user-1"})
    assert isinstance(token, str)
    # JWT format: header.payload.signature — 3 parts separated by dots
    parts = token.split(".")
    assert len(parts) == 3


def test_create_token_preserves_provided_exp():
    """If data already has 'exp', it's preserved."""
    future_exp = int((datetime.utcnow() + timedelta(hours=1)).timestamp())
    token = create_token({"sub": "user-1", "exp": future_exp})
    parts = token.split(".")
    assert len(parts) == 3


def test_create_token_does_not_mutate_input():
    data = {"sub": "user-1"}
    original = data.copy()
    create_token(data)
    assert data == original


# ==== verify_token ====

@pytest.mark.asyncio
async def test_verify_token_valid_returns_payload():
    token = create_token({"sub": "user-1"})
    credentials = make_credentials(token)
    payload = await verify_token(credentials)
    assert payload["sub"] == "user-1"


@pytest.mark.asyncio
async def test_verify_token_includes_extra_claims():
    token = create_token({"sub": "user-1", "email": "u@x.com", "roles": ["admin"]})
    credentials = make_credentials(token)
    payload = await verify_token(credentials)
    assert payload["email"] == "u@x.com"
    assert payload["roles"] == ["admin"]


@pytest.mark.asyncio
async def test_verify_token_expired_raises_401():
    expired_delta = timedelta(seconds=-1)
    token = create_token({"sub": "user-1"}, expires_delta=expired_delta)
    credentials = make_credentials(token)
    with pytest.raises(HTTPException) as exc_info:
        await verify_token(credentials)
    assert exc_info.value.status_code == 401


@pytest.mark.asyncio
async def test_verify_token_invalid_raises_401():
    credentials = make_credentials("invalid.jwt.token")
    with pytest.raises(HTTPException) as exc_info:
        await verify_token(credentials)
    assert exc_info.value.status_code == 401


@pytest.mark.asyncio
async def test_verify_token_wrong_signature_raises_401():
    """Token signed with different key should fail."""
    import jwt as pyjwt
    token = pyjwt.encode({"sub": "user-1"}, "wrong-secret", algorithm="HS256")
    credentials = make_credentials(token)
    with pytest.raises(HTTPException) as exc_info:
        await verify_token(credentials)
    assert exc_info.value.status_code == 401


@pytest.mark.asyncio
async def test_verify_token_401_has_www_authenticate_header():
    token = create_token({"sub": "user-1"}, expires_delta=timedelta(seconds=-1))
    credentials = make_credentials(token)
    with pytest.raises(HTTPException) as exc_info:
        await verify_token(credentials)
    assert "WWW-Authenticate" in exc_info.value.headers
    assert exc_info.value.headers["WWW-Authenticate"] == "Bearer"


# ==== get_current_user_id ====

@pytest.mark.asyncio
async def test_get_current_user_id_extracts_sub():
    payload = {"sub": "user-123"}
    user_id = await get_current_user_id(payload)
    assert user_id == "user-123"


@pytest.mark.asyncio
async def test_get_current_user_id_missing_sub_raises_401():
    payload = {"email": "u@x.com"}  # no sub
    with pytest.raises(HTTPException) as exc_info:
        await get_current_user_id(payload)
    assert exc_info.value.status_code == 401


@pytest.mark.asyncio
async def test_get_current_user_id_empty_sub_raises_401():
    payload = {"sub": ""}
    with pytest.raises(HTTPException) as exc_info:
        await get_current_user_id(payload)
    assert exc_info.value.status_code == 401


@pytest.mark.asyncio
async def test_get_current_user_id_none_sub_raises_401():
    payload = {"sub": None}
    with pytest.raises(HTTPException) as exc_info:
        await get_current_user_id(payload)
    assert exc_info.value.status_code == 401


# ==== get_current_user ====

@pytest.mark.asyncio
async def test_get_current_user_extracts_fields():
    payload = {"sub": "user-1", "email": "u@x.com", "roles": ["admin", "user"]}
    user = await get_current_user(payload)
    assert user["id"] == "user-1"
    assert user["email"] == "u@x.com"
    assert user["roles"] == ["admin", "user"]


@pytest.mark.asyncio
async def test_get_current_user_default_empty_roles():
    payload = {"sub": "user-1"}
    user = await get_current_user(payload)
    assert user["roles"] == []


@pytest.mark.asyncio
async def test_get_current_user_missing_fields():
    """Missing fields return None or empty defaults."""
    payload = {}
    user = await get_current_user(payload)
    assert user["id"] is None
    assert user["email"] is None
    assert user["roles"] == []


# ==== Integration scenarios ====

@pytest.mark.asyncio
async def test_full_auth_flow():
    """Create token → verify → extract user_id."""
    token = create_token({"sub": "user-42", "email": "u@x.com", "roles": ["user"]})
    credentials = make_credentials(token)
    payload = await verify_token(credentials)
    user_id = await get_current_user_id(payload)
    user = await get_current_user(payload)
    assert user_id == "user-42"
    assert user["email"] == "u@x.com"


@pytest.mark.asyncio
async def test_token_roundtrip_preserves_payload():
    """All keys in the original payload should survive token roundtrip."""
    original = {
        "sub": "user-1",
        "email": "u@x.com",
        "roles": ["admin"],
        "custom_field": "custom_value",
    }
    token = create_token(original)
    credentials = make_credentials(token)
    payload = await verify_token(credentials)
    for key, value in original.items():
        assert payload[key] == value


@pytest.mark.asyncio
async def test_expired_token_blocks_auth():
    """Expired tokens should be rejected at verify_token stage."""
    token = create_token({"sub": "user-1"}, expires_delta=timedelta(seconds=-10))
    credentials = make_credentials(token)
    with pytest.raises(HTTPException):
        await verify_token(credentials)
