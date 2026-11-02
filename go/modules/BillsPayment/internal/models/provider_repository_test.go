package billspayment

import (
	"context"
	"database/sql"
	"testing"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"

	"community.local/testing/helper"
)

func setupProvidersSchema(t *testing.T, db *sql.DB) {
	t.Helper()
	_, _ = db.Exec("PRAGMA foreign_keys = ON")

	_, err := db.Exec(`
		CREATE TABLE bill_providers (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT NOT NULL,
			code TEXT UNIQUE NOT NULL,
			category TEXT NOT NULL,
			processing_fee REAL NOT NULL,
			min_amount REAL NOT NULL,
			max_amount REAL NOT NULL,
			is_active BOOLEAN NOT NULL DEFAULT true
		);
	`)
	if err != nil {
		t.Fatalf("create providers failed: %v", err)
	}
}

func TestProviderRepository_GetAllAndByCategory(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupProvidersSchema(t, db)

	repo := NewProviderRepository(db)
	ctx := context.Background()

	// Insert providers
	providers := []struct {
		name, code, category string
		fee                  float64
	}{
		{" Electricity ", "ELEC", "utilities", 1000.0},
		{" Water ", "WAT", "utilities", 500.0},
		{" Internet ", "NET", "telecom", 2000.0},
	}
	for _, p := range providers {
		_, err := db.Exec(`INSERT INTO bill_providers (name, code, category, processing_fee, min_amount, max_amount, is_active) VALUES ($1,$2,$3,$4,$5,$6,$7)`,
			p.name, p.code, p.category, p.fee, 0, 1000000, true)
		if err != nil {
			t.Fatalf("insert provider failed: %v", err)
		}
	}

	// GetAll (only active)
	all, err := repo.GetAll(ctx)
	assert.NoError(t, err)
	assert.Len(t, all, 3)

	// GetByCategory utilities
	utils, err := repo.GetByCategory(ctx, "utilities")
	assert.NoError(t, err)
	assert.Len(t, utils, 2) // electricity and water
	for _, p := range utils {
		assert.Equal(t, "utilities", p.Category)
	}
}
