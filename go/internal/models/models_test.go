package models

import (
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
)

// TestBankingAccountCreation tests creating a BankingAccount struct.
func TestBankingAccountCreation(t *testing.T) {
	acc := BankingAccount{
		ID:            "acc-1",
		UserID:        "user-1",
		AccountNumber: "1234567890",
		AccountType:   "CHECKING",
		Balance:       1000.50,
		Status:        "ACTIVE",
		Metadata:      `{"note":"test"}`,
		CreatedAt:     time.Now(),
		UpdatedAt:     time.Now(),
	}
	assert.Equal(t, "acc-1", acc.ID)
	assert.Equal(t, "CHECKING", acc.AccountType)
	assert.InEpsilon(t, 1000.5, acc.Balance, 0.001)
}

// TestCryptoWalletCreation tests creating a CryptoWallet struct.
func TestCryptoWalletCreation(t *testing.T) {
	wallet := CryptoWallet{
		ID:       "wallet-1",
		UserID:   "user-1",
		Currency: "BTC",
		Balance:  0.5,
		Status:   "ACTIVE",
		Metadata: `{"network":"mainnet"}`,
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}
	assert.Equal(t, "BTC", wallet.Currency)
	assert.False(t, wallet.Balance < 0)
}

// TestFraudEventCreation tests creating a FraudEvent struct.
func TestFraudEventCreation(t *testing.T) {
	event := FraudEvent{
		ID:        "event-1",
		UserID:    "user-1",
		EventType: "LOGIN_FAILED",
		Score:     85.5,
		Details:   `{"ip":"1.2.3.4"}`,
		Processed: false,
		Metadata:  "",
		CreatedAt: time.Now(),
	}
	assert.Equal(t, "LOGIN_FAILED", event.EventType)
	assert.True(t, event.Score > 80)
}

// TestLocationTrackingCreation tests creating a LocationTracking struct.
func TestLocationTrackingCreation(t *testing.T) {
	loc := LocationTracking{
		ID:         "loc-1",
		UserID:     "user-1",
		EntityType: "USER",
		EntityID:   "user-1",
		Latitude:   10.12345,
		Longitude:  -20.54321,
		Accuracy:   15.5,
		Metadata:   `{"source":"gps"}`,
		Timestamp:  time.Now(),
	}
	assert.InEpsilon(t, 10.12345, loc.Latitude, 0.001)
	assert.InEpsilon(t, -20.54321, loc.Longitude, 0.001)
}

// TestMarketplaceItemCreation tests creating a MarketplaceItem struct.
func TestMarketplaceItemCreation(t *testing.T) {
	item := MarketplaceItem{
		ID:       "item-1",
		SellerID: "user-2",
		Category: "Electronics",
		Title:    "Phone",
		Price:    299.99,
		Status:   "ACTIVE",
		Metadata: `{"condition":"new"}`,
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}
	assert.Equal(t, "Electronics", item.Category)
	assert.Positive(t, item.Price)
}

// TestMarketplaceOrderCreation tests creating a MarketplaceOrder struct.
func TestMarketplaceOrderCreation(t *testing.T) {
	order := MarketplaceOrder{
		ID:        "order-1",
		BuyerID:   "user-1",
		SellerID:  "user-2",
		ItemID:    "item-1",
		Status:    "PENDING",
		Metadata:  "",
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}
	assert.Equal(t, "PENDING", order.Status)
}

// TestLandRecordCreation tests creating a LandRecord struct.
func TestLandRecordCreation(t *testing.T) {
	record := LandRecord{
		ID:          "land-1",
		OwnerID:     "user-3",
		PlotNumber:  "PLOT-001",
		Area:        500.75,
		Address:     "123 Main St",
		Type:        "RESIDENTIAL",
		Status:      "OWNED",
		Metadata:    `{"deed":"12345"}`,
		CreatedAt:   time.Now(),
		UpdatedAt:   time.Now(),
	}
	assert.Equal(t, "RESIDENTIAL", record.Type)
	assert.True(t, record.Area > 0)
}

// TestLandPermitCreation tests creating a LandPermit struct.
func TestLandPermitCreation(t *testing.T) {
	permit := LandPermit{
		ID:            "permit-1",
		ApplicantID:   "user-3",
		LandRecordID:  "land-1",
		PermitType:    "BUILDING",
		Status:        "APPROVED",
		Data:          `{"size":"200sqm"}`,
		Metadata:      "",
		CreatedAt:     time.Now(),
		UpdatedAt:     time.Now(),
	}
	assert.Equal(t, "BUILDING", permit.PermitType)
}
