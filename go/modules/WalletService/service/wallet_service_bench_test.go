//go:build ignore
package walletservice

import (
	"context"
	"database/sql"
	"testing"

	_ "github.com/lib/pq"
	walletmodels "community.local/modules/WalletService/internal/models"
)

// setupBenchmarkDB creates an in-memory SQLite database with the required schema.
// It accepts any testing.TB (testing.T or testing.B) for flexibility.
func setupBenchmarkDB(tb testing.TB) (*sql.DB, walletmodels.WalletRepositoryInterface, walletmodels.TransactionRepositoryInterface) {
	db, err := sql.Open("sqlite3", ":memory:")
	if err != nil {
		tb.Fatal(err)
	}
	// Create wallets table with version column
	_, err = db.Exec(`CREATE TABLE wallets (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		user_id TEXT NOT NULL,
		balance REAL NOT NULL DEFAULT 0,
		currency TEXT NOT NULL DEFAULT 'VND',
		status TEXT NOT NULL DEFAULT 'active',
		is_default BOOLEAN NOT NULL DEFAULT false,
		version INTEGER NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
	);`)
	if err != nil {
		tb.Fatal(err)
	}
	// Create wallet_transactions table
	_, err = db.Exec(`CREATE TABLE wallet_transactions (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		wallet_id INTEGER NOT NULL REFERENCES wallets(id),
		type TEXT NOT NULL,
		amount REAL NOT NULL,
		balance_after REAL NOT NULL,
		description TEXT,
		reference_id TEXT UNIQUE,
		metadata TEXT,
		status TEXT NOT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
	);`)
	if err != nil {
		tb.Fatal(err)
	}
	walletRepo := walletmodels.NewWalletRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	return db, walletRepo, txnRepo
}

func BenchmarkGetBalance(b *testing.B) {
	_, walletRepo, txnRepo := setupBenchmarkDB(b)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	ctx := context.Background()
	wallet := &walletmodels.Wallet{
		UserID:    "bench-user",
		Balance:   1000000,
		Currency:  "VND",
		Status:    "active",
		IsDefault: true,
	}
	if err := walletRepo.CreateWallet(ctx, wallet); err != nil {
		b.Fatal(err)
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		bal, err := svc.GetBalance(ctx, wallet.UserID)
		if err != nil {
			b.Fatal(err)
		}
		_ = bal
	}
}

func BenchmarkTransferConcurrent(b *testing.B) {
	_, walletRepo, txnRepo := setupBenchmarkDB(b)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	ctx := context.Background()
	w1 := &walletmodels.Wallet{
		UserID:    "u1",
		Balance:   100000,
		Currency:  "VND",
		Status:    "active",
		IsDefault: true,
	}
	w2 := &walletmodels.Wallet{
		UserID:    "u2",
		Balance:   100000,
		Currency:  "VND",
		Status:    "active",
		IsDefault: true,
	}
	if err := walletRepo.CreateWallet(ctx, w1); err != nil {
		b.Fatal(err)
	}
	if err := walletRepo.CreateWallet(ctx, w2); err != nil {
		b.Fatal(err)
	}

	amount := int64(10)

	b.ResetTimer()
	b.RunParallel(func(pb *testing.PB) {
		for pb.Next() {
			_, err := svc.Transfer(ctx, &walletmodels.TransferRequest{
				FromUserID: w1.UserID,
				ToUserID:   w2.UserID,
				Amount:     amount,
			})
			if err != nil {
				b.Fatal(err)
			}
		}
	})
}
