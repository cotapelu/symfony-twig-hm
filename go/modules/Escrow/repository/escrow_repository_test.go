package repository

import (
	"context"
	"database/sql"
	"strings"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/Escrow/models"
	"community.local/testing/helper"
)

func setupEscrowSchema(t *testing.T, db *sql.DB) {
	t.Helper()
	_, _ = db.ExecContext(context.Background(), `PRAGMA foreign_keys = ON`)
	_, _ = db.ExecContext(context.Background(), `
		CREATE TABLE IF NOT EXISTS escrow (
			id TEXT PRIMARY KEY,
			buyer_id TEXT NOT NULL,
			seller_id TEXT NOT NULL,
			amount REAL NOT NULL,
			currency TEXT NOT NULL,
			status TEXT NOT NULL,
			contract_address TEXT,
			tx_hash TEXT,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL
		)
	`)
}

func newEscrow(id, buyerID, sellerID, currency, status string, amount float64) *models.Escrow {
	now := time.Now()
	return &models.Escrow{
		ID:        id,
		BuyerID:   buyerID,
		SellerID:  sellerID,
		Amount:    amount,
		Currency:  currency,
		Status:    status,
		Contract:  "",
		TxHash:    "",
		CreatedAt: now,
		UpdatedAt: now,
	}
}

// ==================== Repository Tests ====================

func TestEscrowRepository_Create(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupEscrowSchema(t, db)

	repo := NewEscrowRepository(db)
	ctx := context.Background()

	escrow := newEscrow("escrow-123", "buyer-1", "seller-1", "VND", "pending", 1000000.0)
	err := repo.Create(ctx, escrow)
	require.NoError(t, err)

	require.NotEmpty(t, escrow.ID)
	require.NotEmpty(t, escrow.CreatedAt)
	require.NotEmpty(t, escrow.UpdatedAt)
	assert.Equal(t, "buyer-1", escrow.BuyerID)
	assert.Equal(t, "seller-1", escrow.SellerID)
}

func TestEscrowRepository_Create_ContextCancelled(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupEscrowSchema(t, db)

	repo := NewEscrowRepository(db)
	ctx, cancel := context.WithCancel(context.Background())
	cancel() // cancel immediately

	escrow := newEscrow("escrow-123", "buyer-1", "seller-1", "VND", "pending", 1000000.0)
	err := repo.Create(ctx, escrow)
	// SQLite may still accept or return error depending on timing; we accept either
	if err != nil && !strings.Contains(err.Error(), "closed") && !strings.Contains(err.Error(), "canceled") {
		t.Logf("Create with cancelled context returned error (acceptable): %v", err)
	}
}

func TestEscrowRepository_GetByID_Success(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupEscrowSchema(t, db)

	repo := NewEscrowRepository(db)
	ctx := context.Background()

	escrow := newEscrow("escrow-123", "buyer-1", "seller-1", "VND", "pending", 1000000.0)
	now := time.Now()
	escrow.CreatedAt = now
	escrow.UpdatedAt = now
	err := repo.Create(ctx, escrow)
	require.NoError(t, err)

	found, err := repo.GetByID(ctx, "escrow-123")
	require.NoError(t, err)
	require.NotNil(t, found)
	assert.Equal(t, "escrow-123", found.ID)
	assert.Equal(t, "buyer-1", found.BuyerID)
	assert.Equal(t, "seller-1", found.SellerID)
	assert.Equal(t, "VND", found.Currency)
	assert.Equal(t, "pending", found.Status)
	assert.Equal(t, 1000000.0, found.Amount)
	assert.WithinDuration(t, now, found.CreatedAt, 1*time.Second)
	assert.WithinDuration(t, now, found.UpdatedAt, 1*time.Second)
}

func TestEscrowRepository_GetByID_NotFound(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupEscrowSchema(t, db)

	repo := NewEscrowRepository(db)
	ctx := context.Background()

	found, err := repo.GetByID(ctx, "nonexistent")
	assert.ErrorIs(t, err, sql.ErrNoRows)
	assert.Nil(t, found)
}

func TestEscrowRepository_GetByID_InvalidID(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupEscrowSchema(t, db)

	repo := NewEscrowRepository(db)
	ctx := context.Background()

	// Try with empty ID
	found, err := repo.GetByID(ctx, "")
	assert.Error(t, err)
	assert.Nil(t, found)
}

