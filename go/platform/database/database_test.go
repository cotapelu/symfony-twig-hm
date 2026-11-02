package database

import (
	"fmt"
	"os"
	"testing"

	"github.com/DATA-DOG/go-sqlmock"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestNewConfig(t *testing.T) {
	// Ensure environment is clean for deterministic test
	os.Clearenv()
	// Set some env vars
	os.Setenv("DB_HOST", "testhost")
	os.Setenv("DB_PORT", "5555")
	os.Setenv("DB_USER", "testuser")
	os.Setenv("DB_PASSWORD", "testpass")
	os.Setenv("DB_NAME", "testdb")
	os.Setenv("DB_SSLMODE", "require")

	cfg := NewConfig()
	assert.Equal(t, "testhost", cfg.Host)
	assert.Equal(t, "5555", cfg.Port)
	assert.Equal(t, "testuser", cfg.User)
	assert.Equal(t, "testpass", cfg.Password)
	assert.Equal(t, "testdb", cfg.DBName)
	assert.Equal(t, "require", cfg.SSLMode)
}

func TestNewConfig_Defaults(t *testing.T) {
	os.Clearenv()
	// Do not set any env vars; expect defaults
	cfg := NewConfig()
	assert.Equal(t, "localhost", cfg.Host)
	assert.Equal(t, "5432", cfg.Port)
	assert.Equal(t, "postgres", cfg.User)
	assert.Equal(t, "postgres", cfg.Password)
	assert.Equal(t, "phuongxa", cfg.DBName)
	assert.Equal(t, "disable", cfg.SSLMode)
}

func TestRunMigration_Success(t *testing.T) {
	db, mock, err := sqlmock.New(sqlmock.MonitorPingsOption(true))
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	migrationSQL := "SELECT 1"
	mock.ExpectExec(migrationSQL).WillReturnResult(sqlmock.NewResult(0, 1))

	err = RunMigration(db, migrationSQL)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestRunMigration_Error(t *testing.T) {
	db, mock, err := sqlmock.New(sqlmock.MonitorPingsOption(true))
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	migrationSQL := "INVALID SQL"
	mock.ExpectExec(migrationSQL).WillReturnError(fmt.Errorf("exec error"))

	err = RunMigration(db, migrationSQL)
	require.Error(t, err)
	require.Contains(t, err.Error(), "migration failed")
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestHealthCheck_Success(t *testing.T) {
	db, mock, err := sqlmock.New(sqlmock.MonitorPingsOption(true))
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	mock.ExpectPing().WillReturnError(nil)

	err = HealthCheck(db)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestHealthCheck_Error(t *testing.T) {
	db, mock, err := sqlmock.New(sqlmock.MonitorPingsOption(true))
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}
	defer db.Close()

	mock.ExpectPing().WillReturnError(fmt.Errorf("connection lost"))

	err = HealthCheck(db)
	require.Error(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCloseDatabase_Success(t *testing.T) {
	db, mock, err := sqlmock.New(sqlmock.MonitorPingsOption(true))
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}

	mock.ExpectClose().WillReturnError(nil)

	err = CloseDatabase(db)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestCloseDatabase_Error(t *testing.T) {
	db, mock, err := sqlmock.New(sqlmock.MonitorPingsOption(true))
	if err != nil {
		t.Fatalf("sqlmock.New error: %v", err)
	}

	mock.ExpectClose().WillReturnError(fmt.Errorf("close error"))

	err = CloseDatabase(db)
	require.Error(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}
