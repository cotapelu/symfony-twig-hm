# Database Migration Summary

## Overview
- **Total Schemas**: 5 (php, go, python, kotlin, rust)
- **Total Tables**: 61 tables
- **Removed**: common schema (15 tables migrated to language-specific schemas)

## Schema Breakdown

### PHP Schema (27 tables)
**Purpose**: Business logic tables

**Tables**:
1. user - User accounts
2. user_session - User sessions
3. device_token - Device tokens
4. oauth_provider - OAuth providers
5. user_role - User roles
6. feature_flag - Feature flags
7. workflow - Workflows
8. citizen - Citizen information
9. household - Household information
10. gov_officer - Government officers
11. administrative_procedure - Administrative procedures
12. procedure_application - Procedure applications
13. procedure_approval - Procedure approvals
14. citizen_incident - Citizen incidents
15. delivery_store - Delivery stores
16. delivery_product - Delivery products
17. delivery_order - Delivery orders
18. delivery_order_item - Delivery order items
19. delivery_person - Delivery persons
20. delivery_vehicle - Delivery vehicles
21. ecommerce_product - E-commerce products
22. ecommerce_order - E-commerce orders
23. ecommerce_transaction - E-commerce transactions
24. business - Business information
25. business_catalog - Business catalogs
26. service_booking - Service bookings
27. service_review - Service reviews

**Migration File**: `001_create_php_schema.sql`

### Go Schema (10 tables)
**Purpose**: Financial services tables

**Tables**:
1. banking_account - Banking accounts
2. banking_transaction - Banking transactions
3. crypto_wallet - Crypto wallets
4. crypto_transaction - Crypto transactions
5. fraud_alert - Fraud alerts
6. fraud_rule - Fraud rules
7. fraud_evaluation - Fraud evaluations
8. location - Location data
9. geofence - Geofences
10. geofence_event - Geofence events

**Migration File**: `002_create_go_schema.sql`

### Python Schema (11 tables)
**Purpose**: Analytics & AI tables

**Tables**:
1. event - Events
2. event_store - Event store
3. workflow_instance - Workflow instances
4. analytics_event - Analytics events
5. analytics_aggregate - Analytics aggregates
6. ml_model - ML models
7. ml_inference - ML inferences
8. recommendation - Recommendations
9. chatbot_conversation - Chatbot conversations
10. chatbot_interaction - Chatbot interactions
11. image_recognition_result - Image recognition results

**Migration File**: `003_create_python_schema.sql`

### Kotlin Schema (13 tables)
**Purpose**: System & monitoring tables

**Tables**:
1. notification - Notifications
2. notification_channel - Notification channels
3. notification_template - Notification templates
4. media_folder - Media folders
5. media_file - Media files
6. payment - Payments
7. payment_intent - Payment intents
8. push_queue - Push queue
9. email_queue - Email queue
10. system_metric - System metrics
11. alert - Alerts
12. file_metadata - File metadata
13. poll_survey - Poll surveys

**Migration File**: `004_create_kotlin_schema.sql`

### Rust Schema (8 tables)
**Purpose**: Security & crypto tables

**Tables**:
1. security_key - Security keys
2. security_certificate - Security certificates
3. encryption_key - Encryption keys
4. signature - Signatures
5. audit_log - Audit logs
6. security_event - Security events
7. access_control - Access control
8. permission - Permissions

**Migration File**: `005_create_rust_schema.sql`

## Migration Order

1. **001_create_php_schema.sql** - Create PHP schema with 27 tables
2. **002_create_go_schema.sql** - Create Go schema with 10 tables
3. **003_create_python_schema.sql** - Create Python schema with 11 tables
4. **004_create_kotlin_schema.sql** - Create Kotlin schema with 13 tables
5. **005_create_rust_schema.sql** - Create Rust schema with 8 tables
6. **006_drop_common_schema.sql** - Drop common schema

## Features

### Indexes
- All tables have appropriate indexes for performance
- Foreign key columns are indexed
- Timestamp columns are indexed for time-based queries
- Status columns are indexed for filtering

### Triggers
- All tables with `updated_at` columns have triggers to auto-update
- Triggers are named consistently: `update_{table_name}_updated_at`

### Foreign Keys
- All foreign key relationships are defined
- ON DELETE CASCADE for dependent records
- ON DELETE SET NULL for optional relationships

### Constraints
- PRIMARY KEY on all tables (UUID)
- UNIQUE constraints on natural keys
- CHECK constraints for data validation
- NOT NULL constraints on required fields

## Data Types

### Common Types
- UUID for primary keys
- VARCHAR for text fields
- TEXT for long text
- DECIMAL for financial data
- TIMESTAMP for dates
- JSONB for flexible data
- BOOLEAN for flags

### Specific Types
- DECIMAL(20,2) for financial amounts
- DECIMAL(10,8) for latitude
- DECIMAL(11,8) for longitude
- BIGINT for file sizes
- INTEGER for counts

## Rollback Plan

To rollback migrations, run in reverse order:

1. Restore common schema from backup
2. Drop rust schema
3. Drop kotlin schema
4. Drop python schema
5. Drop go schema
6. Drop php schema

## Verification

After running migrations, verify:

```sql
-- Check schemas exist
SELECT schema_name 
FROM information_schema.schemata 
WHERE schema_name IN ('php', 'go', 'python', 'kotlin', 'rust');

-- Count tables in each schema
SELECT 
    schemaname as schema_name,
    COUNT(*) as table_count
FROM pg_tables
WHERE schemaname IN ('php', 'go', 'python', 'kotlin', 'rust')
GROUP BY schemaname;

-- Check common schema is dropped
SELECT schema_name 
FROM information_schema.schemata 
WHERE schema_name = 'common';
```

## Notes

- All migrations use `IF NOT EXISTS` to be idempotent
- All triggers use `OR REPLACE` to be idempotent
- All indexes are created after tables
- All foreign keys are created after tables
- Common schema is dropped last to ensure data migration is complete
