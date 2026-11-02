"""Unit tests for app.authz (IDOR prevention module).

Tests ResourceOwnership registry, ownership verification, decorators, and admin checks.
"""
import pytest
from unittest.mock import MagicMock
from fastapi import HTTPException

from app.authz import (
    ResourceOwnership,
    require_resource_owner,
    require_same_user_id,
    assert_owner,
    assert_admin,
)


# ==== Fixtures ====

@pytest.fixture(autouse=True)
def clear_registry():
    """Clear ResourceOwnership registry between tests."""
    ResourceOwnership.REGISTRY = {}
    yield
    ResourceOwnership.REGISTRY = {}


@pytest.fixture
def mock_post():
    """Mock resource object with user_id attribute."""
    post = MagicMock()
    post.id = "post-1"
    post.user_id = "owner-user"
    post.title = "Test Post"
    return post


# ==== ResourceOwnership.register decorator ====

def test_register_adds_to_registry(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post if post_id == mock_post.id else None

    assert "post" in ResourceOwnership.REGISTRY
    rule = ResourceOwnership.REGISTRY["post"]
    assert rule["owner_field"] == "user_id"
    assert rule["admin_bypass"] is True  # default
    assert rule["fetch_func"] is get_post


def test_register_with_admin_bypass_false(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id", admin_bypass=False)
    def get_post(post_id: str):
        return mock_post

    assert ResourceOwnership.REGISTRY["post"]["admin_bypass"] is False


def test_register_overwrites_previous(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post_v1(post_id: str):
        return mock_post

    @ResourceOwnership.register("post", owner_field="owner_id")
    def get_post_v2(post_id: str):
        return mock_post

    # Last registration wins
    assert ResourceOwnership.REGISTRY["post"]["owner_field"] == "owner_id"
    assert ResourceOwnership.REGISTRY["post"]["fetch_func"] is get_post_v2


# ==== ResourceOwnership.get_owner_id (success cases) ====

def test_get_owner_id_returns_true_for_owner(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    result = ResourceOwnership.get_owner_id("post", "post-1", "owner-user")
    assert result is True


def test_get_owner_id_returns_true_for_admin(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    result = ResourceOwnership.get_owner_id("post", "post-1", "other-user", user_role="admin")
    assert result is True


def test_get_owner_id_returns_true_for_admin_uppercase(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    result = ResourceOwnership.get_owner_id("post", "post-1", "other-user", user_role="ADMIN")
    assert result is True


def test_get_owner_id_returns_true_for_admin_role_prefix(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    result = ResourceOwnership.get_owner_id("post", "post-1", "other-user", user_role="ROLE_ADMIN")
    assert result is True


# ==== ResourceOwnership.get_owner_id (failure cases) ====

def test_get_owner_id_unregistered_resource_type_raises_500():
    with pytest.raises(HTTPException) as exc_info:
        ResourceOwnership.get_owner_id("unknown", "id-1", "user-1")
    assert exc_info.value.status_code == 500


def test_get_owner_id_not_owner_raises_404(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    with pytest.raises(HTTPException) as exc_info:
        ResourceOwnership.get_owner_id("post", "post-1", "different-user")
    assert exc_info.value.status_code == 404


def test_get_owner_id_resource_not_found_raises_404(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return None

    with pytest.raises(HTTPException) as exc_info:
        ResourceOwnership.get_owner_id("post", "missing-id", "user-1")
    assert exc_info.value.status_code == 404


def test_get_owner_id_fetch_exception_raises_500():
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        raise RuntimeError("DB down")

    with pytest.raises(HTTPException) as exc_info:
        ResourceOwnership.get_owner_id("post", "post-1", "user-1")
    assert exc_info.value.status_code == 500


def test_get_owner_id_missing_owner_field_raises_500():
    """Resource that doesn't have the configured owner field raises 500."""
    resource = MagicMock(spec=["id", "title"])  # No user_id
    resource.id = "x"
    resource.title = "Y"

    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return resource

    with pytest.raises(HTTPException) as exc_info:
        ResourceOwnership.get_owner_id("post", "x", "u1")
    assert exc_info.value.status_code == 500


# ==== Admin bypass disabled ====

def test_get_owner_id_admin_bypass_disabled_rejects_admin(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id", admin_bypass=False)
    def get_post(post_id: str):
        return mock_post

    with pytest.raises(HTTPException) as exc_info:
        ResourceOwnership.get_owner_id("post", "post-1", "different-user", user_role="admin")
    # Admin bypass disabled — should reject even admin user
    assert exc_info.value.status_code == 404


def test_get_owner_id_non_admin_role_no_bypass(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    with pytest.raises(HTTPException) as exc_info:
        ResourceOwnership.get_owner_id("post", "post-1", "different-user", user_role="user")
    assert exc_info.value.status_code == 404


# ==== require_resource_owner decorator ====

def test_require_resource_owner_calls_func_when_owner(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    @require_resource_owner("post", "post_id", "current_user_id")
    async def get_post_endpoint(post_id: str, current_user_id: str):
        return {"id": post_id, "user_id": current_user_id}

    # Use asyncio to call the async wrapper
    import asyncio
    result = asyncio.run(get_post_endpoint(post_id="post-1", current_user_id="owner-user"))
    assert result == {"id": "post-1", "user_id": "owner-user"}


def test_require_resource_owner_missing_resource_id_raises_400():
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return None

    @require_resource_owner("post", "post_id", "current_user_id")
    async def get_post_endpoint(post_id: str, current_user_id: str):
        return None

    import asyncio
    with pytest.raises(HTTPException) as exc_info:
        asyncio.run(get_post_endpoint(current_user_id="u1"))  # post_id missing
    assert exc_info.value.status_code == 400


def test_require_resource_owner_missing_current_user_id_raises_401():
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return None

    @require_resource_owner("post", "post_id", "current_user_id")
    async def get_post_endpoint(post_id: str, current_user_id: str):
        return None

    import asyncio
    with pytest.raises(HTTPException) as exc_info:
        asyncio.run(get_post_endpoint(post_id="x"))  # current_user_id missing
    assert exc_info.value.status_code == 401


def test_require_resource_owner_not_owner_blocks(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    @require_resource_owner("post", "post_id", "current_user_id")
    async def get_post_endpoint(post_id: str, current_user_id: str):
        return {"called": True}

    import asyncio
    with pytest.raises(HTTPException) as exc_info:
        asyncio.run(get_post_endpoint(post_id="post-1", current_user_id="attacker"))
    assert exc_info.value.status_code == 404
    # The inner function should NOT have been called


def test_require_resource_owner_admin_can_access(mock_post):
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    @require_resource_owner("post", "post_id", "current_user_id")
    async def get_post_endpoint(post_id: str, current_user_id: str, user_role: str = None):
        return {"role": user_role}

    import asyncio
    result = asyncio.run(get_post_endpoint(post_id="post-1", current_user_id="admin", user_role="admin"))
    assert result["role"] == "admin"


# ==== require_same_user_id decorator ====

def test_require_same_user_id_allows_match():
    @require_same_user_id("user_id", "current_user_id")
    async def get_cart(user_id: str, current_user_id: str):
        return {"user_id": user_id}

    import asyncio
    result = asyncio.run(get_cart(user_id="u1", current_user_id="u1"))
    assert result == {"user_id": "u1"}


def test_require_same_user_id_blocks_mismatch():
    @require_same_user_id("user_id", "current_user_id")
    async def get_cart(user_id: str, current_user_id: str):
        return {"user_id": user_id}

    import asyncio
    with pytest.raises(HTTPException) as exc_info:
        asyncio.run(get_cart(user_id="u1", current_user_id="u2"))
    assert exc_info.value.status_code == 403


def test_require_same_user_id_missing_user_id_raises_400():
    @require_same_user_id("user_id", "current_user_id")
    async def get_cart(user_id: str, current_user_id: str):
        return None

    import asyncio
    with pytest.raises(HTTPException) as exc_info:
        asyncio.run(get_cart(current_user_id="u1"))  # user_id missing
    assert exc_info.value.status_code == 400


def test_require_same_user_id_missing_current_user_id_raises_401():
    @require_same_user_id("user_id", "current_user_id")
    async def get_cart(user_id: str, current_user_id: str):
        return None

    import asyncio
    with pytest.raises(HTTPException) as exc_info:
        asyncio.run(get_cart(user_id="u1"))  # current_user_id missing
    assert exc_info.value.status_code == 401


# ==== assert_owner helper ====

def test_assert_owner_passes_for_owner():
    resource = MagicMock()
    resource.user_id = "u1"
    assert_owner(resource, "user_id", "u1")  # no raise


def test_assert_owner_raises_403_for_non_owner():
    resource = MagicMock()
    resource.user_id = "u1"
    with pytest.raises(HTTPException) as exc_info:
        assert_owner(resource, "user_id", "u2")
    assert exc_info.value.status_code == 403


def test_assert_owner_raises_500_for_missing_field():
    resource = MagicMock(spec=["id", "title"])
    with pytest.raises(HTTPException) as exc_info:
        assert_owner(resource, "user_id", "u1")
    assert exc_info.value.status_code == 500


def test_assert_owner_custom_resource_name():
    resource = MagicMock()
    resource.user_id = "u1"
    with pytest.raises(HTTPException) as exc_info:
        assert_owner(resource, "user_id", "u2", resource_name="cart")
    assert "cart" in str(exc_info.value.detail)


# ==== assert_admin helper ====

def test_assert_admin_passes_for_admin():
    assert_admin("admin")  # no raise


def test_assert_admin_passes_for_ROLE_ADMIN():
    assert_admin("ROLE_ADMIN")


def test_assert_admin_passes_for_uppercase_ADMIN():
    assert_admin("ADMIN")


def test_assert_admin_raises_403_for_user():
    with pytest.raises(HTTPException) as exc_info:
        assert_admin("user")
    assert exc_info.value.status_code == 403


def test_assert_admin_raises_403_for_none():
    with pytest.raises(HTTPException) as exc_info:
        assert_admin(None)
    assert exc_info.value.status_code == 403


def test_assert_admin_raises_403_for_empty_string():
    with pytest.raises(HTTPException) as exc_info:
        assert_admin("")
    assert exc_info.value.status_code == 403


# ==== Integration scenarios ====

def test_full_ownership_workflow(mock_post):
    """Register resource, check owner, check admin, check non-owner."""
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    # Owner can access
    assert ResourceOwnership.get_owner_id("post", "post-1", "owner-user") is True
    # Admin can access (different user but admin role)
    assert ResourceOwnership.get_owner_id("post", "post-1", "other-user", user_role="admin") is True
    # Non-owner non-admin is blocked
    with pytest.raises(HTTPException):
        ResourceOwnership.get_owner_id("post", "post-1", "attacker")


def test_owner_field_variants():
    """Different resources use different owner_field names."""
    @ResourceOwnership.register("cart", owner_field="customer_id")
    def get_cart(cart_id: str):
        cart = MagicMock()
        cart.customer_id = "cust-1"
        return cart

    assert ResourceOwnership.get_owner_id("cart", "c1", "cust-1") is True
    with pytest.raises(HTTPException):
        ResourceOwnership.get_owner_id("cart", "c1", "other-cust")


def test_multiple_resources_in_registry(mock_post):
    """Multiple resource types can be registered independently."""
    @ResourceOwnership.register("post", owner_field="user_id")
    def get_post(post_id: str):
        return mock_post

    other = MagicMock()
    other.author_id = "auth-1"

    @ResourceOwnership.register("comment", owner_field="author_id")
    def get_comment(comment_id: str):
        return other

    assert len(ResourceOwnership.REGISTRY) == 2
    assert "post" in ResourceOwnership.REGISTRY
    assert "comment" in ResourceOwnership.REGISTRY
