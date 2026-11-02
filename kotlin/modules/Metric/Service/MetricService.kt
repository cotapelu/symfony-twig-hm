package com.example.metric

import java.time.Instant
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap
import java.util.concurrent.ConcurrentLinkedQueue

/**
 * Metric Service - Collects and aggregates system metrics
 */
class MetricService {
    private val metrics = ConcurrentHashMap<String, MutableList<MetricPoint>>()
    private val counters = ConcurrentHashMap<String, Long>()
    private val gauges = ConcurrentHashMap<String, Double>()
    private val histograms = ConcurrentHashMap<String, MutableList<Double>>()

    data class MetricPoint(
        val name: String,
        val value: Double,
        val timestamp: Instant = Instant.now(),
        val tags: Map<String, String> = emptyMap()
    )

    data class MetricQuery(
        val name: String,
        val startTime: Instant? = null,
        val endTime: Instant? = null,
        val tags: Map<String, String> = emptyMap(),
        val aggregation: Aggregation = Aggregation.AVG,
        val intervalSeconds: Long? = null
    )

    enum class Aggregation {
        AVG, SUM, MIN, MAX, COUNT, P50, P90, P95, P99
    }

    /**
     * Record a metric point
     */
    fun recordMetric(name: String, value: Double, tags: Map<String, String> = emptyMap()) {
        val point = MetricPoint(name = name, value = value, tags = tags)
        val points = metrics.getOrPut(name) { mutableListOf() }
        points.add(point)

        // Keep only last 10000 points per metric
        if (points.size > 10000) {
            // Remove oldest entries to keep size bounded
            points.subList(0, points.size - 10000).clear()
        }
    }

    /**
     * Increment a counter
     */
    fun incrementCounter(name: String, delta: Long = 1) {
        counters[name] = (counters[name] ?: 0) + delta
    }

    /**
     * Get counter value
     */
    fun getCounter(name: String): Long {
        return counters[name] ?: 0
    }

    /**
     * Reset counter
     */
    fun resetCounter(name: String): Long {
        return counters.remove(name) ?: 0
    }

    /**
     * Set gauge value
     */
    fun setGauge(name: String, value: Double) {
        gauges[name] = value
    }

    /**
     * Get gauge value
     */
    fun getGauge(name: String): Double? {
        return gauges[name]
    }

    /**
     * Record histogram value
     */
    fun recordHistogram(name: String, value: Double) {
        val values = histograms.getOrPut(name) { mutableListOf() }
        values.add(value)

        // Keep only last 10000 values
        if (values.size > 10000) {
            values.subList(0, values.size - 10000).clear()
        }
    }

    /**
     * Get histogram statistics
     */
    fun getHistogramStats(name: String): HistogramStats? {
        val values = histograms[name] ?: return null

        if (values.isEmpty()) return null

        val sorted = values.sorted()
        val count = sorted.size

        return HistogramStats(
            count = count,
            sum = values.sum(),
            avg = values.average(),
            min = sorted.first(),
            max = sorted.last(),
            p50 = sorted[count / 2],
            p90 = sorted[(count * 0.9).toInt()],
            p95 = sorted[(count * 0.95).toInt()],
            p99 = sorted[(count * 0.99).toInt()]
        )
    }

    /**
     * Query metrics
     */
    fun queryMetrics(query: MetricQuery): List<MetricPoint> {
        val points = metrics[query.name] ?: return emptyList()

        var result: List<MetricPoint> = points

        // Filter by time range
        query.startTime?.let { start ->
            result = result.filter { it.timestamp.isAfter(start) }
        }
        query.endTime?.let { end ->
            result = result.filter { it.timestamp.isBefore(end) }
        }

        // Filter by tags
        if (query.tags.isNotEmpty()) {
            result = result.filter { point ->
                query.tags.all { (key, value) -> point.tags[key] == value }
            }
        }

        return result.sortedBy { it.timestamp }
    }

