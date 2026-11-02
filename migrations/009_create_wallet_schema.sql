-- Migration: Create Wallet Schema
-- Description: Create wallet service tables with integer cents and optimistic locking
-- Created: 2025-05-05
-- Author: Security & Financial Integrity Fix

-- Create wallet schema if not using separate schema; using 'go' schema per project convention
SET search_path TO go;

-- Wallet table with version for optimistic locking
CREATE TABLE IF NOT EXISTS wallets (
    id BIGSERIAL PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    balance BIGINT NOT NULL DEFAULT 0, -- balance in cents (integer)
    currency VARCHAR(10) NOT NULL DEFAULT 'VND',
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    is_default BOOLEAN NOT NULL DEFAULT true,
    version BIGINT NOT NULL DEFAULT 0, -- optimistic locking version
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_wallets_user_id ON go.wallets(user_id);
CREATE INDEX idx_wallets_status ON go.wallets(status);

-- Wallet balance materialized view (optional) could be used for reporting
-- Not needed; we use direct table reads

-- Wallet transaction table
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id BIGSERIAL PRIMARY KEY,
    wallet_id BIGINT NOT NULL REFERENCES go.wallets(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL, -- 'topup', 'transfer_in', 'transfer_out', 'withdraw', etc.
    amount BIGINT NOT NULL, -- amount in cents (negative for debits)
    balance_after BIGINT NOT NULL, -- wallet balance after this transaction (in cents)
    description TEXT,
    reference_id VARCHAR(255) UNIQUE, -- idempotency key for deduplication
    metadata JSONB,
    status VARCHAR(50) NOT NULL DEFAULT 'completed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_wallet_transactions_wallet_id ON go.wallet_transactions(wallet_id);
CREATE INDEX idx_wallet_transactions_created_at ON go.wallet_transactions(created_at DESC);
CREATE INDEX idx_wallet_transactions_reference_id ON go.wallet_transactions(reference_id) WHERE reference_id IS NOT NULL;

-- Trigger to update updated_at on wallets
CREATE OR REPLACE FUNCTION update_wallet_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE TRIGGER trigger_update_wallet_timestamp
    BEFORE UPDATE ON wallets
    FOR EACH ROW
    EXECUTE FUNCTION update_wallet_updated_at();

-- Enable row-level security if needed (optional)
-- ALTER TABLE wallets ENABLE ROW LEVEL SECURITY;
