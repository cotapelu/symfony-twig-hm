package idempotency

import (
	"sync"
)

var (
	store = make(map[string]interface{})
	mu    sync.RWMutex
)

// Get returns the cached response for a given idempotency key, if exists.
func Get(key string) (interface{}, bool) {
	mu.RLock()
	defer mu.RUnlock()
	val, ok := store[key]
	return val, ok
}

// Set stores the response for a given idempotency key.
func Set(key string, value interface{}) {
	mu.Lock()
	defer mu.Unlock()
	store[key] = value
}

// Delete removes a key from the store (optional, for cleanup).
func Delete(key string) {
	mu.Lock()
	defer mu.Unlock()
	delete(store, key)
}
