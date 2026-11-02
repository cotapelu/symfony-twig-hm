package schema

import (
	"database/sql"
	"time"
)

// Financial Tables

// BankingAccount represents a banking account
type BankingAccount struct {
	ID            string    `json:"id"`
	UserID        string    `json:"userId"`
	AccountNumber string    `json:"accountNumber"`
	AccountType   string    `json:"accountType"`
	Balance       int64     `json:"balance"`
	Status        string    `json:"status"`
	CreatedAt     time.Time `json:"createdAt"`
	UpdatedAt     time.Time `json:"updatedAt"`
}

// BankingTransaction represents a banking transaction
type BankingTransaction struct {
	ID           int64     `json:"id"`
	AccountID    string    `json:"accountId"`
	Type         string    `json:"type"`
	Amount       int64     `json:"amount"`
	BalanceAfter int64     `json:"balanceAfter"`
	ReferenceID  string    `json:"referenceId"`
	Status       string    `json:"status"`
	CreatedAt    time.Time `json:"createdAt"`
}

// CryptoWallet represents a cryptocurrency wallet
type CryptoWallet struct {
	ID        string    `json:"id"`
	UserID    string    `json:"userId"`
	Currency  string    `json:"currency"`
	Balance   int64     `json:"balance"`
	Status    string    `json:"status"`
	CreatedAt time.Time `json:"createdAt"`
	UpdatedAt time.Time `json:"updatedAt"`
}

// CryptoTransaction represents a cryptocurrency transaction
type CryptoTransaction struct {
	ID        string    `json:"id"`
	WalletID  string    `json:"walletId"`
	Type      string    `json:"type"`
	Amount    int64     `json:"amount"`
	TxHash    string    `json:"txHash"`
	Status    string    `json:"status"`
	CreatedAt time.Time `json:"createdAt"`
}

// FraudEvent represents a fraud detection event
type FraudEvent struct {
	ID        int64     `json:"id"`
	UserID    string    `json:"userId"`
	EventType string    `json:"eventType"`
	Score     int       `json:"score"`
	Details   string    `json:"details"` // JSONB
	CreatedAt time.Time `json:"createdAt"`
}

// Location Tables

// LocationTracking represents location tracking data
type LocationTracking struct {
	ID         int64     `json:"id"`
	UserID     string    `json:"userId"`
	EntityType string    `json:"entityType"`
	EntityID   string    `json:"entityId"`
	Latitude   float64   `json:"latitude"`
	Longitude  float64   `json:"longitude"`
	Accuracy   float64   `json:"accuracy"`
	Timestamp  time.Time `json:"timestamp"`
}

// Marketplace Tables

// MarketplaceItem represents a marketplace item
type MarketplaceItem struct {
	ID        int64     `json:"id"`
	SellerID  string    `json:"sellerId"`
	Category  string    `json:"category"`
	Title     string    `json:"title"`
	Price     int64     `json:"price"`
	Status    string    `json:"status"`
	Metadata  string    `json:"metadata"` // JSONB
	CreatedAt time.Time `json:"createdAt"`
	UpdatedAt time.Time `json:"updatedAt"`
}

// MarketplaceOrder represents a marketplace order
type MarketplaceOrder struct {
	ID        int64     `json:"id"`
	BuyerID   string    `json:"buyerId"`
	SellerID  string    `json:"sellerId"`
	ItemID    int64     `json:"itemId"`
	Status    string    `json:"status"`
	Metadata  string    `json:"metadata"` // JSONB
	CreatedAt time.Time `json:"createdAt"`
	UpdatedAt time.Time `json:"updatedAt"`
}

// LandRecord represents a land record
type LandRecord struct {
	ID         int64     `json:"id"`
	OwnerID    string    `json:"ownerId"`
	PlotNumber string    `json:"plotNumber"`
	Area       float64   `json:"area"`
	Address    string    `json:"address"`
	Type       string    `json:"type"`
	Status     string    `json:"status"`
	CreatedAt  time.Time `json:"createdAt"`
	UpdatedAt  time.Time `json:"updatedAt"`
}

