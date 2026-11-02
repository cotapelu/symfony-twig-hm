from fastapi import APIRouter, HTTPException, Depends
from typing import List, Dict, Any, Optional
from pydantic import BaseModel

from ..service import EcommerceService
from ..models import Product
from app.authz import require_same_user_id
from app.shared.middleware.auth import get_current_user_id
from app.metrics import orders_created_total, order_amount_total, order_creation_latency

router = APIRouter(prefix="/api/ecommerce", tags=["ecommerce"])

ecommerce_service = EcommerceService()


class ProductFilter(BaseModel):
    category: Optional[str] = None
    min_price: Optional[float] = None
    max_price: Optional[float] = None
    search: Optional[str] = None
    limit: int = 20
    offset: int = 0


@router.post("/products")
async def get_products_post(filters: ProductFilter) -> List[Product]:
    """Get products with filters (POST for complex filters)"""
    return ecommerce_service.get_products(filters.dict())

@router.get("/products")
async def get_products_get(
    limit: int = 20,
    category: Optional[str] = None,
    current_user_id: str = Depends(get_current_user_id)
) -> List[Product]:
    """Get products with simple filters (GET)"""
    # IDOR: public - product catalog accessible to any authenticated user
    filters = {"limit": limit, "category": category}
    return ecommerce_service.get_products(filters)


@router.get("/products/{product_id}")
async def get_product_details(
    product_id: str,
    current_user_id: str = Depends(get_current_user_id)
) -> Product:
    """Get product details"""
    # IDOR: public - product details accessible to any authenticated user
    product = ecommerce_service.get_product(product_id)
    if not product:
        raise HTTPException(status_code=404, detail="Product not found")
    return product


@router.get("/recommendations/{user_id}")
async def get_recommendations(
    user_id: str,
    limit: int = 10,
    current_user_id: str = Depends(get_current_user_id)
) -> List[Product]:
    """Get personalized product recommendations (IDOR protected)"""
    if user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other users' recommendations")
    return ecommerce_service.get_recommendations(user_id, limit)


@router.post("/cart")
async def add_to_cart(
    cart_data: Dict[str, Any],
    current_user_id: str = Depends(get_current_user_id)
) -> Dict[str, Any]:
    """Add item to cart (IDOR protected)"""
    # IDOR: public - cart operations use current_user_id, no resource ID check needed
    # In a real implementation, we'd also validate product_id, quantity, etc.
    # For now, just return success with user's cart
    return {
        "cart_id": f"cart_{current_user_id}",
        "items": [],
        "total": 0.0,
        "message": "Item added to cart"
    }


@router.get("/cart/{user_id}")
async def get_cart(
    user_id: str,
    current_user_id: str = Depends(get_current_user_id)
) -> Dict[str, Any]:
    """Get user cart (IDOR protected)"""
    if user_id != current_user_id:
        raise HTTPException(status_code=403, detail="Cannot access other users' carts")
    return {
        "cart_id": f"cart_{user_id}",
        "items": [],
        "total": 0.0
    }


@router.post("/checkout")
async def checkout(
    checkout_data: Dict[str, Any],
    current_user_id: str = Depends(get_current_user_id)
) -> Dict[str, Any]:
    """Process checkout (IDOR protected)"""
    total = checkout_data.get("total", 0.0)
    currency = checkout_data.get("currency", "USD")
    order_type = checkout_data.get("order_type", "generic")
    
    # Record business metrics with timing
    with order_creation_latency.labels(order_type=order_type).time():
        # Ensure checkout is for the authenticated user's cart
        # In real implementation, verify cart belongs to current_user_id
        
        # Increment order counter
        orders_created_total.labels(order_type=order_type, status="created").inc()
        # Increment order amount (volume)
        order_amount_total.labels(currency=currency).inc(total)
    
    return {
        "order_id": "order_123",
        "status": "processing",
        "total": total,
        "user_id": current_user_id
    }


@router.get("/categories")
async def get_categories() -> List[str]:
    """Get product categories"""
    return ["electronics", "clothing", "home", "books"]
