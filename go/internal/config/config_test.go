package config

import (
	"os"
	"path/filepath"
	"testing"

	"github.com/spf13/viper"
)

func TestLoadConfig(t *testing.T) {
	// Reset viper to avoid state leakage
	viper.Reset()
	defer viper.Reset()

	// Create temporary directory and config file
	dir := t.TempDir()
	configPath := filepath.Join(dir, "config.yaml")
	yaml := `server:
  port: 9090
  host: "127.0.0.1"
redis:
  host: "redis-host"
  port: 6380
  db: 1
`
	if err := os.WriteFile(configPath, []byte(yaml), 0644); err != nil {
		t.Fatal(err)
	}

	cfg, err := LoadConfig(dir)
	if err != nil {
		t.Fatalf("LoadConfig() error = %v", err)
	}

	// Verify values from file
	if cfg.Server.Port != 9090 {
		t.Errorf("Server.Port = %d, want 9090", cfg.Server.Port)
	}
	if cfg.Server.Host != "127.0.0.1" {
		t.Errorf("Server.Host = %s, want 127.0.0.1", cfg.Server.Host)
	}
	if cfg.Redis.Host != "redis-host" {
		t.Errorf("Redis.Host = %s, want redis-host", cfg.Redis.Host)
	}
	if cfg.Redis.Port != 6380 {
		t.Errorf("Redis.Redis.Port = %d, want 6380", cfg.Redis.Port)
	}
	if cfg.Redis.DB != 1 {
		t.Errorf("Redis.DB = %d, want 1", cfg.Redis.DB)
	}

	// Verify defaults for unset fields
	if cfg.Server.Mode != "release" {
		t.Errorf("Server.Mode = %s, want release", cfg.Server.Mode)
	}
	if cfg.Log.Level != "info" {
		t.Errorf("Log.Level = %s, want info", cfg.Log.Level)
	}
	if cfg.RabbitMQ.Host != "localhost" {
		t.Errorf("RabbitMQ.Host = %s, want localhost", cfg.RabbitMQ.Host)
	}
}

func TestLoadConfig_Missing(t *testing.T) {
	viper.Reset()
	defer viper.Reset()

	dir := t.TempDir()
	// Do not create any config file
	cfg, err := LoadConfig(dir)
	if err == nil {
		t.Fatal("Expected error from LoadConfig with missing file")
	}
	if cfg != nil {
		t.Errorf("Expected nil config, got %+v", cfg)
	}
}
