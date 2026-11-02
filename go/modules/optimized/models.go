package optimized

import (
	"context"
	"database/sql"
	"time"

	"github.com/google/uuid"
)

// BankingAccount represents a user's banking account
type BankingAccount struct {
	ID            uuid.UUID `json:"id" db:"id"`
	UserID        string    `json:"user_id" db:"user_id"`
	AccountNumber string    `json:"account_number" db:"account_number"`
	AccountType   string    `json:"account_type" db:"account_type"`
	Balance       int       `json:"balance" db:"balance"` // cents
	Status        string    `json:"status" db:"status"`
	CreatedAt     time.Time `json:"created_at" db:"created_at"`
	UpdatedAt     time.Time `json:"updated_at" db:"updated_at"`
}

// BankingTransaction represents a banking transaction
type BankingTransaction struct {
	ID           uuid.UUID `json:"id" db:"id"`
	AccountID    uuid.UUID `json:"account_id" db:"account_id"`
	Type         string    `json:"type" db:"type"`
	Amount       int       `json:"amount" db:"amount"` // cents
	BalanceAfter int       `json:"balance_after" db:"balance_after"`
	ReferenceID  string    `json:"reference_id" db:"reference_id"`
	Status       string    `json:"status" db:"status"`
	CreatedAt    time.Time `json:"created_at" db:"created_at"`
}

// CryptoWallet represents a cryptocurrency wallet
type CryptoWallet struct {
	ID        uuid.UUID `json:"id" db:"id"`
	UserID    string    `json:"user_id" db:"user_id"`
	Currency  string    `json:"currency" db:"currency"`
	Balance   int       `json:"balance" db:"balance"` // smallest unit
	Status    string    `json:"status" db:"status"`
	CreatedAt time.Time `json:"created_at" db:"created_at"`
}

// CryptoTransaction represents a crypto transaction
type CryptoTransaction struct {
	ID        uuid.UUID `json:"id" db:"id"`
	WalletID  uuid.UUID `json:"wallet_id" db:"wallet_id"`
	Type      string    `json:"type" db:"type"`
	Amount    int       `json:"amount" db:"amount"`
	TxHash    string    `json:"tx_hash" db:"tx_hash"`
	Status    string    `json:"status" db:"status"`
	CreatedAt time.Time `json:"created_at" db:"created_at"`
}

// FraudEvent represents a fraud detection event
type FraudEvent struct {
	ID        uuid.UUID              `json:"id" db:"id"`
	UserID    string                 `json:"user_id" db:"user_id"`
	EventType string                 `json:"event_type" db:"event_type"`
	Score     int                    `json:"score" db:"score"`
	Details   map[string]interface{} `json:"details" db:"details"`
	CreatedAt time.Time              `json:"created_at" db:"created_at"`
}

// LocationTracking represents location data
type LocationTracking struct {
	ID         uuid.UUID `json:"id" db:"id"`
	UserID     string    `json:"user_id" db:"user_id"`
	EntityType string    `json:"entity_type" db:"entity_type"`
	EntityID   uuid.UUID `json:"entity_id" db:"entity_id"`
	Lat        float64   `json:"lat" db:"lat"`
	Lon        float64   `json:"lon" db:"lon"`
	Accuracy   float64   `json:"accuracy" db:"accuracy"`
	Timestamp  time.Time `json:"timestamp" db:"timestamp"`
}

// MarketplaceItem represents an item for sale
type MarketplaceItem struct {
	ID        uuid.UUID              `json:"id" db:"id"`
	SellerID  string                 `json:"seller_id" db:"seller_id"`
	Category  string                 `json:"category" db:"category"`
	Title     string                 `json:"title" db:"title"`
	Price     int                    `json:"price" db:"price"` // cents
	Status    string                 `json:"status" db:"status"`
	Metadata  map[string]interface{} `json:"metadata" db:"metadata"`
	CreatedAt time.Time              `json:"created_at" db:"created_at"`
	UpdatedAt time.Time              `json:"updated_at" db:"updated_at"`
}

// MarketplaceOrder represents a marketplace order
type MarketplaceOrder struct {
	ID        uuid.UUID              `json:"id" db:"id"`
	BuyerID   string                 `json:"buyer_id" db:"buyer_id"`
	SellerID  string                 `json:"seller_id" db:"seller_id"`
	ItemID    uuid.UUID              `json:"item_id" db:"item_id"`
	Status    string                 `json:"status" db:"status"`
	Metadata  map[string]interface{} `json:"metadata" db:"metadata"`
	CreatedAt time.Time              `json:"created_at" db:"created_at"`
	UpdatedAt time.Time              `json:"updated_at" db:"updated_at"`
}