// LandPermit represents a land permit
type LandPermit struct {
	ID           int64     `json:"id"`
	ApplicantID  string    `json:"applicantId"`
	LandRecordID int64     `json:"landRecordId"`
	PermitType   string    `json:"permitType"`
	Status       string    `json:"status"`
	Data         string    `json:"data"` // JSONB
	CreatedAt    time.Time `json:"createdAt"`
	UpdatedAt    time.Time `json:"updatedAt"`
}

// Auth & Events Tables

// AuthSession represents an authentication session
type AuthSession struct {
	ID         string    `json:"id"`
	UserID     string    `json:"userId"`
	DeviceInfo string    `json:"deviceInfo"`
	IPAddress  string    `json:"ipAddress"`
	ExpiresAt  time.Time `json:"expiresAt"`
	CreatedAt  time.Time `json:"createdAt"`
}

// EventStore represents an event in the event store
type EventStore struct {
	ID        int64     `json:"id"`
	EventType string    `json:"eventType"`
	Payload   string    `json:"payload"` // JSONB
	CreatedAt time.Time `json:"createdAt"`
}

// Database operations

// CreateBankingAccount creates a new banking account
func CreateBankingAccount(db *sql.DB, account *BankingAccount) error {
	query := `
		INSERT INTO banking_accounts (id, user_id, account_number, account_type, balance, status, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
	`
	_, err := db.Exec(query, account.ID, account.UserID, account.AccountNumber, account.AccountType, account.Balance, account.Status, account.CreatedAt, account.UpdatedAt)
	return err
}

// GetBankingAccount retrieves a banking account by ID
func GetBankingAccount(db *sql.DB, id string) (*BankingAccount, error) {
	query := `
		SELECT id, user_id, account_number, account_type, balance, status, created_at, updated_at
		FROM banking_accounts
		WHERE id = $1
	`
	row := db.QueryRow(query, id)

	var account BankingAccount
	err := row.Scan(&account.ID, &account.UserID, &account.AccountNumber, &account.AccountType, &account.Balance, &account.Status, &account.CreatedAt, &account.UpdatedAt)
	if err != nil {
		return nil, err
	}
	return &account, nil
}

// UpdateBankingAccountBalance updates the balance of a banking account
func UpdateBankingAccountBalance(db *sql.DB, id string, newBalance int64) error {
	query := `
		UPDATE banking_accounts
		SET balance = $1, updated_at = NOW()
		WHERE id = $2
	`
	_, err := db.Exec(query, newBalance, id)
	return err
}

// CreateBankingTransaction creates a new banking transaction
func CreateBankingTransaction(db *sql.DB, tx *BankingTransaction) error {
	query := `
		INSERT INTO banking_transactions (account_id, type, amount, balance_after, reference_id, status, created_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7)
	`
	_, err := db.Exec(query, tx.AccountID, tx.Type, tx.Amount, tx.BalanceAfter, tx.ReferenceID, tx.Status, tx.CreatedAt)
	return err
}

