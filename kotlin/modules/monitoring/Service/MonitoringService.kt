package monitoring

import monitoring.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.time.LocalDateTime

class MonitoringService(
    private val metricRepository: MetricRepository,
    private val alertRepository: AlertRepository,
    private val healthCheckRepository: HealthCheckRepository
) {
    suspend fun recordMetric(
        name: String,
        value: Double,
        unit: String = "count",
        tags: Map<String, String> = emptyMap()
    ) = withContext(Dispatchers.IO) {
        val tagsJson = tags.entries.joinToString(",", " {\"", "\"}") { "\"${it.key}\":\"${it.value}\"" }
        metricRepository.insertMetric(
            name = name,
            value = value,
            unit = unit,
            tags = "[$tagsJson]",
            timestamp = LocalDateTime.now()
        )
    }

    suspend fun getMetricsByName(name: String, limit: Int = 100): List<Metric> = withContext(Dispatchers.IO) {
        metricRepository.getMetricsByName(name, limit)
    }

    suspend fun getActiveAlerts(): List<Alert> = withContext(Dispatchers.IO) {
        alertRepository.getActiveAlerts()
    }

    suspend fun resolveAlert(alertId: Int) = withContext(Dispatchers.IO) {
        alertRepository.updateAlertStatus(alertId, "resolved")
    }

    suspend fun checkThresholds(metricName: String, currentValue: Double, rules: List<AlertRule>) = withContext(Dispatchers.IO) {
        val metrics = metricRepository.getMetricsByName(metricName, limit = 10)
        if (metrics.isEmpty()) return@withContext

        val avgValue = metrics.map { it.value }.average()

        for (rule in rules) {
            var triggered = false
            when (rule.condition) {
                "greater_than" -> triggered = currentValue > rule.threshold
                "less_than" -> triggered = currentValue < rule.threshold
                "equals" -> triggered = currentValue == rule.threshold
                "avg_greater_than" -> triggered = avgValue > rule.threshold
                "avg_less_than" -> triggered = avgValue < rule.threshold
            }

            if (triggered) {
                alertRepository.insertAlert(
                    name = rule.name,
                    type = rule.type,
                    threshold = rule.threshold,
                    currentValue = currentValue,
                    severity = rule.severity,
                    message = "${rule.name} triggered: current=$currentValue, threshold=${rule.threshold}"
                )
            }
        }
    }

    suspend fun performHealthCheck(serviceUrl: String, serviceName: String) = withContext(Dispatchers.IO) {
        val startTime = System.currentTimeMillis()
        try {
            java.net.URL(serviceUrl).openStream().close()
            val responseTime = (System.currentTimeMillis() - startTime).toInt()
            healthCheckRepository.updateHealthCheck(
                serviceName = serviceName,
                status = if (responseTime < 5000) "healthy" else "degraded",
                responseTime = responseTime,
                errorMessage = null
            )
        } catch (e: Exception) {
            healthCheckRepository.updateHealthCheck(
                serviceName = serviceName,
                status = "unhealthy",
                responseTime = null,
                errorMessage = e.message
            )
        }
    }

    suspend fun getSystemHealth(): HealthStatus = withContext(Dispatchers.IO) {
        val healthChecks = healthCheckRepository.getAllHealthChecks()
        val unhealthyCount = healthChecks.count { it.status == "unhealthy" }
        val degradedCount = healthChecks.count { it.status == "degraded" }

        HealthStatus(
            overall = when {
                unhealthyCount > 0 -> "unhealthy"
                degradedCount > 0 -> "degraded"
                else -> "healthy"
            },
            services = healthChecks,
            activeAlerts = alertRepository.getActiveAlerts()
        )
    }
}

data class AlertRule(
    val name: String,
    val type: String,
    val condition: String,
    val threshold: Double,
    val severity: String
)

data class HealthStatus(
    val overall: String,
    val services: List<HealthCheck>,
    val activeAlerts: List<Alert>
)
