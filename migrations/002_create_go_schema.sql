-- Migration: Create Go Schema (Fixed)
-- Description: Create go schema with exactly 10 financial services tables
-- Tables: banking_accounts, banking_transactions, crypto_wallets, crypto_transactions,
--         fraud_events, location_tracking, marketplace_items, marketplace_orders,
--         land_records, land_permits

-- Create go schema
CREATE SCHEMA IF NOT EXISTS go;
SET search_path TO go;

-- Banking tables
CREATE TABLE IF NOT EXISTS banking_account (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    account_type VARCHAR(50) NOT NULL,
    currency VARCHAR(10) NOT NULL,
    balance DECIMAL(20,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_banking_account_user_id ON go.banking_account(user_id);
CREATE INDEX IF NOT EXISTS idx_banking_account_status ON go.banking_account(status);

CREATE TABLE IF NOT EXISTS banking_transaction (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    account_id UUID NOT NULL REFERENCES go.banking_account(id) ON DELETE CASCADE,
    transaction_type VARCHAR(50) NOT NULL,
    amount DECIMAL(20,2) NOT NULL,
    balance DECIMAL(20,2) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_banking_transaction_account_id ON go.banking_transaction(account_id);
CREATE INDEX IF NOT EXISTS idx_banking_transaction_type ON go.banking_transaction(transaction_type);
CREATE INDEX IF NOT EXISTS idx_banking_transaction_created_at ON go.banking_transaction(created_at DESC);

-- Crypto tables
CREATE TABLE IF NOT EXISTS crypto_wallet (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    wallet_type VARCHAR(50) NOT NULL,
    currency VARCHAR(10) NOT NULL,
    balance DECIMAL(20,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_crypto_wallet_user_id ON go.crypto_wallet(user_id);
CREATE INDEX IF NOT EXISTS idx_crypto_wallet_status ON go.crypto_wallet(status);

CREATE TABLE IF NOT EXISTS crypto_transaction (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    wallet_id UUID NOT NULL REFERENCES go.crypto_wallet(id) ON DELETE CASCADE,
    transaction_type VARCHAR(50) NOT NULL,
    amount DECIMAL(20,2) NOT NULL,
    balance DECIMAL(20,2) NOT NULL,
    tx_hash VARCHAR(255),
    address VARCHAR(255),
    description TEXT,
    status VARCHAR(50) DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_crypto_transaction_wallet_id ON go.crypto_transaction(wallet_id);
CREATE INDEX IF NOT EXISTS idx_crypto_transaction_type ON go.crypto_transaction(transaction_type);
CREATE INDEX IF NOT EXISTS idx_crypto_transaction_created_at ON go.crypto_transaction(created_at DESC);

-- Fraud events
CREATE TABLE IF NOT EXISTS fraud_event (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    severity VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    metadata JSONB,
    status VARCHAR(50) DEFAULT 'open',
    assigned_to UUID,
    resolved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_fraud_event_user_id ON go.fraud_event(user_id);
CREATE INDEX IF NOT EXISTS idx_fraud_event_status ON go.fraud_event(status);
CREATE INDEX IF NOT EXISTS idx_fraud_event_severity ON go.fraud_event(severity);
CREATE INDEX IF NOT EXISTS idx_fraud_event_created_at ON go.fraud_event(created_at DESC);

-- Location tracking
CREATE TABLE IF NOT EXISTS location_tracking (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    accuracy DECIMAL(10,2),
    altitude DECIMAL(10,2),
    speed DECIMAL(10,2),
    heading DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_location_tracking_user_id ON go.location_tracking(user_id);
CREATE INDEX IF NOT EXISTS idx_location_tracking_created_at ON go.location_tracking(created_at DESC);

-- Marketplace
CREATE TABLE IF NOT EXISTS marketplace_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    seller_id UUID NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    price DECIMAL(20,2) NOT NULL,
    compare_price DECIMAL(20,2),
    cost_price DECIMAL(20,2),
    stock_quantity INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    views INTEGER DEFAULT 0,
    sold_quantity INTEGER DEFAULT 0,
    rating DECIMAL(3,2),
    review_count INTEGER DEFAULT 0,
    images JSONB,
    attributes JSONB,
    location JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_marketplace_item_seller_id ON go.marketplace_item(seller_id);
CREATE INDEX IF NOT EXISTS idx_marketplace_item_category ON go.marketplace_item(category);
CREATE INDEX IF NOT EXISTS idx_marketplace_item_active ON go.marketplace_item(is_active);
CREATE INDEX IF NOT EXISTS idx_marketplace_item_price ON go.marketplace_item(price);
CREATE INDEX IF NOT EXISTS idx_marketplace_item_rating ON go.marketplace_item(rating);

CREATE TABLE IF NOT EXISTS marketplace_order (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_number VARCHAR(100) UNIQUE NOT NULL,
    buyer_id UUID NOT NULL,
    item_id UUID NOT NULL REFERENCES go.marketplace_item(id),
    quantity INTEGER NOT NULL DEFAULT 1,
    unit_price DECIMAL(20,2) NOT NULL,
    total_amount DECIMAL(20,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    shipping_address JSONB NOT NULL,
    payment_method VARCHAR(100),
    payment_status VARCHAR(50) DEFAULT 'unpaid',
    tracking_number VARCHAR(255),
    notes TEXT,
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP,
    shipped_at TIMESTAMP,
    delivered_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_marketplace_order_buyer_id ON go.marketplace_order(buyer_id);
CREATE INDEX IF NOT EXISTS idx_marketplace_order_item_id ON go.marketplace_order(item_id);
CREATE INDEX IF NOT EXISTS idx_marketplace_order_status ON go.marketplace_order(status);
CREATE INDEX IF NOT EXISTS idx_marketplace_order_ordered_at ON go.marketplace_order(ordered_at DESC);
CREATE INDEX IF NOT EXISTS idx_marketplace_order_number ON go.marketplace_order(order_number);

-- Land records
CREATE TABLE IF NOT EXISTS land_record (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    owner_id UUID NOT NULL,
    parcel_number VARCHAR(100) UNIQUE NOT NULL,
    title_number VARCHAR(100),
    address TEXT NOT NULL,
    area DECIMAL(20,2) NOT NULL,
    land_use VARCHAR(100),
    zoning VARCHAR(100),
    valuation DECIMAL(20,2),
    coordinates JSONB,
    document_urls JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_land_record_owner_id ON go.land_record(owner_id);
CREATE INDEX IF NOT EXISTS idx_land_record_parcel_number ON go.land_record(parcel_number);
CREATE INDEX IF NOT EXISTS idx_land_record_title_number ON go.land_record(title_number);

CREATE TABLE IF NOT EXISTS land_permit (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    land_record_id UUID NOT NULL REFERENCES go.land_record(id) ON DELETE CASCADE,
    permit_type VARCHAR(100) NOT NULL,
    permit_number VARCHAR(100) UNIQUE NOT NULL,
    issuing_authority VARCHAR(255) NOT NULL,
    issued_date DATE NOT NULL,
    expiry_date DATE,
    conditions TEXT,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_land_permit_land_record_id ON go.land_permit(land_record_id);
CREATE INDEX IF NOT EXISTS idx_land_permit_permit_number ON go.land_permit(permit_number);
CREATE INDEX IF NOT EXISTS idx_land_permit_status ON go.land_permit(status);
CREATE INDEX IF NOT EXISTS idx_land_permit_expiry_date ON go.land_permit(expiry_date);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION go.update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_banking_account_updated_at BEFORE UPDATE ON banking_account
    FOR EACH ROW EXECUTE FUNCTION go.update_updated_at_column();

CREATE TRIGGER update_crypto_wallet_updated_at BEFORE UPDATE ON crypto_wallet
    FOR EACH ROW EXECUTE FUNCTION go.update_updated_at_column();

CREATE TRIGGER update_fraud_event_updated_at BEFORE UPDATE ON fraud_event
    FOR EACH ROW EXECUTE FUNCTION go.update_updated_at_column();

CREATE TRIGGER update_marketplace_item_updated_at BEFORE UPDATE ON marketplace_item
    FOR EACH ROW EXECUTE FUNCTION go.update_updated_at_column();

CREATE TRIGGER update_marketplace_order_updated_at BEFORE UPDATE ON marketplace_order
    FOR EACH ROW EXECUTE FUNCTION go.update_updated_at_column();

CREATE TRIGGER update_land_record_updated_at BEFORE UPDATE ON land_record
    FOR EACH ROW EXECUTE FUNCTION go.update_updated_at_column();

CREATE TRIGGER update_land_permit_updated_at BEFORE UPDATE ON land_permit
    FOR EACH ROW EXECUTE FUNCTION go.update_updated_at_column();
