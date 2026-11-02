package schema

import (
	"testing"
	"time"

	"github.com/DATA-DOG/go-sqlmock"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestCreateBankingAccount(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	// Expect an Exec with an INSERT; we don't validate exact query precisely
	mock.ExpectExec(`INSERT INTO banking_accounts`).WillReturnResult(sqlmock.NewResult(1, 1))

	account := &BankingAccount{
		ID:            "id1",
		UserID:        "user1",
		AccountNumber: "1234567890",
		AccountType:   "CHECKING",
		Balance:       5000,
		Status:        "ACTIVE",
	}

	if err := CreateBankingAccount(db, account); err != nil {
		t.Errorf("CreateBankingAccount() error = %v", err)
	}

	if err := mock.ExpectationsWereMet(); err != nil {
		t.Errorf("unmet expectations: %v", err)
	}
}

func TestGetBankingAccount(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	// Simulate a row
	expectedID := "id1"
	rows := sqlmock.NewRows([]string{"id", "user_id", "account_number", "account_type", "balance", "status", "created_at", "updated_at"}).
		AddRow("id1", "user1", "1234567890", "CHECKING", 5000, "ACTIVE", time.Now(), time.Now())

	mock.ExpectQuery(`SELECT .* FROM banking_accounts WHERE id = \$1`).WillReturnRows(rows)

	account, err := GetBankingAccount(db, expectedID)
	if err != nil {
		t.Fatalf("GetBankingAccount() error = %v", err)
	}
	if account.ID != expectedID {
		t.Errorf("GetBankingAccount() ID = %s, want %s", account.ID, expectedID)
	}
	if account.UserID != "user1" {
		t.Errorf("UserID = %s, want user1", account.UserID)
	}

	if err := mock.ExpectationsWereMet(); err != nil {
		t.Errorf("unmet expectations: %v", err)
	}
}

func TestGetBankingTransactionsByAccount(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	accountID := "acc1"
	rows := sqlmock.NewRows([]string{"id", "account_id", "type", "amount", "balance_after", "reference_id", "status", "created_at"}).
		AddRow(1, "acc1", "DEPOSIT", 100, 1000, "ref1", "COMPLETED", time.Now())

	mock.ExpectQuery(`SELECT .* FROM banking_transactions WHERE account_id = \$1 ORDER BY created_at DESC LIMIT \$2`).WillReturnRows(rows)

	txs, err := GetBankingTransactionsByAccount(db, accountID, 10)
	if err != nil {
		t.Fatalf("GetBankingTransactionsByAccount() error = %v", err)
	}
	if len(txs) != 1 {
		t.Fatalf("expected 1 transaction, got %d", len(txs))
	}
	if txs[0].AccountID != accountID {
		t.Errorf("transaction account_id = %s, want %s", txs[0].AccountID, accountID)
	}
	if txs[0].Type != "DEPOSIT" {
		t.Errorf("transaction type = %s, want DEPOSIT", txs[0].Type)
	}

	if err := mock.ExpectationsWereMet(); err != nil {
		t.Errorf("unmet expectations: %v", err)
	}
}

func TestCreateCryptoWallet(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	mock.ExpectExec(`INSERT INTO crypto_wallets`).WillReturnResult(sqlmock.NewResult(1, 1))

	wallet := &CryptoWallet{
		ID:       "wallet1",
		UserID:   "user1",
		Currency: "BTC",
		Balance:  1, // integer for simplicity
		Status:   "ACTIVE",
	}

	if err := CreateCryptoWallet(db, wallet); err != nil {
		t.Errorf("CreateCryptoWallet() error = %v", err)
	}

	if err := mock.ExpectationsWereMet(); err != nil {
		t.Errorf("unmet expectations: %v", err)
	}
}

func TestCreateFraudEvent(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	mock.ExpectExec(`INSERT INTO fraud_events`).WillReturnResult(sqlmock.NewResult(1, 1))

	event := &FraudEvent{
		ID:        1,
		UserID:    "user1",
		EventType: "LOGIN_FAILED",
		Score:     85,
		Details:   `{"reason":"multiple failed attempts"}`,
	}

	if err := CreateFraudEvent(db, event); err != nil {
		t.Errorf("CreateFraudEvent() error = %v", err)
	}

	if err := mock.ExpectationsWereMet(); err != nil {
		t.Errorf("unmet expectations: %v", err)
	}
}

func TestGetAuthSession(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	expectedID := "sess1"
	rows := sqlmock.NewRows([]string{"id", "user_id", "device_info", "ip_address", "expires_at", "created_at"}).
		AddRow("sess1", "user1", "deviceX", "1.2.3.4", time.Now(), time.Now())

	mock.ExpectQuery(`SELECT .* FROM auth_sessions WHERE id = \$1`).WillReturnRows(rows)

	sess, err := GetAuthSession(db, expectedID)
	if err != nil {
		t.Fatalf("GetAuthSession() error = %v", err)
	}
	if sess.ID != expectedID {
		t.Errorf("Session ID = %s, want %s", sess.ID, expectedID)
	}
	if sess.UserID != "user1" {
		t.Errorf("UserID = %s, want user1", sess.UserID)
	}

	if err := mock.ExpectationsWereMet(); err != nil {
		t.Errorf("unmet expectations: %v", err)
	}
}

func TestCreateEvent(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	mock.ExpectExec(`INSERT INTO event_store`).WillReturnResult(sqlmock.NewResult(1, 1))

	event := &EventStore{
		ID:        1,
		EventType: "MessageSent",
		Payload:   `{"message":"hello"}`,
	}

	if err := CreateEvent(db, event); err != nil {
		t.Errorf("CreateEvent() error = %v", err)
	}

	if err := mock.ExpectationsWereMet(); err != nil {
		t.Errorf("unmet expectations: %v", err)
	}
}

func TestUpdateBankingAccountBalance(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	mock.ExpectExec(`UPDATE banking_accounts SET balance = \$1, updated_at = NOW\(\) WHERE id = \$2`).
		WithArgs(int64(1500), "acc-1").
		WillReturnResult(sqlmock.NewResult(1, 1))

	err = UpdateBankingAccountBalance(db, "acc-1", int64(1500))
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCreateBankingTransaction(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	now := time.Now()
	mock.ExpectExec(`INSERT INTO banking_transactions \(account_id, type, amount, balance_after, reference_id, status, created_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7\)`).
		WithArgs("acc-1", "DEPOSIT", int64(100), int64(1100), "ref1", "COMPLETED", now).
		WillReturnResult(sqlmock.NewResult(1, 1))

	tx := &BankingTransaction{
		AccountID:    "acc-1",
		Type:         "DEPOSIT",
		Amount:       100,
		BalanceAfter: 1100,
		ReferenceID:  "ref1",
		Status:       "COMPLETED",
		CreatedAt:    now,
	}
	err = CreateBankingTransaction(db, tx)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestGetFraudEventsByUser_Success(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	now := time.Now()
	events := []*FraudEvent{
		{ID: int64(1), UserID: "user-1", EventType: "LOGIN_FAILED", Score: 85, Details: "details", CreatedAt: now},
		{ID: int64(2), UserID: "user-1", EventType: "UNUSUAL_LOCATION", Score: 60, Details: "", CreatedAt: now},
	}
	rows := sqlmock.NewRows([]string{"id", "user_id", "event_type", "score", "details", "created_at"}).
		AddRow(events[0].ID, events[0].UserID, events[0].EventType, events[0].Score, events[0].Details, events[0].CreatedAt).
		AddRow(events[1].ID, events[1].UserID, events[1].EventType, events[1].Score, events[1].Details, events[1].CreatedAt)

	mock.ExpectQuery(`SELECT .* FROM fraud_events WHERE user_id = \$1 ORDER BY created_at DESC LIMIT \$2`).WithArgs("user-1", 10).WillReturnRows(rows)

	result, err := GetFraudEventsByUser(db, "user-1", 10)
	require.NoError(t, err)
	assert.Equal(t, events, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCreateLocationTracking(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	now := time.Now()
	mock.ExpectExec(`INSERT INTO location_tracking`).
		WithArgs("user-1", "device", "dev-1", 10.5, 20.5, 5.5, now).
		WillReturnResult(sqlmock.NewResult(1, 1))

	tracking := &LocationTracking{
		UserID:     "user-1",
		EntityType: "device",
		EntityID:   "dev-1",
		Latitude:   10.5,
		Longitude:  20.5,
		Accuracy:   5.5,
		Timestamp:  now,
	}
	err = CreateLocationTracking(db, tracking)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestGetLatestLocationByUser_Success(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	now := time.Now()
	tracking := &LocationTracking{
		ID:         int64(1),
		UserID:     "user-1",
		EntityType: "device",
		EntityID:   "dev-1",
		Latitude:   10.5,
		Longitude:  20.5,
		Accuracy:   5.5,
		Timestamp:  now,
	}
	rows := sqlmock.NewRows([]string{"id", "user_id", "entity_type", "entity_id", "lat", "lon", "accuracy", "timestamp"}).
		AddRow(tracking.ID, tracking.UserID, tracking.EntityType, tracking.EntityID, tracking.Latitude, tracking.Longitude, tracking.Accuracy, tracking.Timestamp)

	mock.ExpectQuery(`SELECT .* FROM location_tracking WHERE user_id = \$1 ORDER BY timestamp DESC LIMIT 1`).WithArgs("user-1").WillReturnRows(rows)

	result, err := GetLatestLocationByUser(db, "user-1")
	require.NoError(t, err)
	assert.Equal(t, tracking, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCreateMarketplaceItem(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	now := time.Now()
	mock.ExpectExec(`INSERT INTO marketplace_items`).
		WithArgs("seller-1", "Electronics", "Phone", int64(100), "ACTIVE", "", now, now).
		WillReturnResult(sqlmock.NewResult(1, 1))

	item := &MarketplaceItem{
		SellerID:  "seller-1",
		Category:  "Electronics",
		Title:     "Phone",
		Price:     100,
		Status:    "ACTIVE",
		Metadata:  "",
		CreatedAt: now,
		UpdatedAt: now,
	}
	err = CreateMarketplaceItem(db, item)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestGetMarketplaceItemsBySeller_Success(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	now := time.Now()
	items := []*MarketplaceItem{
		{ID: int64(1), SellerID: "seller-1", Category: "Electronics", Title: "Phone", Price: 100, Status: "ACTIVE", Metadata: "", CreatedAt: now, UpdatedAt: now},
		{ID: int64(2), SellerID: "seller-1", Category: "Electronics", Title: "Laptop", Price: 200, Status: "ACTIVE", Metadata: "", CreatedAt: now, UpdatedAt: now},
	}
	rows := sqlmock.NewRows([]string{"id", "seller_id", "category", "title", "price", "status", "metadata", "created_at", "updated_at"}).
		AddRow(items[0].ID, items[0].SellerID, items[0].Category, items[0].Title, items[0].Price, items[0].Status, items[0].Metadata, items[0].CreatedAt, items[0].UpdatedAt).
		AddRow(items[1].ID, items[1].SellerID, items[1].Category, items[1].Title, items[1].Price, items[1].Status, items[1].Metadata, items[1].CreatedAt, items[1].UpdatedAt)

	mock.ExpectQuery(`SELECT .* FROM marketplace_items WHERE seller_id = \$1 ORDER BY created_at DESC LIMIT \$2`).WithArgs("seller-1", 10).WillReturnRows(rows)

	result, err := GetMarketplaceItemsBySeller(db, "seller-1", 10)
	require.NoError(t, err)
	assert.Equal(t, items, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCreateMarketplaceOrder(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	now := time.Now()
	mock.ExpectExec(`INSERT INTO marketplace_orders`).
		WithArgs("buyer-1", "seller-1", int64(1), "PENDING", "", now, now).
		WillReturnResult(sqlmock.NewResult(1, 1))

	order := &MarketplaceOrder{
		BuyerID:   "buyer-1",
		SellerID:  "seller-1",
		ItemID:    1,
		Status:    "PENDING",
		Metadata:  "",
		CreatedAt: now,
		UpdatedAt: now,
	}
	err = CreateMarketplaceOrder(db, order)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCreateLandRecord(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	now := time.Now()
	mock.ExpectExec(`INSERT INTO land_records`).
		WithArgs("owner-1", "PLOT-001", 100.5, "123 Main St", "RESIDENTIAL", "REGISTERED", now, now).
		WillReturnResult(sqlmock.NewResult(1, 1))

	record := &LandRecord{
		OwnerID:    "owner-1",
		PlotNumber: "PLOT-001",
		Area:       100.5,
		Address:    "123 Main St",
		Type:       "RESIDENTIAL",
		Status:     "REGISTERED",
		CreatedAt:  now,
		UpdatedAt:  now,
	}
	err = CreateLandRecord(db, record)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCreateLandPermit(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	now := time.Now()
	mock.ExpectExec(`INSERT INTO land_permits`).
		WithArgs("app-1", int64(1), "NEW", "PENDING", "", now, now).
		WillReturnResult(sqlmock.NewResult(1, 1))

	permit := &LandPermit{
		ApplicantID:  "app-1",
		LandRecordID: 1,
		PermitType:   "NEW",
		Status:       "PENDING",
		Data:         "",
		CreatedAt:    now,
		UpdatedAt:    now,
	}
	err = CreateLandPermit(db, permit)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestDeleteAuthSession(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	mock.ExpectExec(`DELETE FROM auth_sessions WHERE id = \$1`).WithArgs("sess-1").WillReturnResult(sqlmock.NewResult(1, 1))
	err = DeleteAuthSession(db, "sess-1")
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestGetEventsByType_Success(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	now := time.Now()
	events := []*EventStore{
		{ID: int64(1), EventType: "MessageSent", Payload: `{"msg":"hi"}`, CreatedAt: now},
		{ID: int64(2), EventType: "MessageSent", Payload: `{"msg":"bye"}`, CreatedAt: now},
	}
	rows := sqlmock.NewRows([]string{"id", "event_type", "payload", "created_at"}).
		AddRow(events[0].ID, events[0].EventType, events[0].Payload, events[0].CreatedAt).
		AddRow(events[1].ID, events[1].EventType, events[1].Payload, events[1].CreatedAt)

	mock.ExpectQuery(`SELECT .* FROM event_store WHERE event_type = \$1 ORDER BY created_at DESC LIMIT \$2`).WithArgs("MessageSent", 10).WillReturnRows(rows)

	result, err := GetEventsByType(db, "MessageSent", 10)
	require.NoError(t, err)
	assert.Equal(t, events, result)
	require.NoError(t, mock.ExpectationsWereMet())
}
