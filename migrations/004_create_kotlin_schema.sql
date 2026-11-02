-- Migration: Create Kotlin Schema (Fixed)
-- Description: Create kotlin schema with exactly 8 system & monitoring tables
-- Tables: payments, payment_intents, push_queue, email_queue,
--         system_metrics, alerts, file_metadata, poll_surveys

-- Create kotlin schema
CREATE SCHEMA IF NOT EXISTS kotlin;
SET search_path TO kotlin;

-- Payment tables
CREATE TABLE IF NOT EXISTS payment (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    amount DECIMAL(20,2) NOT NULL,
    currency VARCHAR(10) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    transaction_id VARCHAR(255),
    error_message TEXT,
    refund_of UUID REFERENCES kotlin.payment(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_payment_user_id ON kotlin.payment(user_id);
CREATE INDEX IF NOT EXISTS idx_payment_status ON kotlin.payment(status);
CREATE INDEX IF NOT EXISTS idx_payment_created_at ON kotlin.payment(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_payment_refund_of ON kotlin.payment(refund_of);

CREATE TABLE IF NOT EXISTS payment_intent (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    amount DECIMAL(20,2) NOT NULL,
    currency VARCHAR(10) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'created',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_payment_intent_user_id ON kotlin.payment_intent(user_id);
CREATE INDEX IF NOT EXISTS idx_payment_intent_status ON kotlin.payment_intent(status);
CREATE INDEX IF NOT EXISTS idx_payment_intent_created_at ON kotlin.payment_intent(created_at DESC);

-- Queue tables
CREATE TABLE IF NOT EXISTS push_queue (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    payload JSONB,
    priority VARCHAR(50) DEFAULT 'normal',
    status VARCHAR(50) DEFAULT 'pending',
    scheduled_at TIMESTAMP,
    sent_at TIMESTAMP,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_push_queue_user_id ON kotlin.push_queue(user_id);
CREATE INDEX IF NOT EXISTS idx_push_queue_status ON kotlin.push_queue(status);
CREATE INDEX IF NOT EXISTS idx_push_queue_priority ON kotlin.push_queue(priority);
CREATE INDEX IF NOT EXISTS idx_push_queue_scheduled_at ON kotlin.push_queue(scheduled_at);
CREATE INDEX IF NOT EXISTS idx_push_queue_created_at ON kotlin.push_queue(created_at DESC);

CREATE TABLE IF NOT EXISTS email_queue (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    html_body TEXT,
    priority VARCHAR(50) DEFAULT 'normal',
    status VARCHAR(50) DEFAULT 'pending',
    scheduled_at TIMESTAMP,
    sent_at TIMESTAMP,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_email_queue_user_id ON kotlin.email_queue(user_id);
CREATE INDEX IF NOT EXISTS idx_email_queue_status ON kotlin.email_queue(status);
CREATE INDEX IF NOT EXISTS idx_email_queue_priority ON kotlin.email_queue(priority);
CREATE INDEX IF NOT EXISTS idx_email_queue_scheduled_at ON kotlin.email_queue(scheduled_at);
CREATE INDEX IF NOT EXISTS idx_email_queue_created_at ON kotlin.email_queue(created_at DESC);

-- Monitoring tables
CREATE TABLE IF NOT EXISTS system_metric (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    metric_name VARCHAR(255) NOT NULL,
    metric_value DECIMAL(20,2) NOT NULL,
    metric_type VARCHAR(50) NOT NULL,
    tags JSONB,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_system_metric_name ON kotlin.system_metric(metric_name);
CREATE INDEX IF NOT EXISTS idx_system_metric_type ON kotlin.system_metric(metric_type);
CREATE INDEX IF NOT EXISTS idx_system_metric_timestamp ON kotlin.system_metric(timestamp DESC);

CREATE TABLE IF NOT EXISTS alert (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    alert_name VARCHAR(255) NOT NULL,
    alert_type VARCHAR(100) NOT NULL,
    severity VARCHAR(50) NOT NULL,
    condition TEXT NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'active',
    enabled BOOLEAN DEFAULT true,
    triggered_at TIMESTAMP,
    resolved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_alert_type ON kotlin.alert(alert_type);
CREATE INDEX IF NOT EXISTS idx_alert_severity ON kotlin.alert(severity);
CREATE INDEX IF NOT EXISTS idx_alert_status ON kotlin.alert(status);
CREATE INDEX IF NOT EXISTS idx_alert_enabled ON kotlin.alert(enabled);
CREATE INDEX IF NOT EXISTS idx_alert_triggered_at ON kotlin.alert(triggered_at DESC);

-- File table
CREATE TABLE IF NOT EXISTS file_metadata (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR(100),
    file_path TEXT NOT NULL,
    storage_provider VARCHAR(50) DEFAULT 'local',
    status VARCHAR(50) DEFAULT 'uploaded',
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_file_metadata_user_id ON kotlin.file_metadata(user_id);
CREATE INDEX IF NOT EXISTS idx_file_metadata_status ON kotlin.file_metadata(status);
CREATE INDEX IF NOT EXISTS idx_file_metadata_created_at ON kotlin.file_metadata(created_at DESC);

-- Poll table
CREATE TABLE IF NOT EXISTS poll_survey (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    poll_type VARCHAR(50) DEFAULT 'survey',
    status VARCHAR(50) DEFAULT 'draft',
    questions JSONB NOT NULL,
    responses JSONB DEFAULT '[]',
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_poll_survey_user_id ON kotlin.poll_survey(user_id);
CREATE INDEX IF NOT EXISTS idx_poll_survey_type ON kotlin.poll_survey(poll_type);
CREATE INDEX IF NOT EXISTS idx_poll_survey_status ON kotlin.poll_survey(status);
CREATE INDEX IF NOT EXISTS idx_poll_survey_created_at ON kotlin.poll_survey(created_at DESC);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION kotlin.update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_payment_updated_at BEFORE UPDATE ON payment
    FOR EACH ROW EXECUTE FUNCTION kotlin.update_updated_at_column();

CREATE TRIGGER update_payment_intent_updated_at BEFORE UPDATE ON payment_intent
    FOR EACH ROW EXECUTE FUNCTION kotlin.update_updated_at_column();

CREATE TRIGGER update_push_queue_updated_at BEFORE UPDATE ON push_queue
    FOR EACH ROW EXECUTE FUNCTION kotlin.update_updated_at_column();

CREATE TRIGGER update_email_queue_updated_at BEFORE UPDATE ON email_queue
    FOR EACH ROW EXECUTE FUNCTION kotlin.update_updated_at_column();

CREATE TRIGGER update_alert_updated_at BEFORE UPDATE ON alert
    FOR EACH ROW EXECUTE FUNCTION kotlin.update_updated_at_column();

CREATE TRIGGER update_file_metadata_updated_at BEFORE UPDATE ON file_metadata
    FOR EACH ROW EXECUTE FUNCTION kotlin.update_updated_at_column();

CREATE TRIGGER update_poll_survey_updated_at BEFORE UPDATE ON poll_survey
    FOR EACH ROW EXECUTE FUNCTION kotlin.update_updated_at_column();
