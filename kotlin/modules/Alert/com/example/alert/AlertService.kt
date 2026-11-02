package com.example.alert

import java.time.Instant
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap
import java.util.concurrent.ConcurrentLinkedQueue

/**
 * Alert Service - Manages system alerts and notifications
 */
class AlertService {
    private val alerts = ConcurrentHashMap<String, Alert>()
    private val alertQueue = ConcurrentLinkedQueue<Alert>()
    private val alertRules = ConcurrentHashMap<String, AlertRule>()
    private val userSubscriptions = ConcurrentHashMap<String, MutableSet<String>>()

    data class Alert(
        val id: String = UUID.randomUUID().toString(),
        val title: String,
        val message: String,
        val severity: AlertSeverity,
        val category: AlertCategory,
        val source: String,
        val status: AlertStatus = AlertStatus.NEW,
        val assignedTo: String? = null,
        val metadata: Map<String, String> = emptyMap(),
        val createdAt: Instant = Instant.now(),
        val acknowledgedAt: Instant? = null,
        val resolvedAt: Instant? = null
    )

    data class AlertRule(
        val id: String = UUID.randomUUID().toString(),
        val name: String,
        val condition: String,
        val severity: AlertSeverity,
        val category: AlertCategory,
        val enabled: Boolean = true,
        val createdAt: Instant = Instant.now()
    )

    enum class AlertSeverity {
        CRITICAL, HIGH, MEDIUM, LOW, INFO
    }

    enum class AlertCategory {
        SYSTEM, SECURITY, PERFORMANCE, BUSINESS, MAINTENANCE
    }

    enum class AlertStatus {
        NEW, ACKNOWLEDGED, IN_PROGRESS, RESOLVED, CLOSED
    }

    /**
     * Create a new alert
     */
    fun createAlert(
        title: String,
        message: String,
        severity: AlertSeverity,
        category: AlertCategory,
        source: String,
        metadata: Map<String, String> = emptyMap()
    ): Alert {
        require(title.isNotBlank()) { "Title cannot be empty" }
        require(message.isNotBlank()) { "Message cannot be empty" }

        val alert = Alert(
            title = title,
            message = message,
            severity = severity,
            category = category,
            source = source,
            metadata = metadata
        )

        alerts[alert.id] = alert
        alertQueue.add(alert)

        return alert
    }

    /**
     * Get alert by ID
     */
    fun getAlert(alertId: String): Alert? {
        return alerts[alertId]
    }

    /**
     * Get all alerts with optional filtering
     */
    fun getAlerts(
        severity: AlertSeverity? = null,
        category: AlertCategory? = null,
        status: AlertStatus? = null,
        limit: Int = 50,
        offset: Int = 0
    ): List<Alert> {
        var result = alerts.values.toList()

        severity?.let { sev ->
            result = result.filter { it.severity == sev }
        }
        category?.let { cat ->
            result = result.filter { it.category == cat }
        }
        status?.let { stat ->
            result = result.filter { it.status == stat }
        }

        return result
            .sortedByDescending { it.createdAt }
            .drop(offset)
            .take(limit)
    }

    /**
     * Get unacknowledged alerts
     */
    fun getUnacknowledgedAlerts(): List<Alert> {
        return alerts.values
            .filter { it.status == AlertStatus.NEW || it.status == AlertStatus.ACKNOWLEDGED }
            .sortedByDescending { it.severity.ordinal }
    }

    /**
     * Acknowledge an alert
     */
    fun acknowledgeAlert(alertId: String, userId: String): Alert? {
        val alert = alerts[alertId] ?: return null

        val updatedAlert = alert.copy(
            status = AlertStatus.ACKNOWLEDGED,
            acknowledgedAt = Instant.now()
        )

        alerts[alertId] = updatedAlert
        return updatedAlert
    }

    /**
     * Assign alert to user
     */
    fun assignAlert(alertId: String, userId: String): Alert? {
        val alert = alerts[alertId] ?: return null

        val updatedAlert = alert.copy(
            assignedTo = userId,
            status = AlertStatus.IN_PROGRESS
        )

        alerts[alertId] = updatedAlert
        return updatedAlert
    }

