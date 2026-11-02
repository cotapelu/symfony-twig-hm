from dataclasses import dataclass
from datetime import datetime
from typing import List
from app.appcore.events import Event

@dataclass
class OrderItem:
    product_id: str
    quantity: int
    unit_price: float
    total_price: float

@dataclass
class OrderPlaced(Event):
    order_id: str
    user_id: str
    items: List[OrderItem]
    total_amount: float
    currency: str = "USD"
    occurred_at: datetime = None

    def __post_init__(self):
        if self.occurred_at is None:
            self.occurred_at = datetime.utcnow()

        super().__init__(
            name="OrderPlaced",
            data={
                "order_id": self.order_id,
                "user_id": self.user_id,
                "items": [
                    {
                        "product_id": item.product_id,
                        "quantity": item.quantity,
                        "unit_price": item.unit_price,
                        "total_price": item.total_price
                    } for item in self.items
                ],
                "total_amount": self.total_amount,
                "currency": self.currency,
                "occurred_at": self.occurred_at.isoformat()
            }
        )