    /**
     * Aggregate metrics
     */
    fun aggregateMetrics(query: MetricQuery): Double {
        val points = queryMetrics(query)

        if (points.isEmpty()) return 0.0

        val values = points.map { it.value }

        return when (query.aggregation) {
            Aggregation.AVG -> values.average()
            Aggregation.SUM -> values.sum()
            Aggregation.MIN -> values.minOrNull() ?: 0.0
            Aggregation.MAX -> values.maxOrNull() ?: 0.0
            Aggregation.COUNT -> values.size.toDouble()
            Aggregation.P50 -> calculatePercentile(values, 0.5)
            Aggregation.P90 -> calculatePercentile(values, 0.9)
            Aggregation.P95 -> calculatePercentile(values, 0.95)
            Aggregation.P99 -> calculatePercentile(values, 0.99)
        }
    }

    private fun calculatePercentile(sortedValues: List<Double>, percentile: Double): Double {
        if (sortedValues.isEmpty()) return 0.0
        val index = (sortedValues.size * percentile).toInt().coerceIn(0, sortedValues.size - 1)
        return sortedValues[index]
    }

    /**
     * Get time series data
     */
    fun getTimeSeries(
        name: String,
        startTime: Instant,
        endTime: Instant,
        intervalSeconds: Long
    ): List<TimeSeriesPoint> {
        val points = queryMetrics(MetricQuery(
            name = name,
            startTime = startTime,
            endTime = endTime
        ))

        if (points.isEmpty()) return emptyList()

        // Group by interval
        val intervalMs = intervalSeconds * 1000
        val grouped = points.groupBy { point ->
            val epochMs = point.timestamp.toEpochMilli()
            (epochMs / intervalMs) * intervalMs
        }

        return grouped.map { (interval, intervalPoints) ->
            TimeSeriesPoint(
                timestamp = Instant.ofEpochMilli(interval),
                value = intervalPoints.map { it.value }.average(),
                count = intervalPoints.size
            )
        }.sortedBy { it.timestamp }
    }

    /**
     * Get all metric names
     */
    fun getMetricNames(): List<String> {
        return metrics.keys.toList()
    }

    /**
     * Get all counter names
     */
    fun getCounterNames(): List<String> {
        return counters.keys.toList()
    }

    /**
     * Get all gauge names
     */
    fun getGaugeNames(): List<String> {
        return gauges.keys.toList()
    }

    /**
     * Get histogram names
     */
    fun getHistogramNames(): List<String> {
        return histograms.keys.toList()
    }

    /**
     * Get all current values
     */
    fun getAllCurrentValues(): Map<String, Any> {
        val result = mutableMapOf<String, Any>()

        counters.forEach { (name, value) ->
            result["counter.$name"] = value
        }

        gauges.forEach { (name, value) ->
            result["gauge.$name"] = value
        }

        histograms.forEach { (name, values) ->
            result["histogram.$name.count"] = values.size
            result["histogram.$name.sum"] = values.sum()
            result["histogram.$name.avg"] = values.average()
        }

        return result
    }

    /**
     * Clear old metrics
     */
    fun clearOldMetrics(olderThanSeconds: Long = 86400): Int {
        val cutoff = Instant.now().minusSeconds(olderThanSeconds)
        var cleared = 0

        metrics.forEach { (name, points) ->
            val originalSize = points.size
            points.retainAll { it.timestamp.isAfter(cutoff) }
            cleared += originalSize - points.size
        }

        return cleared
    }

    /**
     * Get service health
     */
    fun getHealth(): ServiceHealth {
        return ServiceHealth(
            status = HealthStatus.HEALTHY,
            metricCount = metrics.values.sumOf { it.size },
            counterCount = counters.size,
            gaugeCount = gauges.size,
            histogramCount = histograms.size,
            timestamp = Instant.now()
        )
    }

    data class TimeSeriesPoint(
        val timestamp: Instant,
        val value: Double,
        val count: Int
    )

    data class HistogramStats(
        val count: Int,
        val sum: Double,
        val avg: Double,
        val min: Double,
        val max: Double,
        val p50: Double,
        val p90: Double,
        val p95: Double,
        val p99: Double
    )

    data class ServiceHealth(
        val status: HealthStatus,
        val metricCount: Int,
        val counterCount: Int,
        val gaugeCount: Int,
        val histogramCount: Int,
        val timestamp: Instant
    )

    enum class HealthStatus {
        HEALTHY, DEGRADED, UNHEALTHY
    }
}