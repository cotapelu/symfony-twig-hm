package com.example.metric

import com.qcoder.kotlin.observability.CorrelationId
import org.slf4j.LoggerFactory
import java.time.Instant

/**
 * Metric Service with Observability features
 * Adds correlation ID logging and health checks
 */
class ObservabilityMetricService {
    private val delegate = MetricService()
    private val logger = LoggerFactory.getLogger("metrics")

    fun recordMetric(name: String, value: Double, tags: Map<String, String> = emptyMap()) {
        val correlationId = CorrelationId.generate()
        logger.info("Metric recorded - name: $name, value: $value, tags: $tags, correlation_id: $correlationId")
        delegate.recordMetric(name, value, tags)
    }

    fun incrementCounter(name: String, delta: Long = 1) {
        delegate.incrementCounter(name, delta)
    }

    fun recordHistogram(name: String, value: Double) {
        delegate.recordHistogram(name, value)
    }

    fun getHealthReport(): Map<String, Any> {
        val health = delegate.getHealth()
        return mapOf(
            "status" to health.status,
            "timestamp" to health.timestamp,
            "metrics" to mapOf(
                "points" to health.metricCount,
                "counters" to health.counterCount,
                "gauges" to health.gaugeCount,
                "histograms" to health.histogramCount
            )
        )
    }

    // Expose other MetricService methods as needed...
}
