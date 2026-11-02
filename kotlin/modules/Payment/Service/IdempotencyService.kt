package payment

import io.ktor.http.HttpStatusCode
import java.time.Duration
import java.time.Instant
import java.util.concurrent.ConcurrentHashMap

/**
 * Service for storing and retrieving idempotent responses.
 * Ensures that repeated requests with the same idempotency key return the same response.
 *
 * Uses in-memory storage with expiration. In production, consider using Redis or distributed cache.
 */
class IdempotencyService(
    private val defaultTtl: Duration = Duration.ofHours(24)
) {
    private data class CacheEntry<T>(
        val value: T,
        val status: HttpStatusCode,
        val expiresAt: Instant
    ) {
        fun isExpired(): Boolean = Instant.now().isAfter(expiresAt)
    }

    private val cache = ConcurrentHashMap<String, CacheEntry<*>>()

    /**
     * Get a cached response for the given key, if present and not expired.
     * Returns the value and its HTTP status.
     */
    @Suppress("UNCHECKED_CAST")
    fun <T> get(key: String): Pair<T, HttpStatusCode>? {
        val entry = cache[key] as? CacheEntry<T> ?: return null
        if (entry.isExpired()) {
            cache.remove(key)
            return null
        }
        return Pair(entry.value, entry.status)
    }

    /**
     * Store a response for the given key with a status code and optional TTL.
     */
    fun <T> set(key: String, value: T, status: HttpStatusCode, ttl: Duration = defaultTtl) {
        val expiresAt = Instant.now().plus(ttl)
        cache[key] = CacheEntry(value, status, expiresAt) as CacheEntry<*>
    }

    /**
     * Compute the value if absent, or return the existing cached value.
     * This method is atomic for the given key using ConcurrentHashMap.compute.
     *
     * Note: The compute block should be idempotent and quick.
     */
    @Suppress("UNCHECKED_CAST")
    fun <T> getOrCompute(
        key: String,
        status: HttpStatusCode,
        ttl: Duration = defaultTtl,
        compute: () -> T
    ): T {
        val newEntry = cache.compute(key) { _, existing ->
            if (existing != null && !existing.isExpired()) {
                existing as CacheEntry<T>
            } else {
                val value = compute()
                CacheEntry(value, status, Instant.now().plus(ttl))
            }
        } as CacheEntry<T>
        return newEntry.value
    }

    /**
     * Remove a specific key from the cache (useful for testing or manual invalidation).
     */
    fun invalidate(key: String) {
        cache.remove(key)
    }

    /**
     * Clear all entries (for testing only).
     */
    fun clear() {
        cache.clear()
    }
}
