package models

import (
	"context"
	"database/sql"
	"time"
)

// ==================== FINANCIAL SERVICES ====================

type BankingAccount struct {
	ID            string    `json:"id" db:"id"`
	UserID        string    `json:"user_id" db:"user_id"`
	AccountNumber string    `json:"account_number" db:"account_number"`
	AccountType   string    `json:"account_type" db:"account_type"` // CHECKING, SAVINGS
	Balance       float64   `json:"balance" db:"balance"`
	Status        string    `json:"status" db:"status"`     // ACTIVE, INACTIVE, BLOCKED
	Metadata      string    `json:"metadata" db:"metadata"` // JSON
	CreatedAt     time.Time `json:"created_at" db:"created_at"`
	UpdatedAt     time.Time `json:"updated_at" db:"updated_at"`
}

type BankingTransaction struct {
	ID           string    `json:"id" db:"id"`
	AccountID    string    `json:"account_id" db:"account_id"`
	Type         string    `json:"type" db:"type"` // DEPOSIT, WITHDRAW, TRANSFER
	Amount       float64   `json:"amount" db:"amount"`
	BalanceAfter float64   `json:"balance_after" db:"balance_after"`
	Description  string    `json:"description" db:"description"`
	ReferenceID  string    `json:"reference_id" db:"reference_id"`
	Status       string    `json:"status" db:"status"`     // PENDING, COMPLETED, FAILED
	Metadata     string    `json:"metadata" db:"metadata"` // JSON
	CreatedAt    time.Time `json:"created_at" db:"created_at"`
}

// ==================== CRYPTO WALLETS ====================

type CryptoWallet struct {
	ID        string    `json:"id" db:"id"`
	UserID    string    `json:"user_id" db:"user_id"`
	Currency  string    `json:"currency" db:"currency"` // BTC, ETH, USDT
	Balance   float64   `json:"balance" db:"balance"`
	Status    string    `json:"status" db:"status"`
	Metadata  string    `json:"metadata" db:"metadata"` // JSON
	CreatedAt time.Time `json:"created_at" db:"created_at"`
	UpdatedAt time.Time `json:"updated_at" db:"updated_at"`
}

type CryptoTransaction struct {
	ID        string    `json:"id" db:"id"`
	WalletID  string    `json:"wallet_id" db:"wallet_id"`
	Type      string    `json:"type" db:"type"` // DEPOSIT, WITHDRAW, TRANSFER
	Amount    float64   `json:"amount" db:"amount"`
	TxHash    string    `json:"tx_hash" db:"tx_hash"`
	Status    string    `json:"status" db:"status"`     // PENDING, CONFIRMED, FAILED
	Metadata  string    `json:"metadata" db:"metadata"` // JSON
	CreatedAt time.Time `json:"created_at" db:"created_at"`
}

// ==================== FRAUD DETECTION ====================

type FraudEvent struct {
	ID        string    `json:"id" db:"id"`
	UserID    string    `json:"user_id" db:"user_id"`
	EventType string    `json:"event_type" db:"event_type"` // LOGIN_FAILED, UNUSUAL_LOCATION, HIGH_VALUE
	Score     float64   `json:"score" db:"score"`           // 0-100
	Details   string    `json:"details" db:"details"`       // JSON
	Processed bool      `json:"processed" db:"processed"`
	Metadata  string    `json:"metadata" db:"metadata"` // JSON
	CreatedAt time.Time `json:"created_at" db:"created_at"`
}

// ==================== LOCATION TRACKING ====================

type LocationTracking struct {
	ID         string    `json:"id" db:"id"`
	UserID     string    `json:"user_id" db:"user_id"`
	EntityType string    `json:"entity_type" db:"entity_type"` // USER, COURIER, DRIVER
	EntityID   string    `json:"entity_id" db:"entity_id"`
	Latitude   float64   `json:"latitude" db:"latitude"`
	Longitude  float64   `json:"longitude" db:"longitude"`
	Accuracy   float64   `json:"accuracy" db:"accuracy"`
	Metadata   string    `json:"metadata" db:"metadata"` // JSON
	Timestamp  time.Time `json:"timestamp" db:"timestamp"`
}

// ==================== MARKETPLACE ====================

