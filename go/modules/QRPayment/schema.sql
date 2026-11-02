-- QR Payment Schema
-- Supports both PostgreSQL and SQLite (with minor adjustments)

-- QR Codes table
CREATE TABLE IF NOT EXISTS qr_codes (
    id SERIAL PRIMARY KEY,
    code VARCHAR(255) UNIQUE NOT NULL,
    code_type VARCHAR(50) NOT NULL,
    merchant_id VARCHAR(255) NOT NULL,
    merchant_name VARCHAR(255) NOT NULL,
    amount NUMERIC NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'VND',
    description TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- QR Payments table
CREATE TABLE IF NOT EXISTS qr_payments (
    id SERIAL PRIMARY KEY,
    qr_code_id INT NOT NULL REFERENCES qr_codes(id),
    payer_id VARCHAR(255) NOT NULL,
    amount NUMERIC NOT NULL,
    fee NUMERIC NOT NULL,
    net_amount NUMERIC NOT NULL,
    currency VARCHAR(3) NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    reference_id VARCHAR(255) UNIQUE NOT NULL,
    bank_code VARCHAR(50),
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    completed_at TIMESTAMP
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_qr_codes_code ON qr_codes(code);
CREATE INDEX IF NOT EXISTS idx_qr_codes_merchant ON qr_codes(merchant_id);
CREATE INDEX IF NOT EXISTS idx_qr_payments_payer ON qr_payments(payer_id);
CREATE INDEX IF NOT EXISTS idx_qr_payments_reference ON qr_payments(reference_id);
CREATE INDEX IF NOT EXISTS idx_qr_payments_created_at ON qr_payments(created_at DESC);
