package repository

import (
	"context"
	"database/sql"
	"testing"
	"time"

	"community.local/internal/models"

	"github.com/DATA-DOG/go-sqlmock"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func newMockDB() (*sql.DB, sqlmock.Sqlmock) {
	db, mock, err := sqlmock.New()
	if err != nil {
		panic(err)
	}
	return db, mock
}

func TestBankingRepositoryImpl_CreateAccount(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	account := &models.BankingAccount{
		ID:            "acc-1",
		UserID:        "user-1",
		AccountNumber: "ACC-001",
		AccountType:   "CHECKING",
		Balance:       0,
		Status:        "ACTIVE",
		Metadata:      "",
		CreatedAt:     now,
		UpdatedAt:     now,
	}

	queryPattern := `INSERT INTO go\.banking_accounts \(id, user_id, account_number, account_type, balance, status, metadata, created_at, updated_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8, \$9\)`
	mock.ExpectExec(queryPattern).
		WithArgs(account.ID, account.UserID, account.AccountNumber, account.AccountType, account.Balance, account.Status, account.Metadata, account.CreatedAt, account.UpdatedAt).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.CreateAccount(context.Background(), account)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetAccount_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	account := &models.BankingAccount{
		ID:            "acc-1",
		UserID:        "user-1",
		AccountNumber: "ACC-001",
		AccountType:   "CHECKING",
		Balance:       100,
		Status:        "ACTIVE",
		Metadata:      "",
		CreatedAt:     now,
		UpdatedAt:     now,
	}

	query := `SELECT id, user_id, account_number, account_type, balance, status, metadata, created_at, updated_at FROM go\.banking_accounts WHERE id = \$1`
	rows := sqlmock.NewRows([]string{"id", "user_id", "account_number", "account_type", "balance", "status", "metadata", "created_at", "updated_at"}).
		AddRow(account.ID, account.UserID, account.AccountNumber, account.AccountType, account.Balance, account.Status, account.Metadata, account.CreatedAt, account.UpdatedAt)
	mock.ExpectQuery(query).WithArgs("acc-1").WillReturnRows(rows)

	result, err := repo.GetAccount(context.Background(), "acc-1")
	require.NoError(t, err)
	assert.Equal(t, account, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetAccount_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	query := `SELECT id, user_id, account_number, account_type, balance, status, metadata, created_at, updated_at FROM go\.banking_accounts WHERE id = \$1`
	mock.ExpectQuery(query).WithArgs("nonexistent").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetAccount(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetAccountByNumber(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	account := &models.BankingAccount{
		ID:            "acc-1",
		UserID:        "user-1",
		AccountNumber: "123456",
		AccountType:   "SAVINGS",
		Balance:       200,
		Status:        "ACTIVE",
		Metadata:      "",
		CreatedAt:     now,
		UpdatedAt:     now,
	}

	query := `SELECT id, user_id, account_number, account_type, balance, status, metadata, created_at, updated_at FROM go\.banking_accounts WHERE account_number = \$1`
	rows := sqlmock.NewRows([]string{"id", "user_id", "account_number", "account_type", "balance", "status", "metadata", "created_at", "updated_at"}).
		AddRow(account.ID, account.UserID, account.AccountNumber, account.AccountType, account.Balance, account.Status, account.Metadata, account.CreatedAt, account.UpdatedAt)
	mock.ExpectQuery(query).WithArgs("123456").WillReturnRows(rows)

	result, err := repo.GetAccountByNumber(context.Background(), "123456")
	require.NoError(t, err)
	assert.Equal(t, account, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetAccountsByUser(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	accounts := []*models.BankingAccount{
		{ID: "acc-1", UserID: "user-1", AccountNumber: "A1", AccountType: "CHECKING", Balance: 100, Status: "ACTIVE", Metadata: "", CreatedAt: now, UpdatedAt: now},
		{ID: "acc-2", UserID: "user-1", AccountNumber: "A2", AccountType: "SAVINGS", Balance: 200, Status: "ACTIVE", Metadata: "", CreatedAt: now, UpdatedAt: now},
	}

	query := `SELECT id, user_id, account_number, account_type, balance, status, metadata, created_at, updated_at FROM go\.banking_accounts WHERE user_id = \$1`
	rows := sqlmock.NewRows([]string{"id", "user_id", "account_number", "account_type", "balance", "status", "metadata", "created_at", "updated_at"}).
		AddRow(accounts[0].ID, accounts[0].UserID, accounts[0].AccountNumber, accounts[0].AccountType, accounts[0].Balance, accounts[0].Status, accounts[0].Metadata, accounts[0].CreatedAt, accounts[0].UpdatedAt).
		AddRow(accounts[1].ID, accounts[1].UserID, accounts[1].AccountNumber, accounts[1].AccountType, accounts[1].Balance, accounts[1].Status, accounts[1].Metadata, accounts[1].CreatedAt, accounts[1].UpdatedAt)
	mock.ExpectQuery(query).WithArgs("user-1").WillReturnRows(rows)

	result, err := repo.GetAccountsByUser(context.Background(), "user-1")
	require.NoError(t, err)
	assert.Equal(t, accounts, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_UpdateBalance(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	// Expected: UPDATE go.banking_accounts SET balance = balance + $1, updated_at = NOW() WHERE id = $2
	mock.ExpectExec(`UPDATE go\.banking_accounts SET balance = balance \+ \$1, updated_at = NOW\(\) WHERE id = \$2`).
		WithArgs(50.0, "acc-1").
		WillReturnResult(sqlmock.NewResult(1, 1))

	err := repo.UpdateBalance(context.Background(), "acc-1", 50.0)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_CreateTransaction(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	tx := &models.BankingTransaction{
		ID:           "tx-1",
		AccountID:    "acc-1",
		Type:         "DEPOSIT",
		Amount:       100,
		BalanceAfter: 200,
		Description:  "test",
		ReferenceID:  "ref-1",
		Status:       "COMPLETED",
		Metadata:     "",
		CreatedAt:    now,
	}

	queryPattern := `INSERT INTO go\.banking_transactions \(id, account_id, type, amount, balance_after, description, reference_id, status, metadata, created_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8, \$9, \$10\)`
	mock.ExpectExec(queryPattern).
		WithArgs(tx.ID, tx.AccountID, tx.Type, tx.Amount, tx.BalanceAfter, tx.Description, tx.ReferenceID, tx.Status, tx.Metadata, tx.CreatedAt).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.CreateTransaction(context.Background(), tx)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetTransactions(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	txs := []*models.BankingTransaction{
		{ID: "tx-1", AccountID: "acc-1", Type: "DEPOSIT", Amount: 100, BalanceAfter: 200, Status: "COMPLETED", CreatedAt: now},
		{ID: "tx-2", AccountID: "acc-1", Type: "WITHDRAW", Amount: 50, BalanceAfter: 150, Status: "COMPLETED", CreatedAt: now},
	}

	query := `SELECT id, account_id, type, amount, balance_after, description, reference_id, status, metadata, created_at FROM go\.banking_transactions WHERE account_id = \$1 ORDER BY created_at DESC LIMIT \$2 OFFSET \$3`
	rows := sqlmock.NewRows([]string{"id", "account_id", "type", "amount", "balance_after", "description", "reference_id", "status", "metadata", "created_at"}).
		AddRow(txs[0].ID, txs[0].AccountID, txs[0].Type, txs[0].Amount, txs[0].BalanceAfter, txs[0].Description, txs[0].ReferenceID, txs[0].Status, txs[0].Metadata, txs[0].CreatedAt).
		AddRow(txs[1].ID, txs[1].AccountID, txs[1].Type, txs[1].Amount, txs[1].BalanceAfter, txs[1].Description, txs[1].ReferenceID, txs[1].Status, txs[1].Metadata, txs[1].CreatedAt)
	mock.ExpectQuery(query).WithArgs("acc-1", 10, 0).WillReturnRows(rows) // note: default limit param from service is 10

	result, err := repo.GetTransactions(context.Background(), "acc-1", 10, 0)
	require.NoError(t, err)
	assert.Equal(t, txs, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

// CryptoRepositoryImpl tests

func TestCryptoRepositoryImpl_CreateWallet(t *testing.T) {
	db, mock := newMockDB()
	repo := NewCryptoRepository(db)

	now := time.Now()
	wallet := &models.CryptoWallet{
		ID:        "wallet-1",
		UserID:    "user-1",
		Currency:  "BTC",
		Balance:   0,
		Status:    "ACTIVE",
		Metadata:  "",
		CreatedAt: now,
		UpdatedAt: now,
	}

	query := `INSERT INTO go\.crypto_wallets \(id, user_id, currency, balance, status, metadata, created_at, updated_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8\)`
	mock.ExpectExec(query).
		WithArgs(wallet.ID, wallet.UserID, wallet.Currency, wallet.Balance, wallet.Status, wallet.Metadata, wallet.CreatedAt, wallet.UpdatedAt).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.CreateWallet(context.Background(), wallet)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCryptoRepositoryImpl_GetWallet_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewCryptoRepository(db)

	now := time.Now()
	wallet := &models.CryptoWallet{
		ID:        "wallet-1",
		UserID:    "user-1",
		Currency:  "BTC",
		Balance:   10,
		Status:    "ACTIVE",
		Metadata:  "",
		CreatedAt: now,
		UpdatedAt: now,
	}

	query := `SELECT id, user_id, currency, balance, status, metadata, created_at, updated_at FROM go\.crypto_wallets WHERE id = \$1`
	rows := sqlmock.NewRows([]string{"id", "user_id", "currency", "balance", "status", "metadata", "created_at", "updated_at"}).
		AddRow(wallet.ID, wallet.UserID, wallet.Currency, wallet.Balance, wallet.Status, wallet.Metadata, wallet.CreatedAt, wallet.UpdatedAt)
	mock.ExpectQuery(query).WithArgs("wallet-1").WillReturnRows(rows)

	result, err := repo.GetWallet(context.Background(), "wallet-1")
	require.NoError(t, err)
	assert.Equal(t, wallet, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCryptoRepositoryImpl_GetWallet_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewCryptoRepository(db)

	query := `SELECT id, user_id, currency, balance, status, metadata, created_at, updated_at FROM go\.crypto_wallets WHERE id = \$1`
	mock.ExpectQuery(query).WithArgs("nonexistent").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetWallet(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCryptoRepositoryImpl_GetWalletByUser_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewCryptoRepository(db)

	now := time.Now()
	wallet := &models.CryptoWallet{
		ID:        "wallet-1",
		UserID:    "user-1",
		Currency:  "ETH",
		Balance:   5,
		Status:    "ACTIVE",
		Metadata:  "",
		CreatedAt: now,
		UpdatedAt: now,
	}

	query := `SELECT id, user_id, currency, balance, status, metadata, created_at, updated_at FROM go\.crypto_wallets WHERE user_id = \$1 AND currency = \$2`
	rows := sqlmock.NewRows([]string{"id", "user_id", "currency", "balance", "status", "metadata", "created_at", "updated_at"}).
		AddRow(wallet.ID, wallet.UserID, wallet.Currency, wallet.Balance, wallet.Status, wallet.Metadata, wallet.CreatedAt, wallet.UpdatedAt)
	mock.ExpectQuery(query).WithArgs("user-1", "ETH").WillReturnRows(rows)

	result, err := repo.GetWalletByUser(context.Background(), "user-1", "ETH")
	require.NoError(t, err)
	assert.Equal(t, wallet, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCryptoRepositoryImpl_GetWalletByUser_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewCryptoRepository(db)

	query := `SELECT id, user_id, currency, balance, status, metadata, created_at, updated_at FROM go\.crypto_wallets WHERE user_id = \$1 AND currency = \$2`
	mock.ExpectQuery(query).WithArgs("user-2", "BTC").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetWalletByUser(context.Background(), "user-2", "BTC")
	require.NoError(t, err)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCryptoRepositoryImpl_UpdateBalance(t *testing.T) {
	db, mock := newMockDB()
	repo := NewCryptoRepository(db)

	mock.ExpectExec(`UPDATE go\.crypto_wallets SET balance = balance \+ \$1, updated_at = NOW\(\) WHERE id = \$2`).
		WithArgs(20.5, "wallet-1").
		WillReturnResult(sqlmock.NewResult(1, 1))

	err := repo.UpdateBalance(context.Background(), "wallet-1", 20.5)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCryptoRepositoryImpl_CreateTransaction(t *testing.T) {
	db, mock := newMockDB()
	repo := NewCryptoRepository(db)

	now := time.Now()
	tx := &models.CryptoTransaction{
		ID:       "tx-1",
		WalletID: "wallet-1",
		Type:     "DEPOSIT",
		Amount:   100,
		TxHash:   "hash123",
		Status:   "CONFIRMED",
		Metadata: "",
		CreatedAt: now,
	}

	query := `INSERT INTO go\.crypto_transactions \(id, wallet_id, type, amount, tx_hash, status, metadata, created_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8\)`
	mock.ExpectExec(query).
		WithArgs(tx.ID, tx.WalletID, tx.Type, tx.Amount, tx.TxHash, tx.Status, tx.Metadata, tx.CreatedAt).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.CreateTransaction(context.Background(), tx)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}


// LocationRepositoryImpl tests

func TestLocationRepositoryImpl_Create(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLocationRepository(db)

	now := time.Now()
	loc := &models.LocationTracking{
		ID:         "loc-1",
		UserID:     "user-1",
		EntityType: "DRIVER",
		EntityID:   "driver-1",
		Latitude:   10.0,
		Longitude:  20.0,
		Accuracy:   5.0,
		Metadata:   "",
		Timestamp:  now,
	}

	query := `INSERT INTO go\.location_tracking \(id, user_id, entity_type, entity_id, latitude, longitude, accuracy, metadata, timestamp\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8, \$9\)`
	mock.ExpectExec(query).
		WithArgs(loc.ID, loc.UserID, loc.EntityType, loc.EntityID, loc.Latitude, loc.Longitude, loc.Accuracy, loc.Metadata, loc.Timestamp).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.Create(context.Background(), loc)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLocationRepositoryImpl_GetLatest_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLocationRepository(db)

	now := time.Now()
	loc := &models.LocationTracking{
		ID:         "loc-1",
		UserID:     "user-1",
		EntityType: "DRIVER",
		EntityID:   "driver-1",
		Latitude:   10.0,
		Longitude:  20.0,
		Accuracy:   5.0,
		Metadata:   "",
		Timestamp:  now,
	}

	query := `SELECT id, user_id, entity_type, entity_id, latitude, longitude, accuracy, metadata, timestamp FROM go\.location_tracking WHERE entity_type = \$1 AND entity_id = \$2 ORDER BY timestamp DESC LIMIT 1`
	rows := sqlmock.NewRows([]string{"id", "user_id", "entity_type", "entity_id", "latitude", "longitude", "accuracy", "metadata", "timestamp"}).
		AddRow(loc.ID, loc.UserID, loc.EntityType, loc.EntityID, loc.Latitude, loc.Longitude, loc.Accuracy, loc.Metadata, loc.Timestamp)
	mock.ExpectQuery(query).WithArgs("DRIVER", "driver-1").WillReturnRows(rows)

	result, err := repo.GetLatest(context.Background(), "DRIVER", "driver-1")
	require.NoError(t, err)
	assert.Equal(t, loc, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLocationRepositoryImpl_GetLatest_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLocationRepository(db)

	query := `SELECT id, user_id, entity_type, entity_id, latitude, longitude, accuracy, metadata, timestamp FROM go\.location_tracking WHERE entity_type = \$1 AND entity_id = \$2 ORDER BY timestamp DESC LIMIT 1`
	mock.ExpectQuery(query).WithArgs("DRIVER", "driver-1").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetLatest(context.Background(), "DRIVER", "driver-1")
	require.NoError(t, err)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLocationRepositoryImpl_GetHistory_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLocationRepository(db)

	now := time.Now()
	older := now.Add(-2 * time.Hour)
	latest := now.Add(-1 * time.Hour)
	locations := []*models.LocationTracking{
		{ID: "loc-2", EntityType: "DRIVER", EntityID: "driver-1", Latitude: 11, Longitude: 21, Timestamp: latest},
		{ID: "loc-1", EntityType: "DRIVER", EntityID: "driver-1", Latitude: 10, Longitude: 20, Timestamp: older},
	}
	from := now.Add(-3 * time.Hour)
	to := now

	query := `SELECT id, user_id, entity_type, entity_id, latitude, longitude, accuracy, metadata, timestamp FROM go\.location_tracking WHERE entity_type = \$1 AND entity_id = \$2 AND timestamp >= \$3 AND timestamp <= \$4 ORDER BY timestamp DESC`
	rows := sqlmock.NewRows([]string{"id", "user_id", "entity_type", "entity_id", "latitude", "longitude", "accuracy", "metadata", "timestamp"}).
		AddRow(locations[0].ID, "", locations[0].EntityType, locations[0].EntityID, locations[0].Latitude, locations[0].Longitude, 0.0, "", locations[0].Timestamp).
		AddRow(locations[1].ID, "", locations[1].EntityType, locations[1].EntityID, locations[1].Latitude, locations[1].Longitude, 0.0, "", locations[1].Timestamp)
	mock.ExpectQuery(query).WithArgs("DRIVER", "driver-1", from, to).WillReturnRows(rows)

	result, err := repo.GetHistory(context.Background(), "DRIVER", "driver-1", from, to)
	require.NoError(t, err)
	// Order should be DESC by timestamp, so first should be latest
	if len(result) != 2 || result[0].ID != "loc-2" {
		t.Fatalf("Unexpected result order or length: %+v", result)
	}
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLocationRepositoryImpl_GetHistory_NoResults(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLocationRepository(db)

	now := time.Now()
	from := now.Add(1 * time.Hour)
	to := now.Add(2 * time.Hour)

	query := `SELECT id, user_id, entity_type, entity_id, latitude, longitude, accuracy, metadata, timestamp FROM go\.location_tracking WHERE entity_type = \$1 AND entity_id = \$2 AND timestamp >= \$3 AND timestamp <= \$4 ORDER BY timestamp DESC`
	rows := sqlmock.NewRows([]string{"id", "user_id", "entity_type", "entity_id", "latitude", "longitude", "accuracy", "metadata", "timestamp"})
	mock.ExpectQuery(query).WithArgs("DRIVER", "driver-1", from, to).WillReturnRows(rows)

	result, err := repo.GetHistory(context.Background(), "DRIVER", "driver-1", from, to)
	require.NoError(t, err)
	if len(result) != 0 {
		t.Errorf("Expected no locations, got %d", len(result))
	}
	require.NoError(t, mock.ExpectationsWereMet())
}


// MarketplaceRepositoryImpl tests

func TestMarketplaceRepositoryImpl_CreateItem(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	now := time.Now()
	item := &models.MarketplaceItem{
		ID:        "item-1",
		SellerID:  "seller-1",
		Category:  "Electronics",
		Title:     "Phone",
		Price:     100,
		Status:    "ACTIVE",
		Metadata:  "",
		CreatedAt: now,
		UpdatedAt: now,
	}

	query := `INSERT INTO go\.marketplace_items \(id, seller_id, category, title, price, status, metadata, created_at, updated_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8, \$9\)`
	mock.ExpectExec(query).
		WithArgs(item.ID, item.SellerID, item.Category, item.Title, item.Price, item.Status, item.Metadata, item.CreatedAt, item.UpdatedAt).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.CreateItem(context.Background(), item)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestMarketplaceRepositoryImpl_GetItem_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	now := time.Now()
	item := &models.MarketplaceItem{
		ID:        "item-1",
		SellerID:  "seller-1",
		Category:  "Electronics",
		Title:     "Phone",
		Price:     100,
		Status:    "ACTIVE",
		Metadata:  "",
		CreatedAt: now,
		UpdatedAt: now,
	}

	query := `SELECT id, seller_id, category, title, price, status, metadata, created_at, updated_at FROM go\.marketplace_items WHERE id = \$1`
	rows := sqlmock.NewRows([]string{"id", "seller_id", "category", "title", "price", "status", "metadata", "created_at", "updated_at"}).
		AddRow(item.ID, item.SellerID, item.Category, item.Title, item.Price, item.Status, item.Metadata, item.CreatedAt, item.UpdatedAt)
	mock.ExpectQuery(query).WithArgs("item-1").WillReturnRows(rows)

	result, err := repo.GetItem(context.Background(), "item-1")
	require.NoError(t, err)
	assert.Equal(t, item, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestMarketplaceRepositoryImpl_GetItem_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	query := `SELECT id, seller_id, category, title, price, status, metadata, created_at, updated_at FROM go\.marketplace_items WHERE id = \$1`
	mock.ExpectQuery(query).WithArgs("nonexistent").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetItem(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestMarketplaceRepositoryImpl_GetItemsBySeller_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	now := time.Now()
	items := []*models.MarketplaceItem{
		{ID: "item-1", SellerID: "seller-1", Category: "Electronics", Title: "Phone", Price: 100, Status: "ACTIVE", Metadata: "", CreatedAt: now, UpdatedAt: now},
		{ID: "item-2", SellerID: "seller-1", Category: "Books", Title: "Book", Price: 20, Status: "ACTIVE", Metadata: "", CreatedAt: now, UpdatedAt: now},
	}

	query := `SELECT id, seller_id, category, title, price, status, metadata, created_at, updated_at FROM go\.marketplace_items WHERE seller_id = \$1 ORDER BY created_at DESC`
	rows := sqlmock.NewRows([]string{"id", "seller_id", "category", "title", "price", "status", "metadata", "created_at", "updated_at"}).
		AddRow(items[0].ID, items[0].SellerID, items[0].Category, items[0].Title, items[0].Price, items[0].Status, items[0].Metadata, items[0].CreatedAt, items[0].UpdatedAt).
		AddRow(items[1].ID, items[1].SellerID, items[1].Category, items[1].Title, items[1].Price, items[1].Status, items[1].Metadata, items[1].CreatedAt, items[1].UpdatedAt)
	mock.ExpectQuery(query).WithArgs("seller-1").WillReturnRows(rows)

	result, err := repo.GetItemsBySeller(context.Background(), "seller-1")
	require.NoError(t, err)
	assert.Equal(t, items, result)
	require.NoError(t, mock.ExpectationsWereMet())
}


func TestMarketplaceRepositoryImpl_CreateOrder(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	now := time.Now()
	order := &models.MarketplaceOrder{
		ID:        "order-1",
		BuyerID:   "buyer-1",
		SellerID:  "seller-1",
		ItemID:    "item-1",
		Status:    "PENDING",
		Metadata:  "",
		CreatedAt: now,
		UpdatedAt: now,
	}

	query := `INSERT INTO go\.marketplace_orders \(id, buyer_id, seller_id, item_id, status, metadata, created_at, updated_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8\)`
	mock.ExpectExec(query).
		WithArgs(order.ID, order.BuyerID, order.SellerID, order.ItemID, order.Status, order.Metadata, order.CreatedAt, order.UpdatedAt).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.CreateOrder(context.Background(), order)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestMarketplaceRepositoryImpl_GetOrder_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	now := time.Now()
	order := &models.MarketplaceOrder{
		ID:        "order-1",
		BuyerID:   "buyer-1",
		SellerID:  "seller-1",
		ItemID:    "item-1",
		Status:    "PENDING",
		Metadata:  "",
		CreatedAt: now,
		UpdatedAt: now,
	}

	query := `SELECT id, buyer_id, seller_id, item_id, status, metadata, created_at, updated_at FROM go\.marketplace_orders WHERE id = \$1`
	rows := sqlmock.NewRows([]string{"id", "buyer_id", "seller_id", "item_id", "status", "metadata", "created_at", "updated_at"}).
		AddRow(order.ID, order.BuyerID, order.SellerID, order.ItemID, order.Status, order.Metadata, order.CreatedAt, order.UpdatedAt)
	mock.ExpectQuery(query).WithArgs("order-1").WillReturnRows(rows)

	result, err := repo.GetOrder(context.Background(), "order-1")
	require.NoError(t, err)
	assert.Equal(t, order, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestMarketplaceRepositoryImpl_GetOrder_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	query := `SELECT id, buyer_id, seller_id, item_id, status, metadata, created_at, updated_at FROM go\.marketplace_orders WHERE id = \$1`
	mock.ExpectQuery(query).WithArgs("nonexistent").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetOrder(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestMarketplaceRepositoryImpl_GetOrdersByBuyer_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	now := time.Now()
	orders := []*models.MarketplaceOrder{
		{ID: "order-1", BuyerID: "buyer-1", SellerID: "seller-1", ItemID: "item-1", Status: "PENDING", Metadata: "", CreatedAt: now, UpdatedAt: now},
		{ID: "order-2", BuyerID: "buyer-1", SellerID: "seller-2", ItemID: "item-2", Status: "COMPLETED", Metadata: "", CreatedAt: now, UpdatedAt: now},
	}

	query := `SELECT id, buyer_id, seller_id, item_id, status, metadata, created_at, updated_at FROM go\.marketplace_orders WHERE buyer_id = \$1 ORDER BY created_at DESC`
	rows := sqlmock.NewRows([]string{"id", "buyer_id", "seller_id", "item_id", "status", "metadata", "created_at", "updated_at"}).
		AddRow(orders[0].ID, orders[0].BuyerID, orders[0].SellerID, orders[0].ItemID, orders[0].Status, orders[0].Metadata, orders[0].CreatedAt, orders[0].UpdatedAt).
		AddRow(orders[1].ID, orders[1].BuyerID, orders[1].SellerID, orders[1].ItemID, orders[1].Status, orders[1].Metadata, orders[1].CreatedAt, orders[1].UpdatedAt)
	mock.ExpectQuery(query).WithArgs("buyer-1").WillReturnRows(rows)

	result, err := repo.GetOrdersByBuyer(context.Background(), "buyer-1")
	require.NoError(t, err)
	assert.Equal(t, orders, result)
	require.NoError(t, mock.ExpectationsWereMet())
}
func TestMarketplaceRepositoryImpl_UpdateStatus(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	mock.ExpectExec(`UPDATE go\.marketplace_items SET status = \$1, updated_at = NOW\(\) WHERE id = \$2`).
		WithArgs("SOLD", "item-1").
		WillReturnResult(sqlmock.NewResult(1, 1))

	err := repo.UpdateStatus(context.Background(), "item-1", "SOLD")
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}


// FraudRepositoryImpl tests

func TestFraudRepositoryImpl_CreateEvent(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudRepository(db)

	now := time.Now()
	event := &models.FraudEvent{
		ID:        "evt-1",
		UserID:    "user-1",
		EventType: "LOGIN_FAILED",
		Score:     85.5,
		Details:   "Suspicious activity",
		Processed: false,
		Metadata:  "",
		CreatedAt: now,
	}

	query := `INSERT INTO go\.fraud_events \(id, user_id, event_type, score, details, processed, metadata, created_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8\)`
	mock.ExpectExec(query).
		WithArgs(event.ID, event.UserID, event.EventType, event.Score, event.Details, event.Processed, event.Metadata, event.CreatedAt).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.CreateEvent(context.Background(), event)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudRepositoryImpl_GetEventsByUser_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudRepository(db)

	now := time.Now()
	events := []*models.FraudEvent{
		{ID: "evt-1", UserID: "user-1", EventType: "LOGIN_FAILED", Score: 80, Details: "", Processed: false, Metadata: "", CreatedAt: now},
		{ID: "evt-2", UserID: "user-1", EventType: "UNUSUAL_LOCATION", Score: 60, Details: "", Processed: true, Metadata: "", CreatedAt: now},
	}

	query := `SELECT id, user_id, event_type, score, details, processed, metadata, created_at FROM go\.fraud_events WHERE user_id = \$1 ORDER BY created_at DESC LIMIT \$2`
	rows := sqlmock.NewRows([]string{"id", "user_id", "event_type", "score", "details", "processed", "metadata", "created_at"}).
		AddRow(events[0].ID, events[0].UserID, events[0].EventType, events[0].Score, events[0].Details, events[0].Processed, events[0].Metadata, events[0].CreatedAt).
		AddRow(events[1].ID, events[1].UserID, events[1].EventType, events[1].Score, events[1].Details, events[1].Processed, events[1].Metadata, events[1].CreatedAt)
	mock.ExpectQuery(query).WithArgs("user-1", 10).WillReturnRows(rows)

	result, err := repo.GetEventsByUser(context.Background(), "user-1", 10)
	require.NoError(t, err)
	assert.Equal(t, events, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudRepositoryImpl_MarkProcessed(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudRepository(db)

	mock.ExpectExec(`UPDATE go\.fraud_events SET processed = true WHERE id = \$1`).
		WithArgs("evt-1").
		WillReturnResult(sqlmock.NewResult(1, 1))

	err := repo.MarkProcessed(context.Background(), "evt-1")
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}


// LandRepositoryImpl tests

func TestLandRepositoryImpl_CreateRecord(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLandRepository(db)

	now := time.Now()
	record := &models.LandRecord{
		ID:          "rec-1",
		OwnerID:     "owner-1",
		PlotNumber:  "PLOT-001",
		Area:        100.5,
		Address:     "123 Main St",
		Type:        "RESIDENTIAL",
		Status:      "REGISTERED",
		Metadata:    "",
		CreatedAt:   now,
		UpdatedAt:   now,
	}

	query := `INSERT INTO go\.land_records \(id, owner_id, plot_number, area, address, type, status, metadata, created_at, updated_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8, \$9, \$10\)`
	mock.ExpectExec(query).
		WithArgs(record.ID, record.OwnerID, record.PlotNumber, record.Area, record.Address, record.Type, record.Status, record.Metadata, record.CreatedAt, record.UpdatedAt).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.CreateRecord(context.Background(), record)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLandRepositoryImpl_GetRecord_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLandRepository(db)

	now := time.Now()
	record := &models.LandRecord{
		ID:          "rec-1",
		OwnerID:     "owner-1",
		PlotNumber:  "PLOT-001",
		Area:        100.5,
		Address:     "123 Main St",
		Type:        "RESIDENTIAL",
		Status:      "REGISTERED",
		Metadata:    "",
		CreatedAt:   now,
		UpdatedAt:   now,
	}

	query := `SELECT id, owner_id, plot_number, area, address, type, status, metadata, created_at, updated_at FROM go\.land_records WHERE id = \$1`
	rows := sqlmock.NewRows([]string{"id", "owner_id", "plot_number", "area", "address", "type", "status", "metadata", "created_at", "updated_at"}).
		AddRow(record.ID, record.OwnerID, record.PlotNumber, record.Area, record.Address, record.Type, record.Status, record.Metadata, record.CreatedAt, record.UpdatedAt)
	mock.ExpectQuery(query).WithArgs("rec-1").WillReturnRows(rows)

	result, err := repo.GetRecord(context.Background(), "rec-1")
	require.NoError(t, err)
	assert.Equal(t, record, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLandRepositoryImpl_GetRecord_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLandRepository(db)

	query := `SELECT id, owner_id, plot_number, area, address, type, status, metadata, created_at, updated_at FROM go\.land_records WHERE id = \$1`
	mock.ExpectQuery(query).WithArgs("nonexistent").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetRecord(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLandRepositoryImpl_GetRecordByPlotNumber_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLandRepository(db)

	now := time.Now()
	record := &models.LandRecord{
		ID:          "rec-1",
		OwnerID:     "owner-1",
		PlotNumber:  "PLOT-001",
		Area:        100.5,
		Address:     "123 Main St",
		Type:        "RESIDENTIAL",
		Status:      "REGISTERED",
		Metadata:    "",
		CreatedAt:   now,
		UpdatedAt:   now,
	}

	query := `SELECT id, owner_id, plot_number, area, address, type, status, metadata, created_at, updated_at FROM go\.land_records WHERE plot_number = \$1`
	rows := sqlmock.NewRows([]string{"id", "owner_id", "plot_number", "area", "address", "type", "status", "metadata", "created_at", "updated_at"}).
		AddRow(record.ID, record.OwnerID, record.PlotNumber, record.Area, record.Address, record.Type, record.Status, record.Metadata, record.CreatedAt, record.UpdatedAt)
	mock.ExpectQuery(query).WithArgs("PLOT-001").WillReturnRows(rows)

	result, err := repo.GetRecordByPlotNumber(context.Background(), "PLOT-001")
	require.NoError(t, err)
	assert.Equal(t, record, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLandRepositoryImpl_GetRecordsByOwner_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLandRepository(db)

	now := time.Now()
	records := []*models.LandRecord{
		{ID: "rec-1", OwnerID: "owner-1", PlotNumber: "P1", Area: 100, Address: "A1", Type: "RESIDENTIAL", Status: "REGISTERED", Metadata: "", CreatedAt: now, UpdatedAt: now},
		{ID: "rec-2", OwnerID: "owner-1", PlotNumber: "P2", Area: 200, Address: "A2", Type: "AGRICULTURAL", Status: "REGISTERED", Metadata: "", CreatedAt: now, UpdatedAt: now},
	}

	query := `SELECT id, owner_id, plot_number, area, address, type, status, metadata, created_at, updated_at FROM go\.land_records WHERE owner_id = \$1 ORDER BY created_at DESC`
	rows := sqlmock.NewRows([]string{"id", "owner_id", "plot_number", "area", "address", "type", "status", "metadata", "created_at", "updated_at"}).
		AddRow(records[0].ID, records[0].OwnerID, records[0].PlotNumber, records[0].Area, records[0].Address, records[0].Type, records[0].Status, records[0].Metadata, records[0].CreatedAt, records[0].UpdatedAt).
		AddRow(records[1].ID, records[1].OwnerID, records[1].PlotNumber, records[1].Area, records[1].Address, records[1].Type, records[1].Status, records[1].Metadata, records[1].CreatedAt, records[1].UpdatedAt)
	mock.ExpectQuery(query).WithArgs("owner-1").WillReturnRows(rows)

	result, err := repo.GetRecordsByOwner(context.Background(), "owner-1")
	require.NoError(t, err)
	assert.Equal(t, records, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLandRepositoryImpl_CreatePermit(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLandRepository(db)

	now := time.Now()
	permit := &models.LandPermit{
		ID:            "perm-1",
		ApplicantID:   "app-1",
		LandRecordID:  "rec-1",
		PermitType:    "NEW",
		Status:        "PENDING",
		Data:          "",
		Metadata:      "",
		CreatedAt:     now,
		UpdatedAt:     now,
	}

	query := `INSERT INTO go\.land_permits \(id, applicant_id, land_record_id, permit_type, status, data, metadata, created_at, updated_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8, \$9\)`
	mock.ExpectExec(query).
		WithArgs(permit.ID, permit.ApplicantID, permit.LandRecordID, permit.PermitType, permit.Status, permit.Data, permit.Metadata, permit.CreatedAt, permit.UpdatedAt).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.CreatePermit(context.Background(), permit)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLandRepositoryImpl_GetPermit_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLandRepository(db)

	now := time.Now()
	permit := &models.LandPermit{
		ID:           "perm-1",
		ApplicantID:  "app-1",
		LandRecordID: "rec-1",
		PermitType:   "NEW",
		Status:       "PENDING",
		Data:         "",
		Metadata:     "",
		CreatedAt:    now,
		UpdatedAt:    now,
	}

	query := `SELECT id, applicant_id, land_record_id, permit_type, status, data, metadata, created_at, updated_at FROM go\.land_permits WHERE id = \$1`
	rows := sqlmock.NewRows([]string{"id", "applicant_id", "land_record_id", "permit_type", "status", "data", "metadata", "created_at", "updated_at"}).
		AddRow(permit.ID, permit.ApplicantID, permit.LandRecordID, permit.PermitType, permit.Status, permit.Data, permit.Metadata, permit.CreatedAt, permit.UpdatedAt)
	mock.ExpectQuery(query).WithArgs("perm-1").WillReturnRows(rows)

	result, err := repo.GetPermit(context.Background(), "perm-1")
	require.NoError(t, err)
	assert.Equal(t, permit, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLandRepositoryImpl_GetPermit_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLandRepository(db)

	query := `SELECT id, applicant_id, land_record_id, permit_type, status, data, metadata, created_at, updated_at FROM go\.land_permits WHERE id = \$1`
	mock.ExpectQuery(query).WithArgs("nonexistent").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetPermit(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestLandRepositoryImpl_GetPermitsByApplicant_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewLandRepository(db)

	now := time.Now()
	permits := []*models.LandPermit{
		{ID: "perm-1", ApplicantID: "app-1", LandRecordID: "rec-1", PermitType: "NEW", Status: "PENDING", Data: "", Metadata: "", CreatedAt: now, UpdatedAt: now},
		{ID: "perm-2", ApplicantID: "app-1", LandRecordID: "rec-2", PermitType: "RENOVATE", Status: "APPROVED", Data: "", Metadata: "", CreatedAt: now, UpdatedAt: now},
	}

	query := `SELECT id, applicant_id, land_record_id, permit_type, status, data, metadata, created_at, updated_at FROM go\.land_permits WHERE applicant_id = \$1 ORDER BY created_at DESC`
	rows := sqlmock.NewRows([]string{"id", "applicant_id", "land_record_id", "permit_type", "status", "data", "metadata", "created_at", "updated_at"}).
		AddRow(permits[0].ID, permits[0].ApplicantID, permits[0].LandRecordID, permits[0].PermitType, permits[0].Status, permits[0].Data, permits[0].Metadata, permits[0].CreatedAt, permits[0].UpdatedAt).
		AddRow(permits[1].ID, permits[1].ApplicantID, permits[1].LandRecordID, permits[1].PermitType, permits[1].Status, permits[1].Data, permits[1].Metadata, permits[1].CreatedAt, permits[1].UpdatedAt)
	mock.ExpectQuery(query).WithArgs("app-1").WillReturnRows(rows)

	result, err := repo.GetPermitsByApplicant(context.Background(), "app-1")
	require.NoError(t, err)
	assert.Equal(t, permits, result)
	require.NoError(t, mock.ExpectationsWereMet())
}


// Missing Marketplace repository tests

func TestMarketplaceRepositoryImpl_GetItemsByCategory_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	now := time.Now()
	items := []*models.MarketplaceItem{
		{ID: "item-1", SellerID: "seller-1", Category: "Electronics", Title: "Phone", Price: 100, Status: "ACTIVE", Metadata: "", CreatedAt: now, UpdatedAt: now},
		{ID: "item-2", SellerID: "seller-2", Category: "Electronics", Title: "Laptop", Price: 200, Status: "ACTIVE", Metadata: "", CreatedAt: now, UpdatedAt: now},
	}

	query := `SELECT id, seller_id, category, title, price, status, metadata, created_at, updated_at FROM go\.marketplace_items WHERE category = \$1 AND status = 'ACTIVE' ORDER BY created_at DESC LIMIT \$2 OFFSET \$3`
	rows := sqlmock.NewRows([]string{"id", "seller_id", "category", "title", "price", "status", "metadata", "created_at", "updated_at"}).
		AddRow(items[0].ID, items[0].SellerID, items[0].Category, items[0].Title, items[0].Price, items[0].Status, items[0].Metadata, items[0].CreatedAt, items[0].UpdatedAt).
		AddRow(items[1].ID, items[1].SellerID, items[1].Category, items[1].Title, items[1].Price, items[1].Status, items[1].Metadata, items[1].CreatedAt, items[1].UpdatedAt)
	mock.ExpectQuery(query).WithArgs("Electronics", 10, 0).WillReturnRows(rows)

	result, err := repo.GetItemsByCategory(context.Background(), "Electronics", 10, 0)
	require.NoError(t, err)
	assert.Equal(t, items, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestMarketplaceRepositoryImpl_GetOrdersBySeller_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewMarketplaceRepository(db)

	now := time.Now()
	orders := []*models.MarketplaceOrder{
		{ID: "order-1", BuyerID: "buyer-1", SellerID: "seller-1", ItemID: "item-1", Status: "PENDING", Metadata: "", CreatedAt: now, UpdatedAt: now},
		{ID: "order-2", BuyerID: "buyer-2", SellerID: "seller-1", ItemID: "item-2", Status: "COMPLETED", Metadata: "", CreatedAt: now, UpdatedAt: now},
	}

	query := `SELECT id, buyer_id, seller_id, item_id, status, metadata, created_at, updated_at FROM go\.marketplace_orders WHERE seller_id = \$1 ORDER BY created_at DESC`
	rows := sqlmock.NewRows([]string{"id", "buyer_id", "seller_id", "item_id", "status", "metadata", "created_at", "updated_at"}).
		AddRow(orders[0].ID, orders[0].BuyerID, orders[0].SellerID, orders[0].ItemID, orders[0].Status, orders[0].Metadata, orders[0].CreatedAt, orders[0].UpdatedAt).
		AddRow(orders[1].ID, orders[1].BuyerID, orders[1].SellerID, orders[1].ItemID, orders[1].Status, orders[1].Metadata, orders[1].CreatedAt, orders[1].UpdatedAt)
	mock.ExpectQuery(query).WithArgs("seller-1").WillReturnRows(rows)

	result, err := repo.GetOrdersBySeller(context.Background(), "seller-1")
	require.NoError(t, err)
	assert.Equal(t, orders, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

