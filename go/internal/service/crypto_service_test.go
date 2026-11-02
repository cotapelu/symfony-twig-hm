package service

import (
	"context"
	"database/sql"
	"testing"
	"time"

	"community.local/internal/models"
)

type mockCryptoRepo struct {
	wallets map[string]*models.CryptoWallet
	txs     []*models.CryptoTransaction
}

func (m *mockCryptoRepo) CreateWallet(ctx context.Context, wallet *models.CryptoWallet) error {
	if m.wallets == nil {
		m.wallets = make(map[string]*models.CryptoWallet)
	}
	wallet.ID = "wallet-" + time.Now().Format("20060102150405")
	wallet.CreatedAt = time.Now()
	wallet.UpdatedAt = time.Now()
	m.wallets[wallet.ID] = wallet
	return nil
}

func (m *mockCryptoRepo) GetWallet(ctx context.Context, id string) (*models.CryptoWallet, error) {
	wallet, ok := m.wallets[id]
	if !ok {
		return nil, sql.ErrNoRows
	}
	return wallet, nil
}

func (m *mockCryptoRepo) GetWalletByUser(ctx context.Context, userID, currency string) (*models.CryptoWallet, error) {
	for _, w := range m.wallets {
		if w.UserID == userID && w.Currency == currency {
			return w, nil
		}
	}
	return nil, sql.ErrNoRows
}

func (m *mockCryptoRepo) UpdateBalance(ctx context.Context, id string, amount float64) error {
	wallet, ok := m.wallets[id]
	if !ok {
		return sql.ErrNoRows
	}
	wallet.Balance += amount
	wallet.UpdatedAt = time.Now()
	return nil
}

func (m *mockCryptoRepo) CreateTransaction(ctx context.Context, tx *models.CryptoTransaction) error {
	if m.txs == nil {
		m.txs = make([]*models.CryptoTransaction, 0)
	}
	tx.ID = "tx-" + time.Now().Format("20060102150405")
	tx.CreatedAt = time.Now()
	m.txs = append(m.txs, tx)
	return nil
}

func (m *mockCryptoRepo) GetTransactions(ctx context.Context, walletID string, limit, offset int) ([]*models.CryptoTransaction, error) {
	var results []*models.CryptoTransaction
	for _, tx := range m.txs {
		if tx.WalletID == walletID {
			results = append(results, tx)
		}
	}
	return results, nil
}

// CryptoService tests
func TestCrypto_CreateWallet(t *testing.T) {
	repo := &mockCryptoRepo{wallets: make(map[string]*models.CryptoWallet)}
	svc := NewCryptoService(repo)

	ctx := context.Background()
	wallet, err := svc.CreateWallet(ctx, "user-1", "BTC")
	if err != nil {
		t.Fatalf("CreateWallet failed: %v", err)
	}
	if wallet.Currency != "BTC" {
		t.Errorf("Expected currency BTC, got %s", wallet.Currency)
	}
	if wallet.Status != "ACTIVE" {
		t.Errorf("Expected default status ACTIVE, got %s", wallet.Status)
	}
}

func TestCrypto_CreateWallet_InvalidCurrency(t *testing.T) {
	repo := &mockCryptoRepo{wallets: make(map[string]*models.CryptoWallet)}
	svc := NewCryptoService(repo)

	ctx := context.Background()
	_, err := svc.CreateWallet(ctx, "user-1", "")
	if err == nil {
		t.Fatal("Expected error for empty currency")
	}
}

func TestCrypto_GetWallet_NotFound(t *testing.T) {
	repo := &mockCryptoRepo{wallets: make(map[string]*models.CryptoWallet)}
	svc := NewCryptoService(repo)

	ctx := context.Background()
	_, err := svc.GetWallet(ctx, "non-existent")
	if err == nil {
		t.Fatal("Expected error for non-existent wallet")
	}
}

func TestCrypto_Deposit_Success(t *testing.T) {
	wallet := &models.CryptoWallet{
		ID:       "wallet-1",
		UserID:   "user-1",
		Currency: "BTC",
		Balance:  1.5,
		Status:   "ACTIVE",
	}
	repo := &mockCryptoRepo{wallets: map[string]*models.CryptoWallet{"wallet-1": wallet}}
	svc := NewCryptoService(repo)

	ctx := context.Background()
	err := svc.Deposit(ctx, "wallet-1", 0.5, "tx-hash-123")
	if err != nil {
		t.Fatalf("Deposit failed: %v", err)
	}
	if wallet.Balance != 2.0 {
		t.Errorf("Expected balance 2.0, got %f", wallet.Balance)
	}
	if len(repo.txs) != 1 {
		t.Errorf("Expected 1 transaction, got %d", len(repo.txs))
	}
}

func TestCrypto_Withdraw_InsufficientBalance(t *testing.T) {
	wallet := &models.CryptoWallet{
		ID:       "wallet-1",
		UserID:   "user-1",
		Currency: "BTC",
		Balance:  0.1,
		Status:   "ACTIVE",
	}
	repo := &mockCryptoRepo{wallets: map[string]*models.CryptoWallet{"wallet-1": wallet}}
	svc := NewCryptoService(repo)

	ctx := context.Background()
	err := svc.Withdraw(ctx, "wallet-1", 1.0, "tx-hash")
	if err == nil {
		t.Fatal("Expected insufficient funds error")
	}
}