    /**
     * Resolve an alert
     */
    fun resolveAlert(alertId: String, resolution: String): Alert? {
        val alert = alerts[alertId] ?: return null

        val updatedAlert = alert.copy(
            status = AlertStatus.RESOLVED,
            resolvedAt = Instant.now(),
            metadata = alert.metadata + ("resolution" to resolution)
        )

        alerts[alertId] = updatedAlert
        return updatedAlert
    }

    /**
     * Close an alert
     */
    fun closeAlert(alertId: String): Alert? {
        val alert = alerts[alertId] ?: return null

        val updatedAlert = alert.copy(status = AlertStatus.CLOSED)
        alerts[alertId] = updatedAlert
        return updatedAlert
    }

    /**
     * Create an alert rule
     */
    fun createAlertRule(
        name: String,
        condition: String,
        severity: AlertSeverity,
        category: AlertCategory
    ): AlertRule {
        require(name.isNotBlank()) { "Rule name cannot be empty" }
        require(condition.isNotBlank()) { "Condition cannot be empty" }

        val rule = AlertRule(
            name = name,
            condition = condition,
            severity = severity,
            category = category
        )

        alertRules[rule.id] = rule
        return rule
    }

    /**
     * Get alert rules
     */
    fun getAlertRules(enabledOnly: Boolean = false): List<AlertRule> {
        return if (enabledOnly) {
            alertRules.values.filter { it.enabled }
        } else {
            alertRules.values.toList()
        }
    }

    /**
     * Enable/disable alert rule
     */
    fun setAlertRuleEnabled(ruleId: String, enabled: Boolean): AlertRule? {
        val rule = alertRules[ruleId] ?: return null
        val updatedRule = rule.copy(enabled = enabled)
        alertRules[ruleId] = updatedRule
        return updatedRule
    }

    /**
     * Subscribe user to alert category
     */
    fun subscribeUser(userId: String, category: AlertCategory) {
        val key = "${userId}:${category.name}"
        userSubscriptions.getOrPut(userId) { mutableSetOf() }.add(category.name)
    }

    /**
     * Unsubscribe user from alert category
     */
    fun unsubscribeUser(userId: String, category: AlertCategory) {
        userSubscriptions[userId]?.remove(category.name)
    }

    /**
     * Get user subscriptions
     */
    fun getUserSubscriptions(userId: String): Set<String> {
        return userSubscriptions[userId] ?: emptySet()
    }

    /**
     * Get alerts for user based on subscriptions
     */
    fun getUserAlerts(userId: String, limit: Int = 20): List<Alert> {
        val subscriptions = userSubscriptions[userId] ?: return emptyList()

        return alerts.values
            .filter { it.category.name in subscriptions }
            .sortedByDescending { it.createdAt }
            .take(limit)
    }

    /**
     * Get alert statistics
     */
    fun getAlertStatistics(): AlertStatistics {
        val totalAlerts = alerts.size

        val bySeverity = alerts.values.groupBy { it.severity }
            .mapValues { it.value.size }

        val byStatus = alerts.values.groupBy { it.status }
            .mapValues { it.value.size }

        val byCategory = alerts.values.groupBy { it.category }
            .mapValues { it.value.size }

        val openAlerts = alerts.values.count {
            it.status == AlertStatus.NEW ||
            it.status == AlertStatus.ACKNOWLEDGED ||
            it.status == AlertStatus.IN_PROGRESS
        }

        return AlertStatistics(
            totalAlerts = totalAlerts,
            openAlerts = openAlerts,
            bySeverity = bySeverity,
            byStatus = byStatus,
            byCategory = byCategory,
            criticalAlerts = bySeverity[AlertSeverity.CRITICAL] ?: 0
        )
    }

    /**
     * Get next alert from queue (for processing)
     */
    fun getNextAlert(): Alert? {
        return alertQueue.poll()
    }

    data class AlertStatistics(
        val totalAlerts: Int,
        val openAlerts: Int,
        val bySeverity: Map<AlertSeverity, Int>,
        val byStatus: Map<AlertStatus, Int>,
        val byCategory: Map<AlertCategory, Int>,
        val criticalAlerts: Int
    )
}