// LandRecord represents a land record
type LandRecord struct {
	ID         uuid.UUID `json:"id" db:"id"`
	OwnerID    string    `json:"owner_id" db:"owner_id"`
	PlotNumber string    `json:"plot_number" db:"plot_number"`
	Area       float64   `json:"area" db:"area"`
	Address    string    `json:"address" db:"address"`
	Type       string    `json:"type" db:"type"`
	Status     string    `json:"status" db:"status"`
	CreatedAt  time.Time `json:"created_at" db:"created_at"`
	UpdatedAt  time.Time `json:"updated_at" db:"updated_at"`
}

// LandPermit represents a land permit
type LandPermit struct {
	ID           uuid.UUID              `json:"id" db:"id"`
	ApplicantID  string                 `json:"applicant_id" db:"applicant_id"`
	LandRecordID uuid.UUID              `json:"land_record_id" db:"land_record_id"`
	PermitType   string                 `json:"permit_type" db:"permit_type"`
	Status       string                 `json:"status" db:"status"`
	Data         map[string]interface{} `json:"data" db:"data"`
	CreatedAt    time.Time              `json:"created_at" db:"created_at"`
	UpdatedAt    time.Time              `json:"updated_at" db:"updated_at"`
}

// AuthSession represents an authentication session
type AuthSession struct {
	ID         uuid.UUID              `json:"id" db:"id"`
	UserID     string                 `json:"user_id" db:"user_id"`
	DeviceInfo map[string]interface{} `json:"device_info" db:"device_info"`
	IPAddress  string                 `json:"ip_address" db:"ip_address"`
	ExpiresAt  time.Time              `json:"expires_at" db:"expires_at"`
	CreatedAt  time.Time              `json:"created_at" db:"created_at"`
}

// EventStore represents stored domain events
type EventStore struct {
	ID        uuid.UUID              `json:"id" db:"id"`
	EventType string                 `json:"event_type" db:"event_type"`
	Payload   map[string]interface{} `json:"payload" db:"payload"`
	CreatedAt time.Time              `json:"created_at" db:"created_at"`
}

// Repository interfaces for optimized schema
type BankingAccountRepository interface {
	Create(ctx context.Context, account *BankingAccount) error
	GetByID(ctx context.Context, id uuid.UUID) (*BankingAccount, error)
	GetByUserID(ctx context.Context, userID string) ([]*BankingAccount, error)
	UpdateBalance(ctx context.Context, id uuid.UUID, amount int) error
}

type BankingTransactionRepository interface {
	Create(ctx context.Context, tx *BankingTransaction) error
	GetByAccountID(ctx context.Context, accountID uuid.UUID, limit, offset int) ([]*BankingTransaction, error)
	GetByReferenceID(ctx context.Context, refID string) (*BankingTransaction, error)
}

type LocationTrackingRepository interface {
	Create(ctx context.Context, loc *LocationTracking) error
	GetByEntity(ctx context.Context, entityType string, entityID uuid.UUID) (*LocationTracking, error)
	GetHistory(ctx context.Context, entityType string, entityID uuid.UUID, from, to time.Time) ([]*LocationTracking, error)
}

type MarketplaceRepository interface {
	CreateItem(ctx context.Context, item *MarketplaceItem) error
	GetItemByID(ctx context.Context, id uuid.UUID) (*MarketplaceItem, error)
	GetItemsBySeller(ctx context.Context, sellerID string) ([]*MarketplaceItem, error)
	CreateOrder(ctx context.Context, order *MarketplaceOrder) error
	GetOrderByID(ctx context.Context, id uuid.UUID) (*MarketplaceOrder, error)
}

type EventStoreRepository interface {
	Save(ctx context.Context, event *EventStore) error
	GetByType(ctx context.Context, eventType string, limit int) ([]*EventStore, error)
	GetByEntity(ctx context.Context, entityType string, entityID uuid.UUID) ([]*EventStore, error)
}

// NewBankingAccount creates a new banking account
func NewBankingAccount(userID, accountType, currency string) *BankingAccount {
	return &BankingAccount{
		ID:            uuid.New(),
		UserID:        userID,
		AccountNumber: generateAccountNumber(),
		AccountType:   accountType,
		Balance:       0,
		Status:        "active",
		CreatedAt:     time.Now(),
		UpdatedAt:     time.Now(),
	}
}

// NewLocationTracking creates a new location tracking entry
func NewLocationTracking(userID, entityType string, entityID uuid.UUID, lat, lon, accuracy float64) *LocationTracking {
	return &LocationTracking{
		ID:         uuid.New(),
		UserID:     userID,
		EntityType: entityType,
		EntityID:   entityID,
		Lat:        lat,
		Lon:        lon,
		Accuracy:   accuracy,
		Timestamp:  time.Now(),
	}
}

func generateAccountNumber() string {
	return uuid.New().String()[:8]
}

// Expose sqlc-like query helpers (for reference)
var (
	_ = sql.Open
	_ = time.Now()
)