type MarketplaceItem struct {
	ID        string    `json:"id" db:"id"`
	SellerID  string    `json:"seller_id" db:"seller_id"`
	Category  string    `json:"category" db:"category"`
	Title     string    `json:"title" db:"title"`
	Price     float64   `json:"price" db:"price"`
	Status    string    `json:"status" db:"status"`     // ACTIVE, SOLD, EXPIRED
	Metadata  string    `json:"metadata" db:"metadata"` // JSON
	CreatedAt time.Time `json:"created_at" db:"created_at"`
	UpdatedAt time.Time `json:"updated_at" db:"updated_at"`
}

type MarketplaceOrder struct {
	ID        string    `json:"id" db:"id"`
	BuyerID   string    `json:"buyer_id" db:"buyer_id"`
	SellerID  string    `json:"seller_id" db:"seller_id"`
	ItemID    string    `json:"item_id" db:"item_id"`
	Status    string    `json:"status" db:"status"`     // PENDING, PAID, SHIPPED, DELIVERED, CANCELLED
	Metadata  string    `json:"metadata" db:"metadata"` // JSON
	CreatedAt time.Time `json:"created_at" db:"created_at"`
	UpdatedAt time.Time `json:"updated_at" db:"updated_at"`
}

// ==================== LAND RECORDS ====================

type LandRecord struct {
	ID         string    `json:"id" db:"id"`
	OwnerID    string    `json:"owner_id" db:"owner_id"`
	PlotNumber string    `json:"plot_number" db:"plot_number"`
	Area       float64   `json:"area" db:"area"` // square meters
	Address    string    `json:"address" db:"address"`
	Type       string    `json:"type" db:"type"`         // RESIDENTIAL, AGRICULTURAL, COMMERCIAL
	Status     string    `json:"status" db:"status"`     // REGISTERED, PENDING
	Metadata   string    `json:"metadata" db:"metadata"` // JSON
	CreatedAt  time.Time `json:"created_at" db:"created_at"`
	UpdatedAt  time.Time `json:"updated_at" db:"updated_at"`
}

type LandPermit struct {
	ID           string    `json:"id" db:"id"`
	ApplicantID  string    `json:"applicant_id" db:"applicant_id"`
	LandRecordID string    `json:"land_record_id" db:"land_record_id"`
	PermitType   string    `json:"permit_type" db:"permit_type"` // NEW, RENOVATE, DEMOLISH
	Status       string    `json:"status" db:"status"`           // PENDING, APPROVED, REJECTED
	Data         string    `json:"data" db:"data"`               // JSON
	Metadata     string    `json:"metadata" db:"metadata"`       // JSON
	CreatedAt    time.Time `json:"created_at" db:"created_at"`
	UpdatedAt    time.Time `json:"updated_at" db:"updated_at"`
}

// ==================== AUTH & SECURITY ====================

type AuthSession struct {
	ID         string    `json:"id" db:"id"`
	UserID     string    `json:"user_id" db:"user_id"`
	DeviceInfo string    `json:"device_info" db:"device_info"`
	IPAddress  string    `json:"ip_address" db:"ip_address"`
	Metadata   string    `json:"metadata" db:"metadata"` // JSON
	ExpiresAt  time.Time `json:"expires_at" db:"expires_at"`
	CreatedAt  time.Time `json:"created_at" db:"created_at"`
}

// ==================== EVENT STORE ====================

type EventStore struct {
	ID        string    `json:"id" db:"id"`
	EventType string    `json:"event_type" db:"event_type"`
	Payload   string    `json:"payload" db:"payload"`   // JSON
	Metadata  string    `json:"metadata" db:"metadata"` // JSON
	CreatedAt time.Time `json:"created_at" db:"created_at"`
}

// ==================== REPOSITORY INTERFACES ====================

type BankingRepository interface {
	CreateAccount(ctx context.Context, account *BankingAccount) error
	GetAccount(ctx context.Context, id string) (*BankingAccount, error)
	GetAccountByNumber(ctx context.Context, accountNumber string) (*BankingAccount, error)
	GetAccountsByUser(ctx context.Context, userID string) ([]*BankingAccount, error)
	UpdateBalance(ctx context.Context, id string, amount float64) error
	CreateTransaction(ctx context.Context, tx *BankingTransaction) error
	GetTransactions(ctx context.Context, accountID string, limit, offset int) ([]*BankingTransaction, error)
}