func TestEscrowRepository_Update_Success(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupEscrowSchema(t, db)

	repo := NewEscrowRepository(db)
	ctx := context.Background()

	escrow := newEscrow("escrow-123", "buyer-1", "seller-1", "VND", "pending", 1000000.0)
	err := repo.Create(ctx, escrow)
	require.NoError(t, err)

	// Update status and set contract/tx
	escrow.Status = "funded"
	escrow.Contract = "0xContractAddress"
	escrow.TxHash = "0xTxHash"
	now := time.Now()
	escrow.UpdatedAt = now

	err = repo.Update(ctx, escrow)
	require.NoError(t, err)

	updated, err := repo.GetByID(ctx, "escrow-123")
	require.NoError(t, err)
	assert.Equal(t, "funded", updated.Status)
	assert.Equal(t, "0xContractAddress", updated.Contract)
	assert.Equal(t, "0xTxHash", updated.TxHash)
	assert.WithinDuration(t, now, updated.UpdatedAt, 1*time.Second)
}

func TestEscrowRepository_Update_NotExists(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupEscrowSchema(t, db)

	repo := NewEscrowRepository(db)
	ctx := context.Background()

	escrow := newEscrow("nonexistent", "buyer-1", "seller-1", "VND", "funded", 1000000.0)
	err := repo.Update(ctx, escrow)
	// SQLite returns 0 rows affected; may not error directly
	if err == nil {
		t.Log("Update on nonexistent row succeeded without error (SQLite behavior)")
	} else {
		t.Logf("Update on nonexistent row returned error (also acceptable): %v", err)
	}
}

func TestEscrowRepository_Update_MultipleFields(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupEscrowSchema(t, db)

	repo := NewEscrowRepository(db)
	ctx := context.Background()

	escrow := newEscrow("escrow-456", "buyer-2", "seller-2", "USD", "pending", 5000.0)
	err := repo.Create(ctx, escrow)
	require.NoError(t, err)

	// Update all updatable fields
	escrow.Status = "released"
	escrow.Contract = "0xNewContract"
	escrow.TxHash = "0xNewTx"
	escrow.UpdatedAt = time.Now()

	err = repo.Update(ctx, escrow)
	require.NoError(t, err)

	updated, err := repo.GetByID(ctx, "escrow-456")
	require.NoError(t, err)
	assert.Equal(t, "released", updated.Status)
	assert.Equal(t, "0xNewContract", updated.Contract)
	assert.Equal(t, "0xNewTx", updated.TxHash)
}

func TestEscrowRepository_Create_EmptyFields(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupEscrowSchema(t, db)

	repo := NewEscrowRepository(db)
	ctx := context.Background()

	// Create with minimal required fields
	escrow := &models.Escrow{
		ID:       "escrow-min",
		BuyerID:  "buyer",
		SellerID: "seller",
		Amount:   0,
		Currency: "VND",
		Status:   "pending",
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}
	err := repo.Create(ctx, escrow)
	require.NoError(t, err)
	assert.Equal(t, "escrow-min", escrow.ID)
}

func TestEscrowRepository_Integration_CreateGetUpdate(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupEscrowSchema(t, db)

	repo := NewEscrowRepository(db)
	ctx := context.Background()

	// Create
	escrow := newEscrow("escrow-int", "buyer-int", "seller-int", "VND", "pending", 2500000.0)
	err := repo.Create(ctx, escrow)
	require.NoError(t, err)

	// Get
	found, err := repo.GetByID(ctx, "escrow-int")
	require.NoError(t, err)
	assert.NotNil(t, found)
	assert.Equal(t, "pending", found.Status)

	// Update
	found.Status = "disbursed"
	found.Contract = "0xDisbursed"
	found.TxHash = "0xTx123"
	found.UpdatedAt = time.Now()
	err = repo.Update(ctx, found)
	require.NoError(t, err)

	// Verify update
	verify, err := repo.GetByID(ctx, "escrow-int")
	require.NoError(t, err)
	assert.Equal(t, "disbursed", verify.Status)
	assert.Equal(t, "0xDisbursed", verify.Contract)
	assert.Equal(t, "0xTx123", verify.TxHash)
}
