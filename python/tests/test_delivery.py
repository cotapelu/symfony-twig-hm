import pytest
from modules.DeliveryService.delivery_manager import DeliveryManager

class TestDeliveryManager:
    def setup_method(self):
        self.manager = DeliveryManager()
        # Create a delivery to use in tests that require an existing delivery
        delivery = self.manager.create_delivery(order_id=123)
        self.delivery_id = delivery['id']

    def test_create_delivery(self):
        order_id = 123
        result = self.manager.create_delivery(order_id)
        assert result is not None

    def test_track_delivery(self):
        status = self.manager.track_delivery(self.delivery_id)
        assert status is not None

    def test_update_status(self):
        # Valid transition from 'created' to 'assigned'
        new_status = "assigned"
        result = self.manager.update_status(self.delivery_id, new_status)
        assert result is not None
        assert result["status"] == new_status

    def test_assign_driver(self):
        driver_id = 789
        result = self.manager.assign_driver(self.delivery_id, driver_id)
        assert result is not None
        assert result["status"] == "assigned"
        assert result["driver_id"] == driver_id

    def test_complete_delivery(self):
        # To complete, must be in_transit; assign driver then mark in_transit first
        self.manager.assign_driver(self.delivery_id, 789)
        self.manager.update_status(self.delivery_id, "in_transit")
        result = self.manager.complete_delivery(self.delivery_id)
        assert result is not None
        assert result["status"] == "delivered"

    def test_cancel_delivery(self):
        result = self.manager.cancel_delivery(self.delivery_id)
        assert result is not None
        assert result["status"] == "cancelled"

    def test_get_delivery_history(self):
        user_id = 123
        history = self.manager.get_delivery_history(user_id)
        assert isinstance(history, list)
