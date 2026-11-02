package repository

import (
	"context"
	"database/sql"
	"fmt"
	"time"

	"community.local/internal/models"
)

// BankingRepositoryImpl implements BankingRepository
type BankingRepositoryImpl struct {
	db *sql.DB
}

func NewBankingRepository(db *sql.DB) models.BankingRepository {
	return &BankingRepositoryImpl{db: db}
}

func (r *BankingRepositoryImpl) CreateAccount(ctx context.Context, account *models.BankingAccount) error {
	query := `
		INSERT INTO go.banking_accounts (id, user_id, account_number, account_type, balance, status, metadata, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
	`
	_, err := r.db.ExecContext(ctx, query,
		account.ID, account.UserID, account.AccountNumber, account.AccountType,
		account.Balance, account.Status, account.Metadata, account.CreatedAt, account.UpdatedAt)
	return err
}

func (r *BankingRepositoryImpl) GetAccount(ctx context.Context, id string) (*models.BankingAccount, error) {
	query := `
		SELECT id, user_id, account_number, account_type, balance, status, metadata, created_at, updated_at
		FROM go.banking_accounts WHERE id = $1
	`
	account := &models.BankingAccount{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&account.ID, &account.UserID, &account.AccountNumber, &account.AccountType,
		&account.Balance, &account.Status, &account.Metadata, &account.CreatedAt, &account.UpdatedAt)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	return account, err
}

func (r *BankingRepositoryImpl) GetAccountByNumber(ctx context.Context, accountNumber string) (*models.BankingAccount, error) {
	query := `
		SELECT id, user_id, account_number, account_type, balance, status, metadata, created_at, updated_at
		FROM go.banking_accounts WHERE account_number = $1
	`
	account := &models.BankingAccount{}
	err := r.db.QueryRowContext(ctx, query, accountNumber).Scan(
		&account.ID, &account.UserID, &account.AccountNumber, &account.AccountType,
		&account.Balance, &account.Status, &account.Metadata, &account.CreatedAt, &account.UpdatedAt)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	return account, err
}

