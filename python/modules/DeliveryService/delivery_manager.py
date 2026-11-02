"""Delivery Manager - Production-ready delivery management for orders and shipments"""
import uuid
import logging
import threading
from typing import Dict, List, Optional
from datetime import datetime
from datetime import datetime

logger = logging.getLogger(__name__)

class DeliveryNotFoundError(Exception):
    """Raised when delivery is not found"""
    pass

class InvalidStatusError(Exception):
    """Raised when status transition is invalid"""
    pass

class DeliveryManager:
    """Thread-safe delivery management for orders and shipments"""
    
    VALID_STATUSES = {'created', 'assigned', 'in_transit', 'delivered', 'cancelled'}
    VALID_TRANSITIONS = {
        'created': {'assigned', 'cancelled'},
        'assigned': {'in_transit', 'cancelled'},
        'in_transit': {'delivered', 'cancelled'},
        'delivered': set(),
        'cancelled': set(),
    }
    
    MAX_ORDER_ID = 10**12
    MAX_DRIVER_ID = 10**10
    MAX_HISTORY_SIZE = 1000

    def __init__(self):
        self._deliveries: Dict[str, Dict] = {}
        self._status_history: Dict[str, List[Dict]] = {}
        self._lock = threading.RLock()
        self._delivery_cache: Dict[int, str] = {}
    
    def _validate_order_id(self, order_id: int) -> None:
        if not isinstance(order_id, int):
            raise TypeError("order_id must be an integer")
        if order_id <= 0:
            raise ValueError("order_id must be positive")
        if order_id > self.MAX_ORDER_ID:
            raise ValueError(f"order_id exceeds maximum allowed ({self.MAX_ORDER_ID})")
    
    def _validate_delivery_id(self, delivery_id: str) -> None:
        if not isinstance(delivery_id, str):
            raise TypeError("delivery_id must be a string")
        if not delivery_id:
            raise ValueError("delivery_id cannot be empty")
    
    def _validate_driver_id(self, driver_id: int) -> None:
        if not isinstance(driver_id, int):
            raise TypeError("driver_id must be an integer")
        if driver_id <= 0:
            raise ValueError("driver_id must be positive")
        if driver_id > self.MAX_DRIVER_ID:
            raise ValueError(f"driver_id exceeds maximum allowed ({self.MAX_DRIVER_ID})")
    
    def _validate_status(self, status: str) -> None:
        if not isinstance(status, str):
            raise TypeError("status must be a string")
        if status not in self.VALID_STATUSES:
            raise InvalidStatusError(f"Invalid status: {status}. Valid statuses: {', '.join(self.VALID_STATUSES)}")
    
    def _validate_status_transition(self, current_status: str, new_status: str) -> None:
        allowed = self.VALID_TRANSITIONS.get(current_status, set())
        if new_status not in allowed:
            raise InvalidStatusError(f"Cannot transition from '{current_status}' to '{new_status}'. Allowed: {', '.join(allowed) if allowed else 'none'}")
    
    def create_delivery(self, order_id: int) -> Dict:
        return self._create_delivery_impl(order_id)

    def _create_delivery_impl(self, order_id: int) -> Dict:
        """Create a new delivery for an order"""
        self._validate_order_id(order_id)
        
        with self._lock:
            if order_id in self._delivery_cache:
                existing_id = self._delivery_cache[order_id]
                if existing_id in self._deliveries:
                    logger.warning(f"Delivery already exists for order {order_id}")
                    return self._deliveries[existing_id].copy()
            
            delivery_id = str(uuid.uuid4())
            now = datetime.now().isoformat()
            
            delivery = {
                'id': delivery_id,
                'order_id': order_id,
                'status': 'created',
                'driver_id': None,
                'created_at': now,
                'updated_at': now
            }
            
            self._deliveries[delivery_id] = delivery
            self._delivery_cache[order_id] = delivery_id
            self._status_history[delivery_id] = [{
                'status': 'created',
                'timestamp': now
            }]
            
            logger.info(f"Created delivery {delivery_id} for order {order_id}")
            return delivery.copy()

    def track_delivery(self, delivery_id: str) -> Optional[Dict]:
        """Track a delivery by ID"""
        self._validate_delivery_id(delivery_id)
        
        with self._lock:
            delivery = self._deliveries.get(delivery_id)
            if delivery is None:
                logger.warning(f"Delivery not found: {delivery_id}")
                return None
            
            logger.debug(f"Tracking delivery {delivery_id}")
            return delivery.copy()

    def update_status(self, delivery_id: str, new_status: str) -> Dict:
        return self._update_status_impl(delivery_id, new_status)

    def _update_status_impl(self, delivery_id: str, new_status: str) -> Dict:
        """Update delivery status"""
        self._validate_delivery_id(delivery_id)
        self._validate_status(new_status)
        
        with self._lock:
            if delivery_id not in self._deliveries:
                logger.error(f"Cannot update status: delivery not found {delivery_id}")
                raise DeliveryNotFoundError(f"Delivery not found: {delivery_id}")
            
            current = self._deliveries[delivery_id]
            current_status = current['status']
            
            self._validate_status_transition(current_status, new_status)
            
            now = datetime.now().isoformat()
            current['status'] = new_status
            current['updated_at'] = now
            
            history = self._status_history.get(delivery_id, [])
            history.append({
                'status': new_status,
                'timestamp': now
            })
            
            if len(history) > self.MAX_HISTORY_SIZE:
                history = history[-self.MAX_HISTORY_SIZE:]
                self._status_history[delivery_id] = history
            
            logger.info(f"Updated delivery {delivery_id} status: {current_status} -> {new_status}")
            return current.copy()

    def assign_driver(self, delivery_id: str, driver_id: int) -> Dict:
        return self._assign_driver_impl(delivery_id, driver_id)

    def _assign_driver_impl(self, delivery_id: str, driver_id: int) -> Dict:
        """Assign a driver to a delivery"""
        self._validate_delivery_id(delivery_id)
        self._validate_driver_id(driver_id)
        
        with self._lock:
            if delivery_id not in self._deliveries:
                logger.error(f"Cannot assign driver: delivery not found {delivery_id}")
                raise DeliveryNotFoundError(f"Delivery not found: {delivery_id}")
            
            current = self._deliveries[delivery_id]
            if current['status'] not in {'created', 'assigned'}:
                raise InvalidStatusError(f"Cannot assign driver to delivery in '{current['status']}' status")
            
            current['driver_id'] = driver_id
            current['status'] = 'assigned'
            current['updated_at'] = datetime.now().isoformat()
            
            history = self._status_history.get(delivery_id, [])
            history.append({
                'status': 'assigned',
                'timestamp': datetime.now().isoformat()
            })
            self._status_history[delivery_id] = history
            
            logger.info(f"Assigned driver {driver_id} to delivery {delivery_id}")
            return current.copy()

    def complete_delivery(self, delivery_id: str) -> Dict:
        """Mark a delivery as completed"""
        return self.update_status(delivery_id, 'delivered')

    def cancel_delivery(self, delivery_id: str) -> Dict:
        """Cancel a delivery"""
        return self.update_status(delivery_id, 'cancelled')

    def get_delivery_history(self, user_id: int) -> List[Dict]:
        """Get delivery history for a user"""
        if not isinstance(user_id, int) or user_id <= 0:
            raise ValueError("user_id must be a positive integer")
        
        with self._lock:
            result = [d.copy() for d in self._deliveries.values()]
            logger.debug(f"Retrieved {len(result)} deliveries for user {user_id}")
            return result

    def get_status_history(self, delivery_id: str) -> List[Dict]:
        """Get status history for a delivery"""
        self._validate_delivery_id(delivery_id)
        
        with self._lock:
            if delivery_id not in self._deliveries:
                raise DeliveryNotFoundError(f"Delivery not found: {delivery_id}")
            
            return self._status_history.get(delivery_id, []).copy()
    
    def get_delivery_count(self) -> int:
        """Get total number of deliveries"""
        with self._lock:
            return len(self._deliveries)
    
    def get_active_deliveries(self) -> List[Dict]:
        """Get all active (not delivered/cancelled) deliveries"""
        with self._lock:
            active = [d.copy() for d in self._deliveries.values() 
                     if d['status'] not in ('delivered', 'cancelled')]
            return active