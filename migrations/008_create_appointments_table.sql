-- Migration: Create appointments table
-- Description: Create appointments table for appointment scheduling

CREATE SCHEMA IF NOT EXISTS php;
SET search_path TO php;

CREATE TABLE IF NOT EXISTS appointments (
    id UUID PRIMARY KEY,
    appointment_number VARCHAR(20) UNIQUE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    citizen_id VARCHAR(36) NULL,
    citizen_name VARCHAR(255) NOT NULL,
    citizen_phone VARCHAR(20) NOT NULL,
    citizen_email VARCHAR(255) NULL,
    citizen_cccd VARCHAR(20) NULL,
    officer_id VARCHAR(36) NULL,
    ward_code VARCHAR(20) NOT NULL,
    citizen_user_id VARCHAR(100) NULL,
    department VARCHAR(255) NULL,
    appointment_date TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    appointment_time VARCHAR(10) NOT NULL,
    version INTEGER NOT NULL DEFAULT 0,
    duration_minutes INTEGER NOT NULL DEFAULT 30,
    purpose VARCHAR(100) NOT NULL,
    preferred_time VARCHAR(50) NULL,
    description TEXT NULL,
    notes TEXT NULL,
    reminders_sent JSON NULL,
    confirmed_at TIMESTAMP WITHOUT TIME ZONE NULL,
    started_at TIMESTAMP WITHOUT TIME ZONE NULL,
    completed_at TIMESTAMP WITHOUT TIME ZONE NULL,
    cancelled_at TIMESTAMP WITHOUT TIME ZONE NULL,
    cancellation_reason TEXT NULL,
    cancel_by VARCHAR(50) NULL,
    rescheduled_from_id VARCHAR(36) NULL,
    officer_notes TEXT NULL,
    citizen_rating INTEGER NULL,
    citizen_feedback TEXT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE NULL
);

-- Indexes for performance
CREATE INDEX idx_appointment_citizen_id ON appointments (citizen_id);
CREATE INDEX idx_appointment_officer_id ON appointments (officer_id);
CREATE INDEX idx_appointment_status ON appointments (status);
CREATE INDEX idx_appointment_datetime ON appointments (appointment_date, appointment_time);

-- Unique constraint to prevent double-booking
CREATE UNIQUE INDEX idx_unique_officer_appointment_slot ON appointments (officer_id, appointment_date, appointment_time);
