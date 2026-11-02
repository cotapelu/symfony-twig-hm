package service

import (
	"os"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestDeliveryService_SetOnline(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	dp, err := svc.SetOnline("user_123", 10.7769, 106.6950)
	require.NoError(t, err)
	assert.NotEmpty(t, dp.ID)
	assert.True(t, dp.IsOnline)
	assert.Equal(t, "available", dp.Status)
}

func TestDeliveryService_SetOffline(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	_, err := svc.SetOnline("user_123", 10.7769, 106.6950)
	require.NoError(t, err)

	err = svc.SetOffline("user_123")
	require.NoError(t, err)

	// Check status
	nearby := svc.FindNearbyDeliveryPersons(10.7769, 106.6950, 5.0)
	assert.Len(t, nearby, 0)
}

func TestDeliveryService_UpdateLocation(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	_, err := svc.SetOnline("user_123", 10.7769, 106.6950)
	require.NoError(t, err)

	err = svc.UpdateLocation("user_123", 10.7800, 106.7000)
	require.NoError(t, err)
}

func TestDeliveryService_FindNearbyDeliveryPersons(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	// Add delivery person at specific location (Ho Chi Minh City)
	_, err := svc.SetOnline("user_123", 10.7769, 106.6950)
	require.NoError(t, err)

	// Find nearby - same location
	nearby := svc.FindNearbyDeliveryPersons(10.7769, 106.6950, 5.0)
	require.Len(t, nearby, 1)
	assert.Equal(t, "user_123", nearby[0].UserID)

	// Find far away - should not find
	nearby = svc.FindNearbyDeliveryPersons(10.0000, 106.0000, 5.0)
	assert.Len(t, nearby, 0)
}

func TestDeliveryService_AssignOrder(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	// Set delivery person online
	_, err := svc.SetOnline("user_123", 10.7769, 106.6950)
	require.NoError(t, err)

	// Assign order
	order, err := svc.AssignOrder("order_123", 10.7769, 106.6950, 10.7800, 106.7000, "Customer Address", "Restaurant Address")
	require.NoError(t, err)
	assert.NotEmpty(t, order.ID)
	assert.Equal(t, "assigned", order.Status)
	assert.Equal(t, "user_123", order.DeliveryPersonID)
}

func TestDeliveryService_AssignOrder_NoAvailable(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	// Try to assign order without any delivery person
	_, err := svc.AssignOrder("order_123", 10.7769, 106.6950, 10.7800, 106.7000, "Customer Address", "Restaurant Address")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "no delivery persons available")
}

func TestDeliveryService_StartDelivery(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	// Set delivery person online
	_, err := svc.SetOnline("user_123", 10.7769, 106.6950)
	require.NoError(t, err)

	// Assign order
	order, err := svc.AssignOrder("order_123", 10.7769, 106.6950, 10.7800, 106.7000, "Customer Address", "Restaurant Address")
	require.NoError(t, err)

	// Start delivery
	updated, err := svc.StartDelivery(order.ID)
	require.NoError(t, err)
	assert.Equal(t, "picked_up", updated.Status)
}

func TestDeliveryService_CompleteDelivery(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	// Set delivery person online
	_, err := svc.SetOnline("user_123", 10.7769, 106.6950)
	require.NoError(t, err)

	// Assign order
	order, err := svc.AssignOrder("order_123", 10.7769, 106.6950, 10.7800, 106.7000, "Customer Address", "Restaurant Address")
	require.NoError(t, err)

	// Complete delivery
	completed, err := svc.CompleteDelivery(order.ID)
	require.NoError(t, err)
	assert.Equal(t, "delivered", completed.Status)
	assert.NotNil(t, completed.DeliveredAt)
}

func TestDeliveryService_GetDeliveryOrder(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	// Set delivery person online
	_, err := svc.SetOnline("user_123", 10.7769, 106.6950)
	require.NoError(t, err)

	// Assign order
	order, err := svc.AssignOrder("order_123", 10.7769, 106.6950, 10.7800, 106.7000, "Customer Address", "Restaurant Address")
	require.NoError(t, err)

	// Get order
	found, err := svc.GetDeliveryOrder(order.ID)
	require.NoError(t, err)
	assert.Equal(t, order.ID, found.ID)
}

func TestDeliveryService_GetDeliveryOrder_NotFound(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	_, err := svc.GetDeliveryOrder("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "not found")
}

func TestDeliveryService_GetActiveDeliveries(t *testing.T) {
	tmpFile := t.TempDir() + "/test_delivery.json"
	defer os.Remove(tmpFile)

	svc := NewDeliveryService(tmpFile)

	// Set delivery person online
	_, err := svc.SetOnline("user_123", 10.7769, 106.6950)
	require.NoError(t, err)

	// Assign order 1
	order1, err := svc.AssignOrder("order_1", 10.7769, 106.6950, 10.7800, 106.7000, "Customer Address", "Restaurant Address")
	require.NoError(t, err)

	// Assign order 2
	_, err = svc.AssignOrder("order_2", 10.7769, 106.6950, 10.7800, 106.7000, "Customer Address", "Restaurant Address")
	require.NoError(t, err)

	// Complete order 1
	svc.CompleteDelivery(order1.ID)

	// Get active deliveries
	active := svc.GetActiveDeliveries("user_123")
	require.Len(t, active, 1)
}

func TestDeliveryService_CalculateDistance(t *testing.T) {
	// Distance from Ho Chi Minh City to Vung Tau (~62km)
	distance := calculateDistance(10.7769, 106.6950, 10.3800, 107.0900)
	// Should be approximately 62km
	assert.InDelta(t, 62.0, distance, 10.0)
}