func TestCrypto_Transfer_Success(t *testing.T) {
	from := &models.CryptoWallet{ID: "w1", UserID: "u1", Currency: "BTC", Balance: 1.0, Status: "ACTIVE"}
	to := &models.CryptoWallet{ID: "w2", UserID: "u2", Currency: "BTC", Balance: 0.5, Status: "ACTIVE"}
	repo := &mockCryptoRepo{wallets: map[string]*models.CryptoWallet{
		"w1": from, "w2": to,
	}}
	svc := NewCryptoService(repo)

	ctx := context.Background()
	err := svc.Transfer(ctx, "w1", "w2", 0.3, "tx-hash")
	if err != nil {
		t.Fatalf("Transfer failed: %v", err)
	}
	if from.Balance != 0.7 {
		t.Errorf("From balance expected 0.7, got %f", from.Balance)
	}
	if to.Balance != 0.8 {
		t.Errorf("To balance expected 0.8, got %f", to.Balance)
	}
	if len(repo.txs) != 2 {
		t.Errorf("Expected 2 transactions, got %d", len(repo.txs))
	}
}

func TestCrypto_GetWalletByUserCurrency_Success(t *testing.T) {
	repo := &mockCryptoRepo{
		wallets: map[string]*models.CryptoWallet{
			"wallet-1": {ID: "wallet-1", UserID: "user-1", Currency: "BTC", Balance: 10, Status: "ACTIVE"},
		},
	}
	svc := NewCryptoService(repo)
	ctx := context.Background()

	wallet, err := svc.GetWalletByUserCurrency(ctx, "user-1", "BTC")
	if err != nil {
		t.Fatalf("GetWalletByUserCurrency failed: %v", err)
	}
	if wallet.Currency != "BTC" {
		t.Errorf("Expected currency BTC, got %s", wallet.Currency)
	}
}

func TestCrypto_GetWalletByUserCurrency_NotFound(t *testing.T) {
	repo := &mockCryptoRepo{
		wallets: map[string]*models.CryptoWallet{
			"wallet-1": {UserID: "user-1", Currency: "BTC"},
		},
	}
	svc := NewCryptoService(repo)
	ctx := context.Background()

	_, err := svc.GetWalletByUserCurrency(ctx, "user-1", "ETH")
	if err == nil {
		t.Fatal("Expected error, got nil")
	}
	// Should be "wallet not found" not sql.ErrNoRows
}

func TestCrypto_GetWalletByUserCurrency_Closed(t *testing.T) {
	repo := &mockCryptoRepo{
		wallets: map[string]*models.CryptoWallet{
			"wallet-1": {ID: "wallet-1", UserID: "user-1", Currency: "BTC", Status: "CLOSED"},
		},
	}
	svc := NewCryptoService(repo)
	ctx := context.Background()

	_, err := svc.GetWalletByUserCurrency(ctx, "user-1", "BTC")
	if err == nil {
		t.Fatal("Expected error for closed wallet, got nil")
	}
	if err.Error() != "wallet is closed" {
		t.Errorf("Expected 'wallet is closed', got %s", err.Error())
	}
}

func TestCrypto_Withdraw_Success(t *testing.T) {
	repo := &mockCryptoRepo{
		wallets: map[string]*models.CryptoWallet{
			"wallet-1": {ID: "wallet-1", UserID: "user-1", Currency: "BTC", Balance: 100, Status: "ACTIVE"},
		},
		txs: []*models.CryptoTransaction{},
	}
	svc := NewCryptoService(repo)
	ctx := context.Background()

	err := svc.Withdraw(ctx, "wallet-1", 50, "tx-withdraw-1")
	if err != nil {
		t.Fatalf("Withdraw failed: %v", err)
	}
	// Verify balance decreased
	wallet := repo.wallets["wallet-1"]
	if wallet.Balance != 50 {
		t.Errorf("Expected balance 50, got %f", wallet.Balance)
	}
	// Verify transaction created
	if len(repo.txs) != 1 {
		t.Errorf("Expected 1 transaction, got %d", len(repo.txs))
	} else if repo.txs[0].Type != "WITHDRAW" || repo.txs[0].Amount != 50 {
		t.Errorf("Transaction not as expected: %+v", repo.txs[0])
	}
}

func TestCrypto_Transfer_InsufficientBalance(t *testing.T) {
	from := &models.CryptoWallet{ID: "w1", UserID: "u1", Currency: "BTC", Balance: 0.2, Status: "ACTIVE"}
	to := &models.CryptoWallet{ID: "w2", UserID: "u2", Currency: "BTC", Balance: 0.5, Status: "ACTIVE"}
	repo := &mockCryptoRepo{wallets: map[string]*models.CryptoWallet{
		"w1": from, "w2": to,
	}}
	svc := NewCryptoService(repo)
	ctx := context.Background()

	err := svc.Transfer(ctx, "w1", "w2", 0.3, "tx-hash")
	if err == nil {
		t.Fatal("Expected error for insufficient balance")
	}
	if err.Error() != "insufficient funds" {
		t.Errorf("Expected 'insufficient funds', got %s", err.Error())
	}
}

func TestCrypto_Transfer_SameWallet(t *testing.T) {
	from := &models.CryptoWallet{ID: "w1", UserID: "u1", Currency: "BTC", Balance: 1.0, Status: "ACTIVE"}
	repo := &mockCryptoRepo{wallets: map[string]*models.CryptoWallet{
		"w1": from,
	}}
	svc := NewCryptoService(repo)
	ctx := context.Background()

	err := svc.Transfer(ctx, "w1", "w1", 0.1, "tx-hash")
	if err == nil {
		t.Fatal("Expected error for transfer to same wallet")
	}
	if err.Error() != "cannot transfer to same wallet" {
		t.Errorf("Expected 'cannot transfer to same wallet', got %s", err.Error())
	}
}
