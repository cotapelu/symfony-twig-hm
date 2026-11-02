package baseline

import com.example.alert.AlertService
import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.BeforeEach
import org.junit.jupiter.api.Test
import java.time.Instant

/**
 * Unit tests for AlertService to increase Kotlin coverage.
 * Tests cover alert creation, retrieval, ack, resolve, rules, and statistics.
 */
class AlertServiceTest {

    private lateinit var service: AlertService

    @BeforeEach
    fun setup() {
        service = AlertService()
    }

    @Test
    fun `createAlert returns alert with correct fields`() {
        val alert = service.createAlert(
            title = "Test Alert",
            message = "Test message",
            severity = AlertService.AlertSeverity.HIGH,
            category = AlertService.AlertCategory.SYSTEM,
            source = "test"
        )

        assertEquals("Test Alert", alert.title)
        assertEquals("Test message", alert.message)
        assertEquals(AlertService.AlertSeverity.HIGH, alert.severity)
        assertEquals(AlertService.AlertCategory.SYSTEM, alert.category)
        assertEquals("test", alert.source)
        assertNotNull(alert.id)
        assertEquals(AlertService.AlertStatus.NEW, alert.status)
    }

    @Test
    fun `createAlert rejects blank title`() {
        val thrown = assertThrows(IllegalArgumentException::class.java) {
            service.createAlert(
                title = "",
                message = "msg",
                severity = AlertService.AlertSeverity.LOW,
                category = AlertService.AlertCategory.SYSTEM,
                source = "test"
            )
        }
        assertEquals("Title cannot be empty", thrown.message)
    }

    @Test
    fun `createAlert rejects blank message`() {
        val thrown = assertThrows(IllegalArgumentException::class.java) {
            service.createAlert(
                title = "title",
                message = "",
                severity = AlertService.AlertSeverity.LOW,
                category = AlertService.AlertCategory.SYSTEM,
                source = "test"
            )
        }
        assertEquals("Message cannot be empty", thrown.message)
    }

    @Test
    fun `getAlert returns null for unknown id`() {
        val alert = service.getAlert("unknown")
        assertNull(alert)
    }

    @Test
    fun `getAlerts filters by severity`() {
        // Create a few alerts with different severities
        service.createAlert("A1", "m1", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM, "src")
        service.createAlert("A2", "m2", AlertService.AlertSeverity.LOW, AlertService.AlertCategory.SYSTEM, "src")
        service.createAlert("A3", "m3", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SECURITY, "src")

        val highAlerts = service.getAlerts(severity = AlertService.AlertSeverity.HIGH)
        assertEquals(2, highAlerts.size)
        assertTrue(highAlerts.all { it.severity == AlertService.AlertSeverity.HIGH })
    }

    @Test
    fun `getAlerts filters by category`() {
        service.createAlert("A1", "m1", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM, "src")
        service.createAlert("A2", "m2", AlertService.AlertSeverity.LOW, AlertService.AlertCategory.SECURITY, "src")
        service.createAlert("A3", "m3", AlertService.AlertSeverity.MEDIUM, AlertService.AlertCategory.SECURITY, "src")

        val securityAlerts = service.getAlerts(category = AlertService.AlertCategory.SECURITY)
        assertEquals(2, securityAlerts.size)
        assertTrue(securityAlerts.all { it.category == AlertService.AlertCategory.SECURITY })
    }

    @Test
    fun `getAlerts filters by status`() {
        val alert1 = service.createAlert("A1", "m1", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM, "src")
        service.acknowledgeAlert(alert1.id, "user1")
        service.resolveAlert(alert1.id, "fixed")

        val newAlerts = service.getAlerts(status = AlertService.AlertStatus.NEW)
        assertEquals(0, newAlerts.size)

        val resolvedAlerts = service.getAlerts(status = AlertService.AlertStatus.RESOLVED)
        assertEquals(1, resolvedAlerts.size)
    }

