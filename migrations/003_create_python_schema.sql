-- Migration: Create Python Schema (Fixed)
-- Description: Create python schema with exactly 8 analytics & AI tables
-- Tables: analytics_events, analytics_aggregates, ml_models, ml_inferences,
--         recommendations, chatbot_conversations, chatbot_interactions,
--         image_recognition_results

-- Create python schema
CREATE SCHEMA IF NOT EXISTS python;
SET search_path TO python;

-- Analytics tables
CREATE TABLE IF NOT EXISTS analytics_event (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    properties JSONB,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_analytics_event_user_id ON python.analytics_event(user_id);
CREATE INDEX IF NOT EXISTS idx_analytics_event_type ON python.analytics_event(event_type);
CREATE INDEX IF NOT EXISTS idx_analytics_event_timestamp ON python.analytics_event(timestamp DESC);

CREATE TABLE IF NOT EXISTS analytics_aggregate (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    aggregate_type VARCHAR(100) NOT NULL,
    metric_name VARCHAR(255) NOT NULL,
    metric_value DECIMAL(20,2) NOT NULL,
    dimensions JSONB,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_analytics_aggregate_type ON python.analytics_aggregate(aggregate_type);
CREATE INDEX IF NOT EXISTS idx_analytics_aggregate_timestamp ON python.analytics_aggregate(timestamp DESC);
CREATE INDEX IF NOT EXISTS idx_analytics_aggregate_metric ON python.analytics_aggregate(metric_name);

-- ML tables
CREATE TABLE IF NOT EXISTS ml_model (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    model_type VARCHAR(100) NOT NULL,
    version VARCHAR(50) NOT NULL,
    framework VARCHAR(50) NOT NULL,
    hyperparameters JSONB,
    metrics JSONB,
    status VARCHAR(50) DEFAULT 'training',
    model_path TEXT,
    training_progress DECIMAL(5,2) DEFAULT 0,
    error_message TEXT,
    base_model_id UUID REFERENCES python.ml_model(id) ON DELETE SET NULL,
    changes JSONB,
    training_data_config JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_ml_model_name ON python.ml_model(name);
CREATE INDEX IF NOT EXISTS idx_ml_model_type ON python.ml_model(model_type);
CREATE INDEX IF NOT EXISTS idx_ml_model_status ON python.ml_model(status);
CREATE INDEX IF NOT EXISTS idx_ml_model_created_at ON python.ml_model(created_at DESC);

CREATE TABLE IF NOT EXISTS ml_inference (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    model_id UUID NOT NULL REFERENCES python.ml_model(id) ON DELETE CASCADE,
    request_id VARCHAR(255) UNIQUE NOT NULL,
    input_data JSONB NOT NULL,
    output_data JSONB,
    confidence DECIMAL(5,4) NOT NULL,
    latency_ms INTEGER NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    error_message TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_ml_inference_model_id ON python.ml_inference(model_id);
CREATE INDEX IF NOT EXISTS idx_ml_inference_request_id ON python.ml_inference(request_id);
CREATE INDEX IF NOT EXISTS idx_ml_inference_status ON python.ml_inference(status);
CREATE INDEX IF NOT EXISTS idx_ml_inference_timestamp ON python.ml_inference(timestamp DESC);

-- Recommendation table
CREATE TABLE IF NOT EXISTS recommendation (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    item_id VARCHAR(255) NOT NULL,
    item_type VARCHAR(100) NOT NULL,
    score DECIMAL(5,4) NOT NULL,
    reason TEXT,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_recommendation_user_id ON python.recommendation(user_id);
CREATE INDEX IF NOT EXISTS idx_recommendation_item_id ON python.recommendation(item_id);
CREATE INDEX IF NOT EXISTS idx_recommendation_item_type ON python.recommendation(item_type);
CREATE INDEX IF NOT EXISTS idx_recommendation_score ON python.recommendation(score DESC);
CREATE INDEX IF NOT EXISTS idx_recommendation_created_at ON python.recommendation(created_at DESC);

-- Chatbot tables
CREATE TABLE IF NOT EXISTS chatbot_conversation (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    bot_type VARCHAR(100) NOT NULL,
    context JSONB,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_chatbot_conversation_user_id ON python.chatbot_conversation(user_id);
CREATE INDEX IF NOT EXISTS idx_chatbot_conversation_bot_type ON python.chatbot_conversation(bot_type);
CREATE INDEX IF NOT EXISTS idx_chatbot_conversation_status ON python.chatbot_conversation(status);
CREATE INDEX IF NOT EXISTS idx_chatbot_conversation_created_at ON python.chatbot_conversation(created_at DESC);

CREATE TABLE IF NOT EXISTS chatbot_interaction (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    conversation_id UUID NOT NULL REFERENCES python.chatbot_conversation(id) ON DELETE CASCADE,
    role VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    metadata JSONB,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_chatbot_interaction_conversation_id ON python.chatbot_interaction(conversation_id);
CREATE INDEX IF NOT EXISTS idx_chatbot_interaction_timestamp ON python.chatbot_interaction(timestamp DESC);

-- Image recognition table
CREATE TABLE IF NOT EXISTS image_recognition_result (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    image_url TEXT NOT NULL,
    detected_objects JSONB NOT NULL,
    confidence DECIMAL(5,4) NOT NULL,
    model_version VARCHAR(50) NOT NULL,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_image_recognition_result_user_id ON python.image_recognition_result(user_id);
CREATE INDEX IF NOT EXISTS idx_image_recognition_result_image_url ON python.image_recognition_result(image_url);
CREATE INDEX IF NOT EXISTS idx_image_recognition_result_model_version ON python.image_recognition_result(model_version);
CREATE INDEX IF NOT EXISTS idx_image_recognition_result_created_at ON python.image_recognition_result(created_at DESC);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION python.update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_ml_model_updated_at BEFORE UPDATE ON ml_model
    FOR EACH ROW EXECUTE FUNCTION python.update_updated_at_column();

CREATE TRIGGER update_chatbot_conversation_updated_at BEFORE UPDATE ON chatbot_conversation
    FOR EACH ROW EXECUTE FUNCTION python.update_updated_at_column();
