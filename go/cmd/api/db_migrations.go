package main

import (
	"database/sql"
	"fmt"
)

func createTables(db *sql.DB) error {
	// Banking tables
	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS banking_accounts (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			account_number TEXT UNIQUE NOT NULL,
			account_type TEXT NOT NULL,
			balance REAL NOT NULL DEFAULT 0,
			currency TEXT NOT NULL DEFAULT 'VND',
			status TEXT NOT NULL DEFAULT 'active',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("banking_accounts: %w", err)
	}

	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS banking_transactions (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			account_id INTEGER NOT NULL,
			type TEXT NOT NULL,
			amount REAL NOT NULL,
			balance_after REAL NOT NULL,
			description TEXT,
			reference_id TEXT,
			status TEXT NOT NULL DEFAULT 'completed',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("banking_transactions: %w", err)
	}

	// BillsPayment tables
	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS bills (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			bill_type TEXT NOT NULL,
			provider TEXT NOT NULL,
			account_number TEXT NOT NULL,
			customer_name TEXT NOT NULL,
			amount REAL NOT NULL,
			period TEXT,
			status TEXT NOT NULL DEFAULT 'pending',
			due_date TIMESTAMP NOT NULL,
			paid_at TIMESTAMP,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("bills: %w", err)
	}

	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS bill_payments (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			bill_id INTEGER NOT NULL,
			user_id TEXT NOT NULL,
			amount REAL NOT NULL,
			payment_method TEXT NOT NULL,
			transaction_id TEXT UNIQUE NOT NULL,
			status TEXT NOT NULL DEFAULT 'completed',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("bill_payments: %w", err)
	}

	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS bill_providers (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT NOT NULL,
			code TEXT UNIQUE NOT NULL,
			category TEXT NOT NULL,
			processing_fee REAL NOT NULL DEFAULT 0,
			min_amount REAL NOT NULL DEFAULT 0,
			max_amount REAL,
			is_active BOOLEAN NOT NULL DEFAULT true
		);
	`); err != nil {
		return fmt.Errorf("bill_providers: %w", err)
	}

	// Courier tables
	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS couriers (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			name TEXT NOT NULL,
			phone TEXT,
			email TEXT,
			vehicle_type TEXT,
			vehicle_number TEXT,
			status TEXT NOT NULL DEFAULT 'available',
			latitude REAL,
			longitude REAL,
			rating REAL DEFAULT 0,
			total_deliveries INTEGER DEFAULT 0,
			available_balance REAL DEFAULT 0,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("couriers: %w", err)
	}

	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS courier_orders (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			order_id TEXT UNIQUE NOT NULL,
			courier_id INTEGER,
			order_type TEXT NOT NULL DEFAULT 'standard',
			pickup_address TEXT NOT NULL,
			pickup_lat REAL NOT NULL,
			pickup_lng REAL NOT NULL,
			delivery_address TEXT NOT NULL,
			delivery_lat REAL NOT NULL,
			delivery_lng REAL NOT NULL,
			customer_name TEXT NOT NULL,
			customer_phone TEXT,
			package_desc TEXT,
			package_weight REAL,
			distance REAL,
			fee REAL,
			status TEXT NOT NULL DEFAULT 'pending',
			assigned_at TIMESTAMP,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			FOREIGN KEY (courier_id) REFERENCES couriers(id)
		);
	`); err != nil {
		return fmt.Errorf("courier_orders: %w", err)
	}

	// CreditLending tables
	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS credit_applications (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			credit_type TEXT NOT NULL,
			amount REAL NOT NULL,
			term INTEGER NOT NULL,
			interest_rate REAL NOT NULL,
			status TEXT NOT NULL DEFAULT 'pending',
			approved_amount REAL,
			monthly_payment REAL,
			total_repayment REAL,
			disbursed_at TIMESTAMP,
			completed_at TIMESTAMP,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("credit_applications: %w", err)
	}

	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS credit_repayments (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			application_id INTEGER NOT NULL,
			amount REAL NOT NULL,
			principal REAL NOT NULL,
			interest REAL NOT NULL,
			penalty REAL DEFAULT 0,
			balance_after REAL NOT NULL,
			paid_at TIMESTAMP,
			status TEXT NOT NULL DEFAULT 'completed',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("credit_repayments: %w", err)
	}

	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS credit_scores (
			user_id TEXT PRIMARY KEY,
			score INTEGER NOT NULL,
			risk_level TEXT NOT NULL,
			max_credit_amount REAL NOT NULL,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("credit_scores: %w", err)
	}

	// FraudDetection tables
	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS fraud_alerts (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			transaction_id TEXT,
			type TEXT NOT NULL,
			risk_level TEXT NOT NULL,
			score REAL NOT NULL,
			details TEXT,
			status TEXT NOT NULL DEFAULT 'open',
			resolved_at TIMESTAMP,
			resolved_by TEXT,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("fraud_alerts: %w", err)
	}

	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS fraud_rules (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT NOT NULL,
			description TEXT,
			rule_type TEXT NOT NULL,
			condition TEXT NOT NULL,
			action TEXT NOT NULL,
			risk_score REAL NOT NULL,
			is_active BOOLEAN NOT NULL DEFAULT true,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("fraud_rules: %w", err)
	}

	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS fraud_reports (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			transaction_id TEXT,
			report_type TEXT NOT NULL,
			description TEXT NOT NULL,
			status TEXT NOT NULL DEFAULT 'pending',
			assigned_to TEXT,
			resolution TEXT,
			resolved_at TIMESTAMP,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("fraud_reports: %w", err)
	}

	// VideoConference tables
	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS video_rooms (
			id TEXT PRIMARY KEY,
			participant_count INTEGER DEFAULT 0,
			status TEXT NOT NULL DEFAULT 'active',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`); err != nil {
		return fmt.Errorf("video_rooms: %w", err)
	}

	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS video_participants (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			room_id TEXT NOT NULL,
			user_id TEXT NOT NULL,
			joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			left_at TIMESTAMP,
			FOREIGN KEY (room_id) REFERENCES video_rooms(id)
		);
	`); err != nil {
		return fmt.Errorf("video_participants: %w", err)
	}

	return nil
}
