import pytest
from datetime import datetime
from modules.DeliveryService.delivery_manager import DeliveryManager, DeliveryNotFoundError, InvalidStatusError

@pytest.fixture
def manager():
    return DeliveryManager()

def test_init(manager):
    assert manager is not None
    assert manager.get_delivery_count() == 0

def test_create_delivery(manager):
    delivery = manager.create_delivery(12345)
    
    assert delivery is not None
    assert delivery['order_id'] == 12345
    assert delivery['status'] == 'created'

def test_create_delivery_invalid_order_id(manager):
    with pytest.raises(ValueError):
        manager.create_delivery(-1)
    
    with pytest.raises(TypeError):
        manager.create_delivery("not_an_int")

def test_create_delivery_twice_same_order(manager):
    delivery1 = manager.create_delivery(12345)
    delivery2 = manager.create_delivery(12345)
    
    assert delivery1['id'] == delivery2['id']

def test_track_delivery(manager):
    delivery = manager.create_delivery(12345)
    tracked = manager.track_delivery(delivery['id'])
    
    assert tracked is not None
    assert tracked['id'] == delivery['id']

def test_track_delivery_not_found(manager):
    result = manager.track_delivery("nonexistent")
    assert result is None

def test_track_delivery_invalid_id(manager):
    with pytest.raises(ValueError):
        manager.track_delivery("")
    
    with pytest.raises(TypeError):
        manager.track_delivery(123)

def test_update_status(manager):
    delivery = manager.create_delivery(12345)
    updated = manager.update_status(delivery['id'], 'assigned')
    
    assert updated['status'] == 'assigned'

def test_update_status_invalid(manager):
    delivery = manager.create_delivery(12345)
    
    with pytest.raises(InvalidStatusError):
        manager.update_status(delivery['id'], 'invalid_status')

def test_update_status_not_found(manager):
    with pytest.raises(DeliveryNotFoundError):
        manager.update_status("nonexistent", 'assigned')

def test_assign_driver(manager):
    delivery = manager.create_delivery(12345)
    updated = manager.assign_driver(delivery['id'], 999)
    
    assert updated['driver_id'] == 999
    assert updated['status'] == 'assigned'

def test_assign_driver_invalid(manager):
    delivery = manager.create_delivery(12345)
    
    with pytest.raises(ValueError):
        manager.assign_driver(delivery['id'], -1)
    
    with pytest.raises(DeliveryNotFoundError):
        manager.assign_driver("nonexistent", 999)

def test_complete_delivery(manager):
    delivery = manager.create_delivery(12345)
    manager.assign_driver(delivery['id'], 999)
    # Must be in_transit before delivery can be completed
    manager.update_status(delivery['id'], 'in_transit')
    completed = manager.complete_delivery(delivery['id'])
    
    assert completed['status'] == 'delivered'

def test_cancel_delivery(manager):
    delivery = manager.create_delivery(12345)
    cancelled = manager.cancel_delivery(delivery['id'])
    
    assert cancelled['status'] == 'cancelled'

def test_get_status_history(manager):
    delivery = manager.create_delivery(12345)
    manager.update_status(delivery['id'], 'assigned')
    
    history = manager.get_status_history(delivery['id'])
    assert len(history) >= 1

def test_get_status_history_not_found(manager):
    with pytest.raises(DeliveryNotFoundError):
        manager.get_status_history("nonexistent")

def test_get_delivery_count(manager):
    manager.create_delivery(12345)
    manager.create_delivery(67890)
    
    assert manager.get_delivery_count() == 2

def test_get_active_deliveries(manager):
    manager.create_delivery(12345)
    delivery2 = manager.create_delivery(67890)
    # Complete delivery2 via proper workflow: assign, move to in_transit, then complete
    manager.assign_driver(delivery2['id'], 999)
    manager.update_status(delivery2['id'], 'in_transit')
    manager.complete_delivery(delivery2['id'])
    
    active = manager.get_active_deliveries()
    assert len(active) == 1

def test_get_delivery_history(manager):
    manager.create_delivery(12345)
    
    history = manager.get_delivery_history(1)
    assert len(history) == 1

def test_get_delivery_history_invalid_user(manager):
    with pytest.raises(ValueError):
        manager.get_delivery_history(0)
    
    with pytest.raises(ValueError):
        manager.get_delivery_history(-1)