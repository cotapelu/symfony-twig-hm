-- Migration: Add unique constraint to prevent double-booking
-- Description: Create unique index on appointments to ensure an officer is not double-booked for the same date and time.

-- Create unique index on (officer_id, appointment_date, appointment_time)
-- Note: officer_id may be NULL (unassigned), but NULL values are allowed multiple times in a unique index.
CREATE UNIQUE INDEX idx_unique_officer_appointment_slot ON appointments (officer_id, appointment_date, appointment_time);
