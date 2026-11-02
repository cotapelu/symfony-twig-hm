package com.example.app.monitoring.Entity

import java.time.LocalDateTime
import java.util.UUID

data class Alert(
    val id: String = UUID.randomUUID().toString(),
    val alertName: String,
    val alertType: AlertType,
    val severity: AlertSeverity,
    val condition: String,
    val description: String,
    val status: AlertStatus = AlertStatus.ACTIVE,
    val enabled: Boolean = true,
    val triggeredAt: LocalDateTime? = null,
    val resolvedAt: LocalDateTime? = null,
    val createdAt: LocalDateTime = LocalDateTime.now(),
    val updatedAt: LocalDateTime = LocalDateTime.now()
)

enum class AlertType {
    SYSTEM,
    SECURITY,
    PERFORMANCE,
    BUSINESS,
    CUSTOM
}

enum class AlertSeverity {
    LOW,
    MEDIUM,
    HIGH,
    CRITICAL
}

enum class AlertStatus {
    ACTIVE,
    TRIGGERED,
    RESOLVED,
    ACKNOWLEDGED,
    DISABLED
}
