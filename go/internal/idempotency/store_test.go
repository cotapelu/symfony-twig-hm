package idempotency

import (
	"sync"
	"testing"
)

func TestGetSet(t *testing.T) {
	key := "test-key"
	value := "test-value"

	Set(key, value)

	got, ok := Get(key)
	if !ok {
		t.Fatal("Get: expected key to exist")
	}
	if got != value {
		t.Errorf("Get() = %v, want %v", got, value)
	}
}

func TestGetMissing(t *testing.T) {
	_, ok := Get("nonexistent")
	if ok {
		t.Error("Get: expected false for missing key")
	}
}

func TestDelete(t *testing.T) {
	key := "delete-key"
	Set(key, "value")

	Delete(key)

	_, ok := Get(key)
	if ok {
		t.Error("Get after Delete: expected key to be gone")
	}
}

func TestSetOverwrite(t *testing.T) {
	key := "overwrite-key"
	Set(key, "first")
	Set(key, "second")

	got, ok := Get(key)
	if !ok {
		t.Fatal("Get: expected key to exist")
	}
	if got != "second" {
		t.Errorf("After overwrite, Get() = %v, want second", got)
	}
}

func TestConcurrentAccess(t *testing.T) {
	const numOps = 100
	var wg sync.WaitGroup

	// Concurrent writes
	for i := 0; i < numOps; i++ {
		wg.Add(1)
		go func(idx int) {
			defer wg.Done()
			key := "key"
			Set(key, idx)
		}(i)
	}

	// Concurrent reads
	for i := 0; i < numOps; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			Get("key")
		}()
	}

	wg.Wait()
	// If no deadlock or crash, consider it passing.
}
