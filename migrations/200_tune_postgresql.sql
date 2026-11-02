-- Performance Tuning for PostgreSQL
-- Add covering indexes, partition large tables, and schedule maintenance

-- 1. Covering indexes for frequent queries

-- Wallet queries: by user_id and created_at
CREATE INDEX IF NOT EXISTS idx_wallet_transactions_user_created ON wallet_transactions (user_id, created_at DESC) INCLUDE (type, amount, balance_after);

-- Notifications: by user_id and status
CREATE INDEX IF NOT EXISTS idx_notifications_user_status ON push_notifications (user_id, status) INCLUDE (title, body, created_at);

-- Messages: by conversation_id and created_at
CREATE INDEX IF NOT EXISTS idx_message_conversation_created ON message (conversation_id, created_at DESC) INCLUDE (sender_id, type);

-- Search history: by user_id and searched_at
CREATE INDEX IF NOT EXISTS idx_search_history_user_searched ON search_history (user_id, searched_at DESC) INCLUDE (query);

-- 2. Partition large tables: example for 'wallet_transactions' by month (if >10M rows)
-- This is a template; adapt to your data volume.
-- CREATE TABLE wallet_transactions_y2025m05 PARTITION OF wallet_transactions
-- FOR VALUES FROM ('2025-05-01') TO ('2025-06-01');

-- 3. Autovacuum tuning (app-level, edit postgresql.conf or via ALTER SYSTEM)
-- These can be set in the PostgreSQL container or via ConfigMap.
-- shared_buffers = 256MB
-- effective_cache_size = 1GB
-- maintenance_work_mem = 64MB
-- checkpoint_completion_target = 0.9
-- wal_buffers = 16MB
-- default_statistics_target = 100

-- 4. Schedule vacuum/analyze using pg_cron (if installed) or external cron.
-- Example: nightly vacuum analyze of modified tables
-- SELECT cron.schedule('nightly-vacuum', '0 2 * * *', 'VACUUM ANALYZE');

-- Alternatively, use a sidecar container that runs:
-- psql -U postgres -c "VACUUM ANALYZE wallet_transactions, push_notifications, message;"
