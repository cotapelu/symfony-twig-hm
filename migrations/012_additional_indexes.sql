-- Additional Performance Indexes
-- Target tables: notifications, events, media, analytics

-- Common schema indexes
CREATE INDEX IF NOT EXISTS idx_notifications_user_unread ON common.notifications(user_id, created_at DESC) WHERE status = 'unread';
CREATE INDEX IF NOT EXISTS idx_notifications_type_status ON common.notifications(type, status);

CREATE INDEX IF NOT EXISTS idx_events_source_created ON common.events(source, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_event_store_aggregate_version ON common.event_store(aggregate_id, aggregate_type, version);

CREATE INDEX IF NOT EXISTS idx_media_folder_parent ON common.media_folders(parent_id);
CREATE INDEX IF NOT EXISTS idx_media_file_folder ON common.media_files(folder_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_media_file_user ON common.media_files(user_id, created_at DESC);

-- User-related indexes
CREATE INDEX IF NOT EXISTS idx_user_sessions_active ON common.user_sessions(user_id, expires_at) WHERE expires_at > NOW();
CREATE INDEX IF NOT EXISTS idx_user_roles_active ON common.user_roles(user_id);

-- Feature flag indexes
CREATE INDEX IF NOT EXISTS idx_feature_flags_enabled ON common.feature_flags(enabled);

-- Workflow indexes
CREATE INDEX IF NOT EXISTS idx_workflows_active ON common.workflows(status) WHERE status = 'active';
CREATE INDEX IF NOT EXISTS idx_workflow_instances_workflow ON common.workflow_instances(workflow_id);

-- Analytics tables (if they exist)
CREATE INDEX IF NOT EXISTS idx_analytics_events_user_event ON python.analytics_events(user_id, event_name, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_recommendations_user_score ON python.recommendations(user_id, score DESC);
CREATE INDEX IF NOT EXISTS idx_chatbot_sessions_user ON python.chatbot_conversations(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_image_recognition_user ON python.image_recognition_results(user_id, created_at DESC);