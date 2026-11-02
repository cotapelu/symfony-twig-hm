package database

import (
	"database/sql"
	"fmt"
	"os"
	"time"

	_ "github.com/lib/pq"
)

// Config holds database configuration
type Config struct {
	Host     string
	Port     string
	User     string
	Password string
	DBName   string
	SSLMode  string
}

// NewConfig creates a new database configuration from environment variables or defaults
func NewConfig() *Config {
	return &Config{
		Host:     getEnv("DB_HOST", "localhost"),
		Port:     getEnv("DB_PORT", "5432"),
		User:     getEnv("DB_USER", "postgres"),
		Password: getEnv("DB_PASSWORD", "postgres"),
		DBName:   getEnv("DB_NAME", "phuongxa"),
		SSLMode:  getEnv("DB_SSLMODE", "disable"),
	}
}

// GetDBConnection returns a PostgreSQL database connection
func GetDBConnection(config *Config) (*sql.DB, error) {
	dsn := fmt.Sprintf(
		"host=%s port=%s user=%s password=%s dbname=%s sslmode=%s",
		config.Host,
		config.Port,
		config.User,
		config.Password,
		config.DBName,
		config.SSLMode,
	)

	db, err := sql.Open("postgres", dsn)
	if err != nil {
		return nil, fmt.Errorf("failed to connect to database: %w", err)
	}

	// Configure connection pool
	db.SetMaxOpenConns(25)
	db.SetMaxIdleConns(5)
	db.SetConnMaxIdleTime(5 * time.Minute)
	db.SetConnMaxLifetime(1 * time.Hour)

	// Verify connection
	if err := db.Ping(); err != nil {
		db.Close()
		return nil, fmt.Errorf("failed to ping database: %w", err)
	}

	return db, nil
}

// GetDefaultDBConnection creates connection using environment variables or defaults
func GetDefaultDBConnection() (*sql.DB, error) {
	return GetDBConnection(NewConfig())
}

// RunMigration executes SQL migration scripts
func RunMigration(db *sql.DB, migrationSQL string) error {
	_, err := db.Exec(migrationSQL)
	if err != nil {
		return fmt.Errorf("migration failed: %w", err)
	}
	return nil
}

