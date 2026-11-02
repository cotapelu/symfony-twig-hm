package database

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestGetDBConnection_Error(t *testing.T) {
	cfg := &Config{
		Host:     "localhost", // Assuming no PostgreSQL server running
		Port:     "5432",
		User:     "postgres",
		Password: "postgres",
		DBName:   "testdb",
		SSLMode:  "disable",
	}
	_, err := GetDBConnection(cfg)
	assert.Error(t, err, "expected error when connecting to non-existent database")
}

func TestGetDefaultDBConnection_Error(t *testing.T) {
	// Override environment to ensure connection fails
	t.Setenv("DB_HOST", "invalid")
	t.Setenv("DB_PORT", "5432")
	t.Setenv("DB_USER", "postgres")
	t.Setenv("DB_PASSWORD", "postgres")
	t.Setenv("DB_NAME", "nonexistent")
	_, err := GetDefaultDBConnection()
	assert.Error(t, err, "expected error when default database connection fails")
}
