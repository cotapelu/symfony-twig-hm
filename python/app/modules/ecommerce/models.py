"""E-commerce Pydantic schemas and business models."""
from typing import Optional, List, Tuple
from datetime import datetime
from enum import Enum
from decimal import Decimal
from pydantic import BaseModel


class ProductCategory(str, Enum):
    FOOD = "food"
    DRINK = "drink"
    CLOTHES = "clothes"
    ELECTRONICS = "electronics"
    OTHER = "other"


class Product(BaseModel):
    id: str
    name: str
    category: ProductCategory
    price: Decimal
    currency: str = "USD"
    description: Optional[str] = None
    image_url: Optional[str] = None
    rating: Decimal = Decimal('0.0')
    review_count: int = 0
    created_at: datetime = datetime.now()


class ProductRecommendation(BaseModel):
    product_id: str
    score: Decimal
    reason: str


class UserPreference(BaseModel):
    user_id: str
    category_preferences: List[ProductCategory]
    price_range: Tuple[Decimal, Decimal] = (Decimal('0'), Decimal('100000'))
    favorite_brands: List[str] = []
    last_viewed_products: List[str] = []
