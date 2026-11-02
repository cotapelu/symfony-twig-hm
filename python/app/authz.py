"""
Authorization helper for Python monolith - prevents IDOR vulnerabilities

This module provides utilities to enforce resource ownership and access control
across all Python micro-modules.

IDOR (Insecure Direct Object Reference) occurs when an application uses user-supplied
input to access objects without proper ownership verification, allowing attackers to
access unauthorized data by manipulating IDs.

Standard pattern:
    @router.get("/resource/{resource_id}")
    async def get_resource(resource_id: str, current_user_id: str = Depends(get_current_user_id)):
        # MUST verify that current_user_id owns the resource OR has appropriate permissions
        authorize_resource_access(current_user_id, resource_id, "read")

Performance: Ownership checks should use cached/validated data where possible.
"""

from fastapi import HTTPException, Depends
from typing import Optional, Callable, Any
from functools import wraps
import inspect

# ==== OWNERSHIP VERIFICATION ====

class ResourceOwnership:
    """
    Registry of ownership rules for different resource types.

    Each resource type defines:
    - owner_field: the field name on the resource that contains the owner's user ID
    - service_method: how to fetch the resource (as (resource_type, resource_id) tuple)
    - admin_bypass: whether admins can bypass ownership checks (default True)
    """

    # Format: {resource_type: (owner_field, service_method, admin_bypass)}
    # service_method returns (resource_type, resource_id) that maps to a service call
    REGISTRY = {}

    @classmethod
    def register(cls, resource_type: str, owner_field: str = "user_id", admin_bypass: bool = True):
        """Decorator to register ownership rules"""
        def decorator(func):
            cls.REGISTRY[resource_type] = {
                "owner_field": owner_field,
                "admin_bypass": admin_bypass,
                "fetch_func": func
            }
            return func
        return decorator

    @classmethod
    def get_owner_id(cls, resource_type: str, resource_id: str, current_user_id: str, user_role: Optional[str] = None) -> bool:
        return cls._get_owner_id_impl(resource_type, resource_id, current_user_id, user_role)

    @classmethod
    def _get_owner_id_impl(cls, resource_type: str, resource_id: str, current_user_id: str, user_role: Optional[str] = None) -> bool:
        """
        Verify that current_user_id is the owner of the resource.

        Args:
            resource_type: Type of resource (e.g., 'cart', 'post', 'conversation')
            resource_id: ID of the resource to check
            current_user_id: ID of the authenticated user
            user_role: Optional role for admin bypass

        Returns:
            True if user is owner or has admin rights (if allowed)

        Raises:
            HTTPException 403 if user is not authorized
            HTTPException 404 if resource not found (optional based on policy)
        """
        if resource_type not in cls.REGISTRY:
            # No rule registered - deny by default to be safe
            raise HTTPException(
                status_code=500,
                detail=f"No ownership rule defined for resource type: {resource_type}"
            )

        rule = cls.REGISTRY[resource_type]
        fetch_func = rule["fetch_func"]
        owner_field = rule["owner_field"]
        admin_bypass = rule["admin_bypass"]

        # Check admin bypass
        if admin_bypass and user_role in ("admin", "ROLE_ADMIN", "ADMIN"):
            return True

        try:
            resource = fetch_func(resource_id)
            if not resource:
                raise HTTPException(status_code=404, detail=f"{resource_type} not found")

            owner_id = getattr(resource, owner_field, None)
            if owner_id is None:
                raise HTTPException(
                    status_code=500,
                    detail=f"Resource '{resource_id}' has no owner field '{owner_field}'"
                )

            if owner_id != current_user_id:
                raise HTTPException(
                    status_code=404,
                    detail=f"Not authorized: you do not own this {resource_type}"
                )

            return True
        except HTTPException:
            raise
        except Exception as e:
            raise HTTPException(status_code=500, detail=f"Authorization check failed: {str(e)}") from e


# ==== DECORATORS FOR COMMON PATTERNS ====

def require_resource_owner(resource_type: str, param_name: str = "resource_id", owner_param: str = "current_user_id"):
    return _require_resource_owner_impl(resource_type, param_name, owner_param)