    @Test
    fun `getAlerts respects limit and offset`() {
        // Create 5 alerts
        repeat(5) { i ->
            service.createAlert("A$i", "m$i", AlertService.AlertSeverity.MEDIUM, AlertService.AlertCategory.SYSTEM, "src")
        }

        val all = service.getAlerts(limit = 50)
        assertEquals(5, all.size)

        val firstTwo = service.getAlerts(limit = 2, offset = 0)
        assertEquals(2, firstTwo.size)

        val lastTwo = service.getAlerts(limit = 2, offset = 3)
        assertEquals(2, lastTwo.size)
    }

    @Test
    fun `acknowledgeAlert returns updated alert`() {
        val alert = service.createAlert("A1", "m1", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM, "src")
        val updated = service.acknowledgeAlert(alert.id, "user1")

        assertNotNull(updated)
        assertEquals(AlertService.AlertStatus.ACKNOWLEDGED, updated!!.status)
        assertNotNull(updated.acknowledgedAt)
    }

    @Test
    fun `acknowledgeAlert returns null for unknown alert`() {
        val result = service.acknowledgeAlert("unknown", "user1")
        assertNull(result)
    }

    @Test
    fun `assignAlert returns updated alert`() {
        val alert = service.createAlert("A1", "m1", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM, "src")
        val updated = service.assignAlert(alert.id, "user2")

        assertNotNull(updated)
        assertEquals("user2", updated!!.assignedTo)
        assertEquals(AlertService.AlertStatus.IN_PROGRESS, updated.status)
    }

    @Test
    fun `resolveAlert adds resolution metadata`() {
        val alert = service.createAlert("A1", "m1", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM, "src")
        val updated = service.resolveAlert(alert.id, "fixed by manual")

        assertNotNull(updated)
        assertEquals(AlertService.AlertStatus.RESOLVED, updated!!.status)
        assertNotNull(updated.resolvedAt)
        assertEquals("fixed by manual", updated.metadata["resolution"])
    }

    @Test
    fun `closeAlert changes status to closed`() {
        val alert = service.createAlert("A1", "m1", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM, "src")
        val updated = service.closeAlert(alert.id)

        assertNotNull(updated)
        assertEquals(AlertService.AlertStatus.CLOSED, updated!!.status)
    }

    @Test
    fun `createAlertRule adds rule`() {
        val rule = service.createAlertRule(
            name = "High CPU",
            condition = "cpu > 90",
            severity = AlertService.AlertSeverity.CRITICAL,
            category = AlertService.AlertCategory.PERFORMANCE
        )

        assertNotNull(rule.id)
        assertEquals("High CPU", rule.name)
        assertEquals("cpu > 90", rule.condition)
        assertEquals(AlertService.AlertSeverity.CRITICAL, rule.severity)
        assertTrue(rule.enabled)
    }

    @Test
    fun `createAlertRule rejects empty name`() {
        val thrown = assertThrows(IllegalArgumentException::class.java) {
            service.createAlertRule("", "cond", AlertService.AlertSeverity.LOW, AlertService.AlertCategory.SYSTEM)
        }
        assertEquals("Rule name cannot be empty", thrown.message)
    }

    @Test
    fun `createAlertRule rejects empty condition`() {
        val thrown = assertThrows(IllegalArgumentException::class.java) {
            service.createAlertRule("name", "", AlertService.AlertSeverity.LOW, AlertService.AlertCategory.SYSTEM)
        }
        assertEquals("Condition cannot be empty", thrown.message)
    }

    @Test
    fun `getAlertRules returns all rules`() {
        service.createAlertRule("R1", "cond1", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM)
        service.createAlertRule("R2", "cond2", AlertService.AlertSeverity.LOW, AlertService.AlertCategory.SECURITY)

        val all = service.getAlertRules()
        assertEquals(2, all.size)
    }