type CryptoRepository interface {
	CreateWallet(ctx context.Context, wallet *CryptoWallet) error
	GetWallet(ctx context.Context, id string) (*CryptoWallet, error)
	GetWalletByUser(ctx context.Context, userID, currency string) (*CryptoWallet, error)
	UpdateBalance(ctx context.Context, id string, amount float64) error
	CreateTransaction(ctx context.Context, tx *CryptoTransaction) error
}

type FraudRepository interface {
	CreateEvent(ctx context.Context, event *FraudEvent) error
	GetEventsByUser(ctx context.Context, userID string, limit int) ([]*FraudEvent, error)
	MarkProcessed(ctx context.Context, id string) error
}

type LocationRepository interface {
	Create(ctx context.Context, loc *LocationTracking) error
	GetLatest(ctx context.Context, entityType, entityID string) (*LocationTracking, error)
	GetHistory(ctx context.Context, entityType, entityID string, from, to time.Time) ([]*LocationTracking, error)
}

type MarketplaceRepository interface {
	CreateItem(ctx context.Context, item *MarketplaceItem) error
	GetItem(ctx context.Context, id string) (*MarketplaceItem, error)
	GetItemsBySeller(ctx context.Context, sellerID string) ([]*MarketplaceItem, error)
	GetItemsByCategory(ctx context.Context, category string, limit, offset int) ([]*MarketplaceItem, error)
	UpdateStatus(ctx context.Context, id string, status string) error
	CreateOrder(ctx context.Context, order *MarketplaceOrder) error
	GetOrder(ctx context.Context, id string) (*MarketplaceOrder, error)
	GetOrdersByBuyer(ctx context.Context, buyerID string) ([]*MarketplaceOrder, error)
	GetOrdersBySeller(ctx context.Context, sellerID string) ([]*MarketplaceOrder, error)
}

type LandRepository interface {
	CreateRecord(ctx context.Context, record *LandRecord) error
	GetRecord(ctx context.Context, id string) (*LandRecord, error)
	GetRecordByPlotNumber(ctx context.Context, plotNumber string) (*LandRecord, error)
	GetRecordsByOwner(ctx context.Context, ownerID string) ([]*LandRecord, error)
	CreatePermit(ctx context.Context, permit *LandPermit) error
	GetPermit(ctx context.Context, id string) (*LandPermit, error)
	GetPermitsByApplicant(ctx context.Context, applicantID string) ([]*LandPermit, error)
}

type AuthRepository interface {
	CreateSession(ctx context.Context, session *AuthSession) error
	GetSession(ctx context.Context, id string) (*AuthSession, error)
	GetSessionByUser(ctx context.Context, userID string) (*AuthSession, error)
	DeleteSession(ctx context.Context, id string) error
	DeleteExpiredSessions(ctx context.Context) error
}

type EventRepository interface {
	Create(ctx context.Context, event *EventStore) error
	GetByType(ctx context.Context, eventType string, limit int) ([]*EventStore, error)
	GetByEntity(ctx context.Context, entityType, entityID string) ([]*EventStore, error)
}

// ==================== DB HELPERS ====================

var (
	_ = sql.NullString{}
	_ = time.Now()
)

// Indexes for optimization
const (
	IndexBankingUserID     = "CREATE INDEX idx_banking_accounts_user ON go.banking_accounts(user_id);"
	IndexBankingAccountNum = "CREATE UNIQUE INDEX idx_banking_accounts_number ON go.banking_accounts(account_number);"
	IndexBankingTxAccount  = "CREATE INDEX idx_banking_transactions_account ON go.banking_transactions(account_id, created_at DESC);"
	IndexFraudUserID       = "CREATE INDEX idx_fraud_events_user ON go.fraud_events(user_id, created_at DESC);"
	IndexLocationEntity    = "CREATE INDEX idx_location_tracking_entity ON go.location_tracking(entity_type, entity_id, timestamp DESC);"
	IndexMarketplaceSeller = "CREATE INDEX idx_marketplace_items_seller ON go.marketplace_items(seller_id);"
	IndexMarketplaceCat    = "CREATE INDEX idx_marketplace_items_category ON go.marketplace_items(category, status);"
	IndexLandPlotNumber    = "CREATE UNIQUE INDEX idx_land_records_plot ON go.land_records(plot_number);"
	IndexLandOwner         = "CREATE INDEX idx_land_records_owner ON go.land_records(owner_id);"
	IndexEventType         = "CREATE INDEX idx_event_store_type ON go.event_store(event_type, created_at DESC);"
)