func (r *BankingRepositoryImpl) GetAccountsByUser(ctx context.Context, userID string) ([]*models.BankingAccount, error) {
	query := `
		SELECT id, user_id, account_number, account_type, balance, status, metadata, created_at, updated_at
		FROM go.banking_accounts WHERE user_id = $1
	`
	rows, err := r.db.QueryContext(ctx, query, userID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var accounts []*models.BankingAccount
	for rows.Next() {
		account := &models.BankingAccount{}
		err := rows.Scan(
			&account.ID, &account.UserID, &account.AccountNumber, &account.AccountType,
			&account.Balance, &account.Status, &account.Metadata, &account.CreatedAt, &account.UpdatedAt)
		if err != nil {
			return nil, err
		}
		accounts = append(accounts, account)
	}
	return accounts, nil
}

func (r *BankingRepositoryImpl) UpdateBalance(ctx context.Context, id string, amount float64) error {
	query := `UPDATE go.banking_accounts SET balance = balance + $1, updated_at = NOW() WHERE id = $2`
	_, err := r.db.ExecContext(ctx, query, amount, id)
	return err
}

func (r *BankingRepositoryImpl) CreateTransaction(ctx context.Context, tx *models.BankingTransaction) error {
	query := `
		INSERT INTO go.banking_transactions (id, account_id, type, amount, balance_after, description, reference_id, status, metadata, created_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
	`
	_, err := r.db.ExecContext(ctx, query,
		tx.ID, tx.AccountID, tx.Type, tx.Amount, tx.BalanceAfter,
		tx.Description, tx.ReferenceID, tx.Status, tx.Metadata, tx.CreatedAt)
	return err
}

func (r *BankingRepositoryImpl) GetTransactions(ctx context.Context, accountID string, limit, offset int) ([]*models.BankingTransaction, error) {
	query := `
		SELECT id, account_id, type, amount, balance_after, description, reference_id, status, metadata, created_at
		FROM go.banking_transactions WHERE account_id = $1 ORDER BY created_at DESC LIMIT $2 OFFSET $3
	`
	rows, err := r.db.QueryContext(ctx, query, accountID, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var transactions []*models.BankingTransaction
	for rows.Next() {
		tx := &models.BankingTransaction{}
		err := rows.Scan(
			&tx.ID, &tx.AccountID, &tx.Type, &tx.Amount, &tx.BalanceAfter,
			&tx.Description, &tx.ReferenceID, &tx.Status, &tx.Metadata, &tx.CreatedAt)
		if err != nil {
			return nil, err
		}
		transactions = append(transactions, tx)
	}
	return transactions, nil
}

// CryptoRepositoryImpl implements CryptoRepository
type CryptoRepositoryImpl struct {
	db *sql.DB
}

func NewCryptoRepository(db *sql.DB) models.CryptoRepository {
	return &CryptoRepositoryImpl{db: db}
}

func (r *CryptoRepositoryImpl) CreateWallet(ctx context.Context, wallet *models.CryptoWallet) error {
	query := `
		INSERT INTO go.crypto_wallets (id, user_id, currency, balance, status, metadata, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
	`
	_, err := r.db.ExecContext(ctx, query,
		wallet.ID, wallet.UserID, wallet.Currency, wallet.Balance,
		wallet.Status, wallet.Metadata, wallet.CreatedAt, wallet.UpdatedAt)
	return err
}

func (r *CryptoRepositoryImpl) GetWallet(ctx context.Context, id string) (*models.CryptoWallet, error) {
	query := `
		SELECT id, user_id, currency, balance, status, metadata, created_at, updated_at
		FROM go.crypto_wallets WHERE id = $1
	`
	wallet := &models.CryptoWallet{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&wallet.ID, &wallet.UserID, &wallet.Currency, &wallet.Balance,
		&wallet.Status, &wallet.Metadata, &wallet.CreatedAt, &wallet.UpdatedAt)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	return wallet, err
}

func (r *CryptoRepositoryImpl) GetWalletByUser(ctx context.Context, userID, currency string) (*models.CryptoWallet, error) {
	query := `
		SELECT id, user_id, currency, balance, status, metadata, created_at, updated_at
		FROM go.crypto_wallets WHERE user_id = $1 AND currency = $2
	`
	wallet := &models.CryptoWallet{}
	err := r.db.QueryRowContext(ctx, query, userID, currency).Scan(
		&wallet.ID, &wallet.UserID, &wallet.Currency, &wallet.Balance,
		&wallet.Status, &wallet.Metadata, &wallet.CreatedAt, &wallet.UpdatedAt)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	return wallet, err
}

func (r *CryptoRepositoryImpl) UpdateBalance(ctx context.Context, id string, amount float64) error {
	query := `UPDATE go.crypto_wallets SET balance = balance + $1, updated_at = NOW() WHERE id = $2`
	_, err := r.db.ExecContext(ctx, query, amount, id)
	return err
}

func (r *CryptoRepositoryImpl) CreateTransaction(ctx context.Context, tx *models.CryptoTransaction) error {
	query := `
		INSERT INTO go.crypto_transactions (id, wallet_id, type, amount, tx_hash, status, metadata, created_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
	`
	_, err := r.db.ExecContext(ctx, query,
		tx.ID, tx.WalletID, tx.Type, tx.Amount, tx.TxHash, tx.Status, tx.Metadata, tx.CreatedAt)
	return err
}

// FraudRepositoryImpl implements FraudRepository
type FraudRepositoryImpl struct {
	db *sql.DB
}

func NewFraudRepository(db *sql.DB) models.FraudRepository {
	return &FraudRepositoryImpl{db: db}
}

func (r *FraudRepositoryImpl) CreateEvent(ctx context.Context, event *models.FraudEvent) error {
	query := `
		INSERT INTO go.fraud_events (id, user_id, event_type, score, details, processed, metadata, created_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
	`
	_, err := r.db.ExecContext(ctx, query,
		event.ID, event.UserID, event.EventType, event.Score,
		event.Details, event.Processed, event.Metadata, event.CreatedAt)
	return err
}

func (r *FraudRepositoryImpl) GetEventsByUser(ctx context.Context, userID string, limit int) ([]*models.FraudEvent, error) {
	query := `
		SELECT id, user_id, event_type, score, details, processed, metadata, created_at
		FROM go.fraud_events WHERE user_id = $1 ORDER BY created_at DESC LIMIT $2
	`
	rows, err := r.db.QueryContext(ctx, query, userID, limit)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var events []*models.FraudEvent
	for rows.Next() {
		event := &models.FraudEvent{}
		err := rows.Scan(
			&event.ID, &event.UserID, &event.EventType, &event.Score,
			&event.Details, &event.Processed, &event.Metadata, &event.CreatedAt)
		if err != nil {
			return nil, err
		}
		events = append(events, event)
	}
	return events, nil
}

func (r *FraudRepositoryImpl) MarkProcessed(ctx context.Context, id string) error {
	query := `UPDATE go.fraud_events SET processed = true WHERE id = $1`
	_, err := r.db.ExecContext(ctx, query, id)
	return err
}

// LocationRepositoryImpl implements LocationRepository
type LocationRepositoryImpl struct {
	db *sql.DB
}

func NewLocationRepository(db *sql.DB) models.LocationRepository {
	return &LocationRepositoryImpl{db: db}
}

func (r *LocationRepositoryImpl) Create(ctx context.Context, loc *models.LocationTracking) error {
	query := `
		INSERT INTO go.location_tracking (id, user_id, entity_type, entity_id, latitude, longitude, accuracy, metadata, timestamp)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
	`
	_, err := r.db.ExecContext(ctx, query,
		loc.ID, loc.UserID, loc.EntityType, loc.EntityID,
		loc.Latitude, loc.Longitude, loc.Accuracy, loc.Metadata, loc.Timestamp)
	return err
}

func (r *LocationRepositoryImpl) GetLatest(ctx context.Context, entityType, entityID string) (*models.LocationTracking, error) {
	query := `
		SELECT id, user_id, entity_type, entity_id, latitude, longitude, accuracy, metadata, timestamp
		FROM go.location_tracking WHERE entity_type = $1 AND entity_id = $2 ORDER BY timestamp DESC LIMIT 1
	`
	loc := &models.LocationTracking{}
	err := r.db.QueryRowContext(ctx, query, entityType, entityID).Scan(
		&loc.ID, &loc.UserID, &loc.EntityType, &loc.EntityID,
		&loc.Latitude, &loc.Longitude, &loc.Accuracy, &loc.Metadata, &loc.Timestamp)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	return loc, err
}

func (r *LocationRepositoryImpl) GetHistory(ctx context.Context, entityType, entityID string, from, to time.Time) ([]*models.LocationTracking, error) {
	query := `
		SELECT id, user_id, entity_type, entity_id, latitude, longitude, accuracy, metadata, timestamp
		FROM go.location_tracking WHERE entity_type = $1 AND entity_id = $2 AND timestamp >= $3 AND timestamp <= $4 ORDER BY timestamp DESC
	`
	rows, err := r.db.QueryContext(ctx, query, entityType, entityID, from, to)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var locations []*models.LocationTracking
	for rows.Next() {
		loc := &models.LocationTracking{}
		err := rows.Scan(
			&loc.ID, &loc.UserID, &loc.EntityType, &loc.EntityID,
			&loc.Latitude, &loc.Longitude, &loc.Accuracy, &loc.Metadata, &loc.Timestamp)
		if err != nil {
			return nil, err
		}
		locations = append(locations, loc)
	}
	return locations, nil
}

// MarketplaceRepositoryImpl implements MarketplaceRepository
type MarketplaceRepositoryImpl struct {
	db *sql.DB
}

func NewMarketplaceRepository(db *sql.DB) models.MarketplaceRepository {
	return &MarketplaceRepositoryImpl{db: db}
}

func (r *MarketplaceRepositoryImpl) CreateItem(ctx context.Context, item *models.MarketplaceItem) error {
	query := `
		INSERT INTO go.marketplace_items (id, seller_id, category, title, price, status, metadata, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
	`
	_, err := r.db.ExecContext(ctx, query,
		item.ID, item.SellerID, item.Category, item.Title, item.Price,
		item.Status, item.Metadata, item.CreatedAt, item.UpdatedAt)
	return err
}

func (r *MarketplaceRepositoryImpl) GetItem(ctx context.Context, id string) (*models.MarketplaceItem, error) {
	query := `
		SELECT id, seller_id, category, title, price, status, metadata, created_at, updated_at
		FROM go.marketplace_items WHERE id = $1
	`
	item := &models.MarketplaceItem{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&item.ID, &item.SellerID, &item.Category, &item.Title, &item.Price,
		&item.Status, &item.Metadata, &item.CreatedAt, &item.UpdatedAt)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	return item, err
}

func (r *MarketplaceRepositoryImpl) GetItemsBySeller(ctx context.Context, sellerID string) ([]*models.MarketplaceItem, error) {
	query := `
		SELECT id, seller_id, category, title, price, status, metadata, created_at, updated_at
		FROM go.marketplace_items WHERE seller_id = $1 ORDER BY created_at DESC
	`
	rows, err := r.db.QueryContext(ctx, query, sellerID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var items []*models.MarketplaceItem
	for rows.Next() {
		item := &models.MarketplaceItem{}
		err := rows.Scan(
			&item.ID, &item.SellerID, &item.Category, &item.Title, &item.Price,
			&item.Status, &item.Metadata, &item.CreatedAt, &item.UpdatedAt)
		if err != nil {
			return nil, err
		}
		items = append(items, item)
	}
	return items, nil
}

func (r *MarketplaceRepositoryImpl) GetItemsByCategory(ctx context.Context, category string, limit, offset int) ([]*models.MarketplaceItem, error) {
	query := `
		SELECT id, seller_id, category, title, price, status, metadata, created_at, updated_at
		FROM go.marketplace_items WHERE category = $1 AND status = 'ACTIVE' ORDER BY created_at DESC LIMIT $2 OFFSET $3
	`
	rows, err := r.db.QueryContext(ctx, query, category, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var items []*models.MarketplaceItem
	for rows.Next() {
		item := &models.MarketplaceItem{}
		err := rows.Scan(
			&item.ID, &item.SellerID, &item.Category, &item.Title, &item.Price,
			&item.Status, &item.Metadata, &item.CreatedAt, &item.UpdatedAt)
		if err != nil {
			return nil, err
		}
		items = append(items, item)
	}
	return items, nil
}

func (r *MarketplaceRepositoryImpl) UpdateStatus(ctx context.Context, id string, status string) error {
	query := `UPDATE go.marketplace_items SET status = $1, updated_at = NOW() WHERE id = $2`
	_, err := r.db.ExecContext(ctx, query, status, id)
	return err
}

func (r *MarketplaceRepositoryImpl) CreateOrder(ctx context.Context, order *models.MarketplaceOrder) error {
	query := `
		INSERT INTO go.marketplace_orders (id, buyer_id, seller_id, item_id, status, metadata, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
	`
	_, err := r.db.ExecContext(ctx, query,
		order.ID, order.BuyerID, order.SellerID, order.ItemID,
		order.Status, order.Metadata, order.CreatedAt, order.UpdatedAt)
	return err
}

func (r *MarketplaceRepositoryImpl) GetOrder(ctx context.Context, id string) (*models.MarketplaceOrder, error) {
	query := `
		SELECT id, buyer_id, seller_id, item_id, status, metadata, created_at, updated_at
		FROM go.marketplace_orders WHERE id = $1
	`
	order := &models.MarketplaceOrder{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&order.ID, &order.BuyerID, &order.SellerID, &order.ItemID,
		&order.Status, &order.Metadata, &order.CreatedAt, &order.UpdatedAt)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	return order, err
}

func (r *MarketplaceRepositoryImpl) GetOrdersByBuyer(ctx context.Context, buyerID string) ([]*models.MarketplaceOrder, error) {
	query := `
		SELECT id, buyer_id, seller_id, item_id, status, metadata, created_at, updated_at
		FROM go.marketplace_orders WHERE buyer_id = $1 ORDER BY created_at DESC
	`
	rows, err := r.db.QueryContext(ctx, query, buyerID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var orders []*models.MarketplaceOrder
	for rows.Next() {
		order := &models.MarketplaceOrder{}
		err := rows.Scan(
			&order.ID, &order.BuyerID, &order.SellerID, &order.ItemID,
			&order.Status, &order.Metadata, &order.CreatedAt, &order.UpdatedAt)
		if err != nil {
			return nil, err
		}
		orders = append(orders, order)
	}
	return orders, nil
}

func (r *MarketplaceRepositoryImpl) GetOrdersBySeller(ctx context.Context, sellerID string) ([]*models.MarketplaceOrder, error) {
	query := `
		SELECT id, buyer_id, seller_id, item_id, status, metadata, created_at, updated_at
		FROM go.marketplace_orders WHERE seller_id = $1 ORDER BY created_at DESC
	`
	rows, err := r.db.QueryContext(ctx, query, sellerID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var orders []*models.MarketplaceOrder
	for rows.Next() {
		order := &models.MarketplaceOrder{}
		err := rows.Scan(
			&order.ID, &order.BuyerID, &order.SellerID, &order.ItemID,
			&order.Status, &order.Metadata, &order.CreatedAt, &order.UpdatedAt)
		if err != nil {
			return nil, err
		}
		orders = append(orders, order)
	}
	return orders, nil
}

// LandRepositoryImpl implements LandRepository
type LandRepositoryImpl struct {
	db *sql.DB
}

func NewLandRepository(db *sql.DB) models.LandRepository {
	return &LandRepositoryImpl{db: db}
}

func (r *LandRepositoryImpl) CreateRecord(ctx context.Context, record *models.LandRecord) error {
	query := `
		INSERT INTO go.land_records (id, owner_id, plot_number, area, address, type, status, metadata, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
	`
	_, err := r.db.ExecContext(ctx, query,
		record.ID, record.OwnerID, record.PlotNumber, record.Area, record.Address,
		record.Type, record.Status, record.Metadata, record.CreatedAt, record.UpdatedAt)
	return err
}

func (r *LandRepositoryImpl) GetRecord(ctx context.Context, id string) (*models.LandRecord, error) {
	query := `
		SELECT id, owner_id, plot_number, area, address, type, status, metadata, created_at, updated_at
		FROM go.land_records WHERE id = $1
	`
	record := &models.LandRecord{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&record.ID, &record.OwnerID, &record.PlotNumber, &record.Area, &record.Address,
		&record.Type, &record.Status, &record.Metadata, &record.CreatedAt, &record.UpdatedAt)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	return record, err
}

func (r *LandRepositoryImpl) GetRecordByPlotNumber(ctx context.Context, plotNumber string) (*models.LandRecord, error) {
	query := `
		SELECT id, owner_id, plot_number, area, address, type, status, metadata, created_at, updated_at
		FROM go.land_records WHERE plot_number = $1
	`
	record := &models.LandRecord{}
	err := r.db.QueryRowContext(ctx, query, plotNumber).Scan(
		&record.ID, &record.OwnerID, &record.PlotNumber, &record.Area, &record.Address,
		&record.Type, &record.Status, &record.Metadata, &record.CreatedAt, &record.UpdatedAt)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	return record, err
}

func (r *LandRepositoryImpl) GetRecordsByOwner(ctx context.Context, ownerID string) ([]*models.LandRecord, error) {
	query := `
		SELECT id, owner_id, plot_number, area, address, type, status, metadata, created_at, updated_at
		FROM go.land_records WHERE owner_id = $1 ORDER BY created_at DESC
	`
	rows, err := r.db.QueryContext(ctx, query, ownerID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var records []*models.LandRecord
	for rows.Next() {
		record := &models.LandRecord{}
		err := rows.Scan(
			&record.ID, &record.OwnerID, &record.PlotNumber, &record.Area, &record.Address,
			&record.Type, &record.Status, &record.Metadata, &record.CreatedAt, &record.UpdatedAt)
		if err != nil {
			return nil, err
		}
		records = append(records, record)
	}
	return records, nil
}

func (r *LandRepositoryImpl) CreatePermit(ctx context.Context, permit *models.LandPermit) error {
	query := `
		INSERT INTO go.land_permits (id, applicant_id, land_record_id, permit_type, status, data, metadata, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
	`
	_, err := r.db.ExecContext(ctx, query,
		permit.ID, permit.ApplicantID, permit.LandRecordID, permit.PermitType,
		permit.Status, permit.Data, permit.Metadata, permit.CreatedAt, permit.UpdatedAt)
	return err
}

func (r *LandRepositoryImpl) GetPermit(ctx context.Context, id string) (*models.LandPermit, error) {
	query := `
		SELECT id, applicant_id, land_record_id, permit_type, status, data, metadata, created_at, updated_at
		FROM go.land_permits WHERE id = $1
	`
	permit := &models.LandPermit{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&permit.ID, &permit.ApplicantID, &permit.LandRecordID, &permit.PermitType,
		&permit.Status, &permit.Data, &permit.Metadata, &permit.CreatedAt, &permit.UpdatedAt)
	if err == sql.ErrNoRows {
		return nil, nil
	}
	return permit, err
}

func (r *LandRepositoryImpl) GetPermitsByApplicant(ctx context.Context, applicantID string) ([]*models.LandPermit, error) {
	query := `
		SELECT id, applicant_id, land_record_id, permit_type, status, data, metadata, created_at, updated_at
		FROM go.land_permits WHERE applicant_id = $1 ORDER BY created_at DESC
	`
	rows, err := r.db.QueryContext(ctx, query, applicantID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var permits []*models.LandPermit
	for rows.Next() {
		permit := &models.LandPermit{}
		err := rows.Scan(
			&permit.ID, &permit.ApplicantID, &permit.LandRecordID, &permit.PermitType,
			&permit.Status, &permit.Data, &permit.Metadata, &permit.CreatedAt, &permit.UpdatedAt)
		if err != nil {
			return nil, err
		}
		permits = append(permits, permit)
	}
	return permits, nil
}

// RepositoryFactory creates all repository implementations
type RepositoryFactory struct {
	db *sql.DB
}

func NewRepositoryFactory(db *sql.DB) *RepositoryFactory {
	return &RepositoryFactory{db: db}
}

func (f *RepositoryFactory) Banking() models.BankingRepository {
	return NewBankingRepository(f.db)
}

func (f *RepositoryFactory) Crypto() models.CryptoRepository {
	return NewCryptoRepository(f.db)
}

func (f *RepositoryFactory) Fraud() models.FraudRepository {
	return NewFraudRepository(f.db)
}

func (f *RepositoryFactory) Location() models.LocationRepository {
	return NewLocationRepository(f.db)
}

func (f *RepositoryFactory) Marketplace() models.MarketplaceRepository {
	return NewMarketplaceRepository(f.db)
}

func (f *RepositoryFactory) Land() models.LandRepository {
	return NewLandRepository(f.db)
}

// Helper function to initialize database connection
func InitDB(dataSourceName string) (*sql.DB, error) {
	db, err := sql.Open("postgres", dataSourceName)
	if err != nil {
		return nil, fmt.Errorf("failed to open database: %w", err)
	}

	if err := db.Ping(); err != nil {
		return nil, fmt.Errorf("failed to ping database: %w", err)
	}

	return db, nil
}