// GetBankingTransactionsByAccount retrieves transactions for a specific account
func GetBankingTransactionsByAccount(db *sql.DB, accountID string, limit int) ([]*BankingTransaction, error) {
	query := `
		SELECT id, account_id, type, amount, balance_after, reference_id, status, created_at
		FROM banking_transactions
		WHERE account_id = $1
		ORDER BY created_at DESC
		LIMIT $2
	`
	rows, err := db.Query(query, accountID, limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var transactions []*BankingTransaction
	for rows.Next() {
		var tx BankingTransaction
		err := rows.Scan(&tx.ID, &tx.AccountID, &tx.Type, &tx.Amount, &tx.BalanceAfter, &tx.ReferenceID, &tx.Status, &tx.CreatedAt)
		if err != nil {
			return nil, err
		}
		transactions = append(transactions, &tx)
	}
	return transactions, nil
}

// CreateCryptoWallet creates a new cryptocurrency wallet
func CreateCryptoWallet(db *sql.DB, wallet *CryptoWallet) error {
	query := `
		INSERT INTO crypto_wallets (id, user_id, currency, balance, status, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7)
	`
	_, err := db.Exec(query, wallet.ID, wallet.UserID, wallet.Currency, wallet.Balance, wallet.Status, wallet.CreatedAt, wallet.UpdatedAt)
	return err
}

// UpdateCryptoWalletBalance updates the balance of a crypto wallet
func UpdateCryptoWalletBalance(db *sql.DB, id string, newBalance int64) error {
	query := `
		UPDATE crypto_wallets
		SET balance = $1, updated_at = NOW()
		WHERE id = $2
	`
	_, err := db.Exec(query, newBalance, id)
	return err
}

// CreateCryptoTransaction creates a new cryptocurrency transaction
func CreateCryptoTransaction(db *sql.DB, tx *CryptoTransaction) error {
	query := `
		INSERT INTO crypto_transactions (wallet_id, type, amount, tx_hash, status, created_at)
		VALUES ($1, $2, $3, $4, $5, $6)
	`
	_, err := db.Exec(query, tx.WalletID, tx.Type, tx.Amount, tx.TxHash, tx.Status, tx.CreatedAt)
	return err
}

// CreateFraudEvent creates a new fraud detection event
func CreateFraudEvent(db *sql.DB, event *FraudEvent) error {
	query := `
		INSERT INTO fraud_events (user_id, event_type, score, details, created_at)
		VALUES ($1, $2, $3, $4, $5)
	`
	_, err := db.Exec(query, event.UserID, event.EventType, event.Score, event.Details, event.CreatedAt)
	return err
}

// GetFraudEventsByUser retrieves fraud events for a specific user
func GetFraudEventsByUser(db *sql.DB, userID string, limit int) ([]*FraudEvent, error) {
	query := `
		SELECT id, user_id, event_type, score, details, created_at
		FROM fraud_events
		WHERE user_id = $1
		ORDER BY created_at DESC
		LIMIT $2
	`
	rows, err := db.Query(query, userID, limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var events []*FraudEvent
	for rows.Next() {
		var event FraudEvent
		err := rows.Scan(&event.ID, &event.UserID, &event.EventType, &event.Score, &event.Details, &event.CreatedAt)
		if err != nil {
			return nil, err
		}
		events = append(events, &event)
	}
	return events, nil
}

// CreateLocationTracking creates a new location tracking record
func CreateLocationTracking(db *sql.DB, tracking *LocationTracking) error {
	query := `
		INSERT INTO location_tracking (user_id, entity_type, entity_id, lat, lon, accuracy, timestamp)
		VALUES ($1, $2, $3, $4, $5, $6, $7)
	`
	_, err := db.Exec(query, tracking.UserID, tracking.EntityType, tracking.EntityID, tracking.Latitude, tracking.Longitude, tracking.Accuracy, tracking.Timestamp)
	return err
}

// GetLatestLocationByUser retrieves the latest location for a user
func GetLatestLocationByUser(db *sql.DB, userID string) (*LocationTracking, error) {
	query := `
		SELECT id, user_id, entity_type, entity_id, lat, lon, accuracy, timestamp
		FROM location_tracking
		WHERE user_id = $1
		ORDER BY timestamp DESC
		LIMIT 1
	`
	row := db.QueryRow(query, userID)

	var tracking LocationTracking
	err := row.Scan(&tracking.ID, &tracking.UserID, &tracking.EntityType, &tracking.EntityID, &tracking.Latitude, &tracking.Longitude, &tracking.Accuracy, &tracking.Timestamp)
	if err != nil {
		return nil, err
	}
	return &tracking, nil
}

// CreateMarketplaceItem creates a new marketplace item
func CreateMarketplaceItem(db *sql.DB, item *MarketplaceItem) error {
	query := `
		INSERT INTO marketplace_items (seller_id, category, title, price, status, metadata, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
	`
	_, err := db.Exec(query, item.SellerID, item.Category, item.Title, item.Price, item.Status, item.Metadata, item.CreatedAt, item.UpdatedAt)
	return err
}

// GetMarketplaceItemsBySeller retrieves items for a specific seller
func GetMarketplaceItemsBySeller(db *sql.DB, sellerID string, limit int) ([]*MarketplaceItem, error) {
	query := `
		SELECT id, seller_id, category, title, price, status, metadata, created_at, updated_at
		FROM marketplace_items
		WHERE seller_id = $1
		ORDER BY created_at DESC
		LIMIT $2
	`
	rows, err := db.Query(query, sellerID, limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var items []*MarketplaceItem
	for rows.Next() {
		var item MarketplaceItem
		err := rows.Scan(&item.ID, &item.SellerID, &item.Category, &item.Title, &item.Price, &item.Status, &item.Metadata, &item.CreatedAt, &item.UpdatedAt)
		if err != nil {
			return nil, err
		}
		items = append(items, &item)
	}
	return items, nil
}

// CreateMarketplaceOrder creates a new marketplace order
func CreateMarketplaceOrder(db *sql.DB, order *MarketplaceOrder) error {
	query := `
		INSERT INTO marketplace_orders (buyer_id, seller_id, item_id, status, metadata, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7)
	`
	_, err := db.Exec(query, order.BuyerID, order.SellerID, order.ItemID, order.Status, order.Metadata, order.CreatedAt, order.UpdatedAt)
	return err
}

// CreateLandRecord creates a new land record
func CreateLandRecord(db *sql.DB, record *LandRecord) error {
	query := `
		INSERT INTO land_records (owner_id, plot_number, area, address, type, status, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
	`
	_, err := db.Exec(query, record.OwnerID, record.PlotNumber, record.Area, record.Address, record.Type, record.Status, record.CreatedAt, record.UpdatedAt)
	return err
}

// CreateLandPermit creates a new land permit
func CreateLandPermit(db *sql.DB, permit *LandPermit) error {
	query := `
		INSERT INTO land_permits (applicant_id, land_record_id, permit_type, status, data, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7)
	`
	_, err := db.Exec(query, permit.ApplicantID, permit.LandRecordID, permit.PermitType, permit.Status, permit.Data, permit.CreatedAt, permit.UpdatedAt)
	return err
}

// CreateAuthSession creates a new authentication session
func CreateAuthSession(db *sql.DB, session *AuthSession) error {
	query := `
		INSERT INTO auth_sessions (id, user_id, device_info, ip_address, expires_at, created_at)
		VALUES ($1, $2, $3, $4, $5, $6)
	`
	_, err := db.Exec(query, session.ID, session.UserID, session.DeviceInfo, session.IPAddress, session.ExpiresAt, session.CreatedAt)
	return err
}

// GetAuthSession retrieves an authentication session by ID
func GetAuthSession(db *sql.DB, id string) (*AuthSession, error) {
	query := `
		SELECT id, user_id, device_info, ip_address, expires_at, created_at
		FROM auth_sessions
		WHERE id = $1
	`
	row := db.QueryRow(query, id)

	var session AuthSession
	err := row.Scan(&session.ID, &session.UserID, &session.DeviceInfo, &session.IPAddress, &session.ExpiresAt, &session.CreatedAt)
	if err != nil {
		return nil, err
	}
	return &session, nil
}

// DeleteAuthSession deletes an authentication session
func DeleteAuthSession(db *sql.DB, id string) error {
	query := `DELETE FROM auth_sessions WHERE id = $1`
	_, err := db.Exec(query, id)
	return err
}

// CreateEvent creates a new event in the event store
func CreateEvent(db *sql.DB, event *EventStore) error {
	query := `
		INSERT INTO event_store (event_type, payload, created_at)
		VALUES ($1, $2, $3)
	`
	_, err := db.Exec(query, event.EventType, event.Payload, event.CreatedAt)
	return err
}

// GetEventsByType retrieves events by type
func GetEventsByType(db *sql.DB, eventType string, limit int) ([]*EventStore, error) {
	query := `
		SELECT id, event_type, payload, created_at
		FROM event_store
		WHERE event_type = $1
		ORDER BY created_at DESC
		LIMIT $2
	`
	rows, err := db.Query(query, eventType, limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var events []*EventStore
	for rows.Next() {
		var event EventStore
		err := rows.Scan(&event.ID, &event.EventType, &event.Payload, &event.CreatedAt)
		if err != nil {
			return nil, err
		}
		events = append(events, &event)
	}
	return events, nil
}
