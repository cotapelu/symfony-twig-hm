package com.qcoder.kotlin.observability

import kotlinx.serialization.Serializable
import java.util.concurrent.atomic.AtomicInteger
import java.util.concurrent.atomic.AtomicReference

/**
 * Simple thread-safe metrics collector
 */
object Metrics {
    private val requestCount = AtomicInteger(0)
    private val errorCount = AtomicInteger(0)
    private val responseTimes = mutableListOf<Long>()
    private val lock = Any()
    private val maxSamples = 10000

    fun record(statusCode: Int, durationMs: Long) {
        requestCount.incrementAndGet()
        if (statusCode >= 400) errorCount.incrementAndGet()
        synchronized(lock) {
            responseTimes.add(durationMs)
            if (responseTimes.size > maxSamples) responseTimes.removeAt(0)
        }
    }

    @Serializable
    data class Snapshot(
        val total_requests: Int,
        val error_count: Int,
        val error_rate: Double,
        val p50_ms: Long,
        val p99_ms: Long,
        val samples: Int
    )

    fun snapshot(): Snapshot {
        val total = requestCount.get()
        val errors = errorCount.get()
        val sorted: List<Long>
        synchronized(lock) {
            sorted = responseTimes.sorted()
        }
        val p50 = if (sorted.isNotEmpty()) sorted[(sorted.size * 0.5).toInt()] else 0L
        val p99 = if (sorted.size >= 100) sorted[(sorted.size * 0.99).toInt()] else sorted.lastOrNull() ?: 0L
        val errorRate = if (total > 0) errors.toDouble() / total else 0.0
        return Snapshot(total, errors, errorRate, p50, p99, sorted.size)
    }

    // Test‑only helper to reset internal state between tests
    internal fun clearForTest() {
        requestCount.set(0)
        errorCount.set(0)
        synchronized(lock) {
            responseTimes.clear()
        }
    }
}