    @Test
    fun `getAlertRules enabledOnly filters correctly`() {
        val r1 = service.createAlertRule("R1", "cond1", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM)
        val r2 = service.createAlertRule("R2", "cond2", AlertService.AlertSeverity.LOW, AlertService.AlertCategory.SECURITY)
        service.setAlertRuleEnabled(r2.id, false)

        val enabled = service.getAlertRules(enabledOnly = true)
        assertEquals(1, enabled.size)
        assertEquals(r1.id, enabled[0].id)
    }

    @Test
    fun `setAlertRuleEnabled toggles rule`() {
        val rule = service.createAlertRule("R1", "cond", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM)
        assertTrue(rule.enabled)

        val disabled = service.setAlertRuleEnabled(rule.id, false)
        assertFalse(disabled!!.enabled)

        val reenabled = service.setAlertRuleEnabled(rule.id, true)
        assertTrue(reenabled!!.enabled)
    }

    @Test
    fun `subscribeUser adds subscription`() {
        service.subscribeUser("user1", AlertService.AlertCategory.SYSTEM)
        val subs = service.getUserSubscriptions("user1")
        assertTrue(subs.contains("SYSTEM"))
    }

    @Test
    fun `unsubscribeUser removes subscription`() {
        service.subscribeUser("user1", AlertService.AlertCategory.SYSTEM)
        service.unsubscribeUser("user1", AlertService.AlertCategory.SYSTEM)
        val subs = service.getUserSubscriptions("user1")
        assertFalse(subs.contains("SYSTEM"))
    }

    @Test
    fun `getUserAlerts returns alerts based on subscriptions`() {
        service.subscribeUser("user1", AlertService.AlertCategory.SYSTEM)
        service.createAlert(
            "A1", "m1", AlertService.AlertSeverity.HIGH,
            AlertService.AlertCategory.SYSTEM, "src"
        )
        service.createAlert(
            "A2", "m2", AlertService.AlertSeverity.LOW,
            AlertService.AlertCategory.SECURITY, "src"
        )

        val userAlerts = service.getUserAlerts("user1", limit = 20)
        assertEquals(1, userAlerts.size)
        assertEquals("A1", userAlerts[0].title)
    }

    @Test
    fun `getAlertStatistics computes counts correctly`() {
        service.createAlert("A1", "m1", AlertService.AlertSeverity.CRITICAL, AlertService.AlertCategory.SYSTEM, "src")
        service.createAlert("A2", "m2", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM, "src")
        service.createAlert("A3", "m3", AlertService.AlertSeverity.CRITICAL, AlertService.AlertCategory.SECURITY, "src")
        // Acknowledge one alert (acknowledged still counts as open)
        val firstAlertId = service.getAlerts().first().id
        service.acknowledgeAlert(firstAlertId, "user")

        val stats = service.getAlertStatistics()

        assertEquals(3, stats.totalAlerts)
        // All alerts are open (NEW, ACKNOWLEDGED, or IN_PROGRESS); none are RESOLVED/CLOSED
        assertEquals(3, stats.openAlerts)
        assertEquals(2, stats.bySeverity[AlertService.AlertSeverity.CRITICAL]!!.toInt())
        assertEquals(2, stats.criticalAlerts)
    }

    @Test
    fun `getNextAlert polls from queue`() {
        // Queue initially empty
        val next1 = service.getNextAlert()
        assertNull(next1)

        // Create alerts (they are added to queue)
        service.createAlert("A1", "m1", AlertService.AlertSeverity.HIGH, AlertService.AlertCategory.SYSTEM, "src")
        service.createAlert("A2", "m2", AlertService.AlertSeverity.MEDIUM, AlertService.AlertCategory.SYSTEM, "src")

        val next2 = service.getNextAlert()
        assertNotNull(next2)
        // One remains
        val next3 = service.getNextAlert()
        assertNotNull(next3)
        val next4 = service.getNextAlert()
        assertNull(next4)
    }
}
