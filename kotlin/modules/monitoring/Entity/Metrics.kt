package monitoring

import org.jetbrains.exposed.dao.id.IntIdTable
import org.jetbrains.exposed.sql.Column
import org.jetbrains.exposed.sql.javatime.datetime

object Metrics : IntIdTable("metrics") {
    val name = varchar("name", 255)
    val value = double("value")
    val unit = varchar("unit", 50)
    val tags = text("tags")
    val timestamp = datetime("timestamp")
}

object Alerts : IntIdTable("alerts") {
    val name = varchar("name", 255)
    val type = varchar("type", 50)
    val threshold = double("threshold")
    val currentValue = double("current_value")
    val severity = varchar("severity", 20)
    val status = varchar("status", 20)
    val triggeredAt = datetime("triggered_at")
    val resolvedAt = datetime("resolved_at").nullable()
    val message = text("message").nullable()
}

object HealthChecks : IntIdTable("health_checks") {
    val serviceName = varchar("service_name", 255)
    val status = varchar("status", 20)
    val responseTime = integer("response_time").nullable()
    val lastChecked = datetime("last_checked")
    val errorMessage = text("error_message").nullable()
}
