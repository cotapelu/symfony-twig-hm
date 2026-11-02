package repository

import (
	"context"
	"fmt"
	"sync"
	"time"

	walletservice "community.local/modules/WalletService/internal/models"
)

type InMemoryWalletRepository struct {
	mu      sync.RWMutex
	wallets map[int64]*walletservice.Wallet
	nextID  int64
}

func NewInMemoryWalletRepository() *InMemoryWalletRepository {
	return &InMemoryWalletRepository{
		wallets: make(map[int64]*walletservice.Wallet),
		nextID:  1,
	}
}

func (r *InMemoryWalletRepository) CreateWallet(ctx context.Context, w *walletservice.Wallet) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	w.ID = r.nextID
	w.CreatedAt = time.Now()
	w.UpdatedAt = time.Now()
	r.nextID++

	r.wallets[w.ID] = w
	return nil
}

func (r *InMemoryWalletRepository) GetWallet(ctx context.Context, id int64) (*walletservice.Wallet, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	return r.wallets[id], nil
}

func (r *InMemoryWalletRepository) GetWalletByUser(ctx context.Context, userID string) (*walletservice.Wallet, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	for _, w := range r.wallets {
		if w.UserID == userID && w.Status == "active" {
			return w, nil
		}
	}
	return nil, nil
}

func (r *InMemoryWalletRepository) GetWalletForUpdate(ctx context.Context, userID string) (*walletservice.Wallet, error) {
	r.mu.Lock()
	defer r.mu.Unlock()

	for _, w := range r.wallets {
		if w.UserID == userID && w.Status == "active" {
			return w, nil
		}
	}
	return nil, nil
}

func (r *InMemoryWalletRepository) UpdateBalance(ctx context.Context, id int64, amount int64) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	wallet, exists := r.wallets[id]
	if !exists {
		return fmt.Errorf("wallet not found")
	}
	wallet.Balance += amount
	wallet.UpdatedAt = time.Now()
	return nil
}

func (r *InMemoryWalletRepository) HasSufficientBalance(ctx context.Context, userID string, amount int64) (bool, error) {
	wallet, err := r.GetWalletByUser(ctx, userID)
	if err != nil {
		return false, err
	}
	return wallet != nil && wallet.Balance >= amount, nil
}

type InMemoryTransactionRepository struct {
	mu           sync.RWMutex
	transactions map[int64]*walletservice.WalletTransaction
	walletIdx    map[int64][]int64
	nextID       int64
}

func NewInMemoryTransactionRepository() *InMemoryTransactionRepository {
	return &InMemoryTransactionRepository{
		transactions: make(map[int64]*walletservice.WalletTransaction),
		walletIdx:    make(map[int64][]int64),
		nextID:       1,
	}
}

func (r *InMemoryTransactionRepository) CreateTransaction(ctx context.Context, t *walletservice.WalletTransaction) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	t.ID = r.nextID
	t.CreatedAt = time.Now()
	r.nextID++

	r.transactions[t.ID] = t
	r.walletIdx[t.WalletID] = append(r.walletIdx[t.WalletID], t.ID)
	return nil
}

func (r *InMemoryTransactionRepository) GetByWallet(ctx context.Context, walletID int64, limit, offset int) ([]*walletservice.WalletTransaction, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	txnIDs := r.walletIdx[walletID]
	if offset >= len(txnIDs) {
		return nil, nil
	}

	end := offset + limit
	if end > len(txnIDs) {
		end = len(txnIDs)
	}

	result := make([]*walletservice.WalletTransaction, 0, end-offset)
	for i := offset; i < end; i++ {
		if txn, ok := r.transactions[txnIDs[i]]; ok {
			result = append(result, txn)
		}
	}
	return result, nil
}

func (r *InMemoryTransactionRepository) GetByReference(ctx context.Context, refID string) (*walletservice.WalletTransaction, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	for _, t := range r.transactions {
		if t.ReferenceID == refID {
			return t, nil
		}
	}
	return nil, nil
}