// CreateTables creates all required tables for the application
func CreateTables(db *sql.DB) error {
	migrations := []string{
		// Users table
		`CREATE TABLE IF NOT EXISTS users (
			id SERIAL PRIMARY KEY,
			uuid UUID UNIQUE NOT NULL DEFAULT gen_random_uuid(),
			email TEXT UNIQUE NOT NULL,
			username TEXT UNIQUE NOT NULL,
			password_hash TEXT NOT NULL,
			full_name TEXT,
			avatar_url TEXT,
			phone TEXT,
			is_active BOOLEAN NOT NULL DEFAULT true,
			is_verified BOOLEAN NOT NULL DEFAULT false,
			role TEXT NOT NULL DEFAULT 'user',
			metadata JSONB,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Sessions table
		`CREATE TABLE IF NOT EXISTS sessions (
			id SERIAL PRIMARY KEY,
			user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
			session_token TEXT UNIQUE NOT NULL,
			device_info TEXT,
			ip_address TEXT,
			expires_at TIMESTAMP NOT NULL,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_accessed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Banking accounts
		`CREATE TABLE IF NOT EXISTS banking_accounts (
			id SERIAL PRIMARY KEY,
			user_id TEXT NOT NULL,
			account_number TEXT UNIQUE NOT NULL,
			account_type TEXT NOT NULL,
			balance NUMERIC(20,2) NOT NULL DEFAULT 0.00,
			currency TEXT NOT NULL DEFAULT 'VND',
			status TEXT NOT NULL DEFAULT 'active',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Banking transactions
		`CREATE INDEX idx_banking_transactions_account_id ON banking_transactions(account_id);`,
		`CREATE TABLE IF NOT EXISTS banking_transactions (
			id SERIAL PRIMARY KEY,
			account_id INTEGER NOT NULL REFERENCES banking_accounts(id),
			type TEXT NOT NULL,
			amount NUMERIC(20,2) NOT NULL,
			balance_after NUMERIC(20,2) NOT NULL,
			description TEXT,
			reference_id TEXT,
			status TEXT NOT NULL DEFAULT 'completed',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Bills
		`CREATE INDEX idx_bills_due_date ON bills(due_date);`,
		`CREATE TABLE IF NOT EXISTS bills (
			id SERIAL PRIMARY KEY,
			bill_type TEXT NOT NULL,
			provider TEXT NOT NULL,
			account_number TEXT NOT NULL,
			customer_name TEXT NOT NULL,
			amount NUMERIC(20,2) NOT NULL,
			period TEXT,
			status TEXT NOT NULL DEFAULT 'pending',
			due_date DATE NOT NULL,
			paid_at TIMESTAMP,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Bill payments
		`CREATE INDEX idx_bill_payments_transaction_id ON bill_payments(transaction_id);`,
		`CREATE TABLE IF NOT EXISTS bill_payments (
			id SERIAL PRIMARY KEY,
			bill_id INTEGER NOT NULL REFERENCES bills(id),
			user_id TEXT NOT NULL,
			amount NUMERIC(20,2) NOT NULL,
			payment_method TEXT NOT NULL,
			transaction_id TEXT UNIQUE NOT NULL,
			status TEXT NOT NULL DEFAULT 'completed',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Couriers
		`CREATE INDEX idx_couriers_status ON couriers(status);`,
		`CREATE INDEX idx_couriers_location ON couriers(latitude, longitude) USING GIST;`,
		`CREATE TABLE IF NOT EXISTS couriers (
			id SERIAL PRIMARY KEY,
			user_id TEXT NOT NULL,
			name TEXT NOT NULL,
			phone TEXT,
			email TEXT,
			vehicle_type TEXT,
			vehicle_number TEXT,
			status TEXT NOT NULL DEFAULT 'available',
			latitude NUMERIC(10, 8),
			longitude NUMERIC(11, 8),
			rating NUMERIC(3, 2) DEFAULT 0.0,
			total_deliveries INTEGER DEFAULT 0,
			available_balance NUMERIC(20,2) DEFAULT 0.0,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Courier orders
		`CREATE INDEX idx_courier_orders_status ON courier_orders(status);`,
		`CREATE TABLE IF NOT EXISTS courier_orders (
			id SERIAL PRIMARY KEY,
			order_id TEXT UNIQUE NOT NULL,
			courier_id INTEGER REFERENCES couriers(id),
			order_type TEXT NOT NULL DEFAULT 'standard',
			pickup_address TEXT NOT NULL,
			pickup_lat NUMERIC(10, 8) NOT NULL,
			pickup_lng NUMERIC(11, 8) NOT NULL,
			delivery_address TEXT NOT NULL,
			delivery_lat NUMERIC(10, 8) NOT NULL,
			delivery_lng NUMERIC(11, ) NOT NULL,
			customer_name TEXT NOT NULL,
			customer_phone TEXT,
			package_desc TEXT,
			package_weight NUMERIC(10, 2),
			distance NUMERIC,
			fee NUMERIC(10, 2),
			status TEXT NOT NULL DEFAULT 'pending',
			assigned_at TIMESTAMP,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Credit applications
		`CREATE INDEX idx_credit_applications_user_id ON credit_applications(user_id);`,
		`CREATE TABLE IF NOT EXISTS credit_applications (
			id SERIAL PRIMARY KEY,
			user_id TEXT NOT NULL,
			credit_type TEXT NOT NULL,
			amount NUMERIC(20,2) NOT NULL,
			term INTEGER NOT NULL,
			interest_rate NUMERIC(5,2) NOT NULL,
			status TEXT NOT NULL DEFAULT 'pending',
			approved_amount NUMERIC(20,2),
			monthly_payment NUMERIC(20,2),
			total_repayment NUMERIC(20,2),
			disbursed_at TIMESTAMP,
			completed_at TIMESTAMP,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Credit repayments
		`CREATE INDEX idx_credit_repayments_application_id ON credit_repayments(application_id);`,
		`CREATE TABLE IF NOT EXISTS credit_repayments (
			id SERIAL PRIMARY KEY,
			application_id INTEGER NOT NULL REFERENCES credit_applications(id),
			amount NUMERIC(20,2) NOT NULL,
			principal NUMERIC(20,2) NOT NULL,
			interest NUMERIC(20,2) NOT NULL,
			penalty NUMERIC(20,2) DEFAULT 0,
			balance_after NUMERIC(20,2) NOT NULL,
			paid_at TIMESTAMP,
			status TEXT NOT NULL DEFAULT 'completed',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Credit scores
		`CREATE INDEX idx_credit_scores_user_id ON credit_scores(user_id);`,
		`CREATE TABLE IF NOT EXISTS credit_scores (
			user_id TEXT PRIMARY KEY,
			score INTEGER NOT NULL,
			risk_level TEXT NOT NULL,
			max_credit_amount NUMERIC(20,2) NOT NULL,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Fraud alerts
		`CREATE INDEX idx_fraud_alerts_user_id ON fraud_alerts(user_id);`,
		`CREATE INDEX idx_fraud_alerts_status ON fraud_alerts(status);`,
		`CREATE TABLE IF NOT EXISTS fraud_alerts (
			id SERIAL PRIMARY KEY,
			user_id TEXT NOT NULL,
			transaction_id TEXT,
			type TEXT NOT NULL,
			risk_level TEXT NOT NULL,
			score NUMERIC(5,2) NOT NULL,
			details TEXT,
			status TEXT NOT NULL DEFAULT 'open',
			resolved_at TIMESTAMP,
			resolved_by TEXT,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Fraud rules
		`CREATE INDEX idx_fraud_rules_is_active ON fraud_rules(is_active);`,
		`CREATE TABLE IF NOT EXISTS fraud_rules (
			id SERIAL PRIMARY KEY,
			name TEXT NOT NULL,
			description TEXT,
			rule_type TEXT NOT NULL,
			condition TEXT NOT NULL,
			action TEXT NOT NULL,
			risk_score NUMERIC(5,2) NOT NULL,
			is_active BOOLEAN NOT NULL DEFAULT true,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Fraud reports
		`CREATE INDEX idx_fraud_reports_user_id ON fraud_reports(user_id);`,
		`CREATE INDEX idx_fraud_reports_status ON fraud_reports(status);`,
		`CREATE TABLE IF NOT EXISTS fraud_reports (
			id SERIAL PRIMARY KEY,
			user_id TEXT NOT NULL,
			transaction_id TEXT,
			report_type TEXT NOT NULL,
			description TEXT NOT NULL,
			status TEXT NOT NULL DEFAULT 'pending',
			assigned_to TEXT,
			resolution TEXT,
			resolved_at TIMESTAMP,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Video rooms
		`CREATE INDEX idx_video_participants_room_id ON video_participants(room_id);`,
		`CREATE TABLE IF NOT EXISTS video_rooms (
			id TEXT PRIMARY KEY,
			participant_count INTEGER DEFAULT 0,
			status TEXT NOT NULL DEFAULT 'active',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)`,
		
		// Video participants
		`CREATE INDEX idx_video_participants_user_id ON video_participants(user_id);`,
		`CREATE TABLE IF NOT EXISTS video_participants (
			id SERIAL PRIMARY KEY,
			room_id TEXT NOT NULL REFERENCES video_rooms(id),
			user_id TEXT NOT NULL,
			joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			left_at TIMESTAMP
		)`,
	}

	for _, migration := range migrations {
		if err := RunMigration(db, migration); err != nil {
			return fmt.Errorf("migration '%s' failed: %w", migration[0:50], err)
		}
	}

	return nil
}

// CloseDatabase closes the database connection
func CloseDatabase(db *sql.DB) error {
	if err := db.Close(); err != nil {
		return fmt.Errorf("failed to close database: %w", err)
	}
	return nil
}

// HealthCheck checks if the database is accessible
func HealthCheck(db *sql.DB) error {
	return db.Ping()
}

// getEnv retrieves an environment variable with a default value
func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}
