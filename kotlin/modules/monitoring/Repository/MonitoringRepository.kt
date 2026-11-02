package monitoring

import monitoring.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.jetbrains.exposed.sql.*
import org.jetbrains.exposed.sql.transactions.transaction

class MetricRepository {
    suspend fun insertMetric(
        name: String,
        value: Double,
        unit: String,
        tags: String,
        timestamp: java.time.LocalDateTime
    ) = withContext(Dispatchers.IO) {
        transaction {
            Metrics.insert {
                it[Metrics.name] = name
                it[Metrics.value] = value
                it[Metrics.unit] = unit
                it[Metrics.tags] = tags
                it[Metrics.timestamp] = timestamp
            }
        }
    }

    suspend fun getMetricsByName(name: String, limit: Int = 100) = withContext(Dispatchers.IO) {
        transaction {
            Metrics.select { Metrics.name eq name }
                .orderBy(Metrics.timestamp to SortOrder.DESC)
                .limit(limit)
                .map { row ->
                    Metric(
                        name = row[Metrics.name],
                        value = row[Metrics.value],
                        unit = row[Metrics.unit],
                        tags = row[Metrics.tags],
                        timestamp = row[Metrics.timestamp]
                    )
                }
        }
    }
}

data class Metric(
    val name: String,
    val value: Double,
    val unit: String,
    val tags: String,
    val timestamp: java.time.LocalDateTime
)

class AlertRepository {
    suspend fun insertAlert(
        name: String,
        type: String,
        threshold: Double,
        currentValue: Double,
        severity: String,
        message: String?
    ) = withContext(Dispatchers.IO) {
        transaction {
            Alerts.insert {
                it[Alerts.name] = name
                it[Alerts.type] = type
                it[Alerts.threshold] = threshold
                it[Alerts.currentValue] = currentValue
                it[Alerts.severity] = severity
                it[Alerts.status] = "triggered"
                it[Alerts.triggeredAt] = java.time.LocalDateTime.now()
                it[Alerts.message] = message
            }
        }
    }

    suspend fun updateAlertStatus(alertId: Int, status: String) = withContext(Dispatchers.IO) {
        transaction {
            Alerts.update({ Alerts.id eq alertId }) {
                it[Alerts.status] = status
                if (status == "resolved") {
                    it[Alerts.resolvedAt] = java.time.LocalDateTime.now()
                }
            }
        }
    }

    suspend fun getActiveAlerts() = withContext(Dispatchers.IO) {
        transaction {
            Alerts.select { Alerts.status eq "triggered" }
                .orderBy(Alerts.triggeredAt to SortOrder.DESC)
                .map { row ->
                    Alert(
                        id = row[Alerts.id].value,
                        name = row[Alerts.name],
                        type = row[Alerts.type],
                        severity = row[Alerts.severity],
                        message = row[Alerts.message],
                        triggeredAt = row[Alerts.triggeredAt]
                    )
                }
        }
    }
}

data class Alert(
    val id: Int,
    val name: String,
    val type: String,
    val severity: String,
    val message: String?,
    val triggeredAt: java.time.LocalDateTime
)

class HealthCheckRepository {
    suspend fun updateHealthCheck(
        serviceName: String,
        status: String,
        responseTime: Int?,
        errorMessage: String?
    ) = withContext(Dispatchers.IO) {
        transaction {
            val existing = HealthChecks.select { HealthChecks.serviceName eq serviceName }
                .singleOrNull()

            if (existing != null) {
                HealthChecks.update({ HealthChecks.serviceName eq serviceName }) {
                    it[HealthChecks.status] = status
                    it[HealthChecks.responseTime] = responseTime
                    it[HealthChecks.lastChecked] = java.time.LocalDateTime.now()
                    it[HealthChecks.errorMessage] = errorMessage
                }
            } else {
                HealthChecks.insert {
                    it[HealthChecks.serviceName] = serviceName
                    it[HealthChecks.status] = status
                    it[HealthChecks.responseTime] = responseTime
                    it[HealthChecks.lastChecked] = java.time.LocalDateTime.now()
                    it[HealthChecks.errorMessage] = errorMessage
                }
            }
        }
    }

    suspend fun getAllHealthChecks() = withContext(Dispatchers.IO) {
        transaction {
            HealthChecks.selectAll()
                .map { row ->
                    HealthCheck(
                        serviceName = row[HealthChecks.serviceName],
                        status = row[HealthChecks.status],
                        responseTime = row[HealthChecks.responseTime],
                        lastChecked = row[HealthChecks.lastChecked],
                        errorMessage = row[HealthChecks.errorMessage]
                    )
                }
        }
    }
}

data class HealthCheck(
    val serviceName: String,
    val status: String,
    val responseTime: Int?,
    val lastChecked: java.time.LocalDateTime,
    val errorMessage: String?
)
