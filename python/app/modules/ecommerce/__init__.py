from typing import List, Dict, Any
from .service import EcommerceService
from .models import Product

ecommerce_service = EcommerceService()


def get_products(filters: Dict[str, Any]) -> List[Product]:
    return ecommerce_service.get_products(filters)


def get_product(product_id: str) -> Product:
    return ecommerce_service.get_product(product_id)


def get_recommendations(user_id: str, limit: int = 10) -> List[Product]:
    return ecommerce_service.get_recommendations(user_id, limit)
