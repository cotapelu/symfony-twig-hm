package com.example.alert

import com.example.alert.AlertService.AlertCategory
import com.example.alert.AlertService.AlertSeverity
import com.example.alert.AlertService.AlertStatus
import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import java.time.Instant

class AlertServiceTest {

    private val service = AlertService()

    @Test
    fun `createAlert should return alert with required fields`() {
        val alert = service.createAlert(
            title = "Test Alert",
            message = "Test message",
            severity = AlertSeverity.HIGH,
            category = AlertCategory.SYSTEM,
            source = "test"
        )

        assertNotNull(alert.id)
        assertEquals("Test Alert", alert.title)
        assertEquals("Test message", alert.message)
        assertEquals(AlertSeverity.HIGH, alert.severity)
        assertEquals(AlertCategory.SYSTEM, alert.category)
        assertEquals("test", alert.source)
        assertEquals(AlertStatus.NEW, alert.status)
    }

    @Test
    fun `createAlert should throw for empty title`() {
        assertThrows(IllegalArgumentException::class.java) {
            service.createAlert(
                title = "",
                message = "msg",
                severity = AlertSeverity.HIGH,
                category = AlertCategory.SYSTEM,
                source = "test"
            )
        }
    }

    @Test
    fun `getAlert should return null for unknown id`() {
        val result = service.getAlert("unknown")
        assertNull(result)
    }

    @Test
    fun `acknowledgeAlert should update status`() {
        val alert = service.createAlert(
            title = "Ack Test",
            message = "msg",
            severity = AlertSeverity.MEDIUM,
            category = AlertCategory.BUSINESS,
            source = "test"
        )
        val acked = service.acknowledgeAlert(alert.id, "user1")
        assertNotNull(acked)
        assertEquals(AlertStatus.ACKNOWLEDGED, acked!!.status)
        assertNotNull(acked.acknowledgedAt)
    }

    @Test
    fun `resolveAlert should set resolved status`() {
        val alert = service.createAlert(
            title = "Resolve Test",
            message = "msg",
            severity = AlertSeverity.LOW,
            category = AlertCategory.MAINTENANCE,
            source = "test"
        )
        val resolved = service.resolveAlert(alert.id, "fixed")
        assertNotNull(resolved)
        assertEquals(AlertStatus.RESOLVED, resolved!!.status)
        assertNotNull(resolved.resolvedAt)
        assertEquals("fixed", resolved.metadata["resolution"])
    }

    @Test
    fun `getAlerts should filter by severity`() {
        service.createAlert("A", "msg", AlertSeverity.HIGH, AlertCategory.SYSTEM, "src")
        service.createAlert("B", "msg", AlertSeverity.LOW, AlertCategory.SYSTEM, "src")
        service.createAlert("C", "msg", AlertSeverity.HIGH, AlertCategory.SYSTEM, "src")

        val highAlerts = service.getAlerts(severity = AlertSeverity.HIGH)
        assertEquals(2, highAlerts.size)
    }

    @Test
    fun `getUnacknowledgedAlerts should return new and acknowledged alerts`() {
        service.createAlert("New1", "msg", AlertSeverity.MEDIUM, AlertCategory.SYSTEM, "src")
        val a = service.createAlert("New2", "msg", AlertSeverity.MEDIUM, AlertCategory.SYSTEM, "src")
        service.acknowledgeAlert(a.id, "user")
        service.createAlert("Resolved", "msg", AlertSeverity.MEDIUM, AlertCategory.SYSTEM, "src").let {
            service.resolveAlert(it.id, "done")
        }

        val unacked = service.getUnacknowledgedAlerts()
        assertEquals(2, unacked.size)
    }
}
