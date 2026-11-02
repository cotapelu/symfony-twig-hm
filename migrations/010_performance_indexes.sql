-- Performance Indexes Migration
-- Add indexes for frequently queried columns

-- Wallet tables (go schema)
CREATE INDEX IF NOT EXISTS idx_wallet_user_id ON go.banking_account(user_id);
CREATE INDEX IF NOT EXISTS idx_wallet_transaction_wallet_id ON go.banking_transaction(wallet_id);
CREATE INDEX IF NOT EXISTS idx_wallet_transaction_created ON go.banking_transaction(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_wallet_transaction_type ON go.banking_transaction(transaction_type);

-- Ecommerce indexes
CREATE INDEX IF NOT EXISTS idx_ecommerce_product_active_created ON php.ecommerce_product(is_active, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_ecommerce_product_category ON php.ecommerce_product(category);
CREATE INDEX IF NOT EXISTS idx_ecommerce_order_user_status ON php.ecommerce_order(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_ecommerce_order_created ON php.ecommerce_order(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_ecommerce_transaction_wallet_created ON php.ecommerce_transaction(order_id, created_at DESC);

-- Delivery indexes
CREATE INDEX IF NOT EXISTS idx_delivery_order_status ON php.delivery_order(status);
CREATE INDEX IF NOT EXISTS idx_delivery_order_assigned_courier ON php.delivery_order(assigned_courier_id);
CREATE INDEX IF NOT EXISTS idx_delivery_order_created ON php.delivery_order(created_at DESC);

-- Appointment/Service indexes
CREATE INDEX IF NOT EXISTS idx_service_booking_date_status ON php.service_booking(booking_date, status);
CREATE INDEX IF NOT EXISTS idx_service_booking_business_status ON php.service_booking(business_id, status);

-- Citizen incident indexes
CREATE INDEX IF NOT EXISTS idx_incident_status_created ON php.citizen_incident(status, reported_at DESC);
CREATE INDEX IF NOT EXISTS idx_incident_type ON php.citizen_incident(incident_type);

-- Notification indexes
CREATE INDEX IF NOT EXISTS idx_notification_user_read ON php.notification(user_id, read_at);
CREATE INDEX IF NOT EXISTS idx_notification_created ON php.notification(created_at DESC);

-- Common schema indexes
CREATE INDEX IF NOT EXISTS idx_events_created ON common.events(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_event_store_created ON common.event_store(created_at DESC);

-- Composite indexes for common query patterns
CREATE INDEX IF NOT EXISTS idx_wallet_user_date ON go.banking_transaction(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_ecommerce_user_orders ON php.ecommerce_order(customer_id, created_at DESC);