def _require_resource_owner_impl(resource_type: str, param_name: str = "resource_id", owner_param: str = "current_user_id"):
    """
    Decorator that automatically checks ownership of a resource.

    Usage:
        @router.get("/posts/{post_id}")
        @require_resource_owner("post", "post_id")
        async def get_post(post_id: str, current_user_id: str = Depends(get_current_user_id)):
            # At this point, ownership is verified
            return await service.get_post(post_id)

    Args:
        resource_type: Type registered in ResourceOwnership.REGISTRY
        param_name: Name of the route parameter containing resource ID
        owner_param: Name of the parameter containing current user ID (from Depends)
    """
    def _decorator(func: Callable) -> Callable:
        sig = inspect.signature(func)

        @wraps(func)
        async def wrapper(*args, **kwargs):
            # Extract parameters
            if param_name not in kwargs:
                raise HTTPException(status_code=400, detail=f"Missing resource ID parameter: {param_name}")
            if owner_param not in kwargs:
                raise HTTPException(status_code=401, detail="Authentication required")

            resource_id = kwargs[param_name]
            current_user_id = kwargs[owner_param]
            # Get user role if available (optional)
            user_role = kwargs.get("user_role")

            # Perform ownership check
            ResourceOwnership.get_owner_id(resource_type, resource_id, current_user_id, user_role)

            return await func(*args, **kwargs)

        return wrapper
    return _decorator


def require_same_user_id(user_id_param: str = "user_id", current_user_id_param: str = "current_user_id"):
    return _require_same_user_id_impl(user_id_param, current_user_id_param)

def _require_same_user_id_impl(user_id_param: str = "user_id", current_user_id_param: str = "current_user_id"):
    """
    Decorator that ensures the requested user_id matches the authenticated user's ID.
    Simpler than full ownership verification when the resource is directly the user.

    Usage:
        @router.get("/cart/{user_id}")
        @require_same_user_id("user_id")
        async def get_cart(user_id: str, current_user_id: str = Depends(get_current_user_id)):
            # user_id == current_user_id is guaranteed
            ...
    """
    def _decorator(func: Callable) -> Callable:
        sig = inspect.signature(func)

        @wraps(func)
        async def wrapper(*args, **kwargs):
            if user_id_param not in kwargs:
                raise HTTPException(status_code=400, detail=f"Missing user ID parameter: {user_id_param}")
            if current_user_id_param not in kwargs:
                raise HTTPException(status_code=401, detail="Authentication required")

            requested_user_id = kwargs[user_id_param]
            current_user_id = kwargs[current_user_id_param]

            if requested_user_id != current_user_id:
                raise HTTPException(
                    status_code=403,
                    detail="Cannot access other users' resources"
                )

            return await func(*args, **kwargs)

        return wrapper
    return _decorator


# ==== CONVENIENCE FUNCTIONS ====

def assert_owner(resource_obj: Any, owner_field: str, user_id: str, resource_name: str = "resource"):
    return _assert_owner_impl(resource_obj, owner_field, user_id, resource_name)

def _assert_owner_impl(resource_obj: Any, owner_field: str, user_id: str, resource_name: str = "resource"):
    """
    Quick inline check when you already have the resource loaded.

    Args:
        resource_obj: The resource object
        owner_field: Field name containing owner's user ID
        user_id: Current authenticated user ID
        resource_name: Human-readable name for error messages

    Raises:
        HTTPException 403 if not owner
    """
    owner_id = getattr(resource_obj, owner_field, None)
    if owner_id is None:
        raise HTTPException(status_code=500, detail=f"{resource_name} has no owner field")
    if owner_id != user_id:
        raise HTTPException(
            status_code=403,
            detail=f"Not authorized: you do not own this {resource_name}"
        )


def assert_admin(user_role: Optional[str] = None):
    """Check if user has admin role"""
    if user_role not in ("admin", "ROLE_ADMIN", "ADMIN"):
        raise HTTPException(status_code=403, detail="Admin access required")


# ==== EXAMPLE REGISTRATIONS ====
# These would be placed in each module's code where services are defined:

"""
# In ecommerce/service.py or similar:
from app.authz import ResourceOwnership

@ResourceOwnership.register("cart", owner_field="user_id")
def get_cart_by_id(cart_id: str):
    return cart_repository.find_by_id(cart_id)

# In socialfeed/service.py:
@ResourceOwnership.register("post", owner_field="user_id")
def get_post_by_id(post_id: str):
    return socialfeed_repository.find_post(post_id)

@ResourceOwnership.register("user_follow_list", owner_field="user_id")  # for followers/following
def get_user_by_id(user_id: str):
    return user_repository.find_by_id(user_id)
"""
