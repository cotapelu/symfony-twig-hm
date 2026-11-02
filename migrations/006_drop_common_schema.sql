-- Migration: Drop Common Schema
-- Description: Drop common schema after migrating tables to language-specific schemas

-- WARNING: This migration will drop the common schema
-- Make sure all data has been migrated to the appropriate language schemas

-- Drop common schema
DROP SCHEMA IF EXISTS common CASCADE;

-- Verify schemas exist
SELECT schema_name 
FROM information_schema.schemata 
WHERE schema_name IN ('php', 'go', 'python', 'kotlin', 'rust')
ORDER BY schema_name;

-- Count tables in each schema
SELECT 
    schemaname as schema_name,
    COUNT(*) as table_count
FROM pg_tables
WHERE schemaname IN ('php', 'go', 'python', 'kotlin', 'rust')
GROUP BY schemaname
ORDER BY schemaname;
