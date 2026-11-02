package pushnotifications

import org.jetbrains.exposed.dao.id.IntIdTable
import org.jetbrains.exposed.sql.Column
import org.jetbrains.exposed.sql.javatime.datetime

object DeviceTokens : IntIdTable("device_tokens") {
    val userId = varchar("user_id", 255)
    val deviceToken = varchar("device_token", 500)
    val deviceType = varchar("device_type", 50)
    val osVersion = varchar("os_version", 50).nullable()
    val appVersion = varchar("app_version", 50).nullable()
    val isActive = bool("is_active")
    val lastUsedAt = datetime("last_used_at").nullable()
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object Notifications : IntIdTable("notifications") {
    val title = varchar("title", 255)
    val body = text("body")
    val data = text("data").nullable()
    val type = varchar("type", 50)
    val priority = varchar("priority", 20)
    val userIds = text("user_ids")
    val tokens = text("tokens")
    val platform = varchar("platform", 50)
    val status = varchar("status", 20)
    val sentAt = datetime("sent_at").nullable()
    val failedAt = datetime("failed_at").nullable()
    val failureReason = text("failure_reason").nullable()
    val createdAt = datetime("created_at")
}

object Campaigns : IntIdTable("notification_campaigns") {
    val name = varchar("name", 255)
    val description = text("description").nullable()
    val title = varchar("title", 255)
    val body = text("body")
    val data = text("data").nullable()
    val type = varchar("type", 50)
    val platform = varchar("platform", 50)
    val scheduledAt = datetime("scheduled_at").nullable()
    val sentAt = datetime("sent_at").nullable()
    val status = varchar("status", 20)
    val totalRecipients = integer("total_recipients")
    val sentCount = integer("sent_count").default(0)
    val failedCount = integer("failed_count").default(0)
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object NotificationLogs : IntIdTable("notification_logs") {
    val notificationId = reference("notification_id", Notifications)
    val campaignId = reference("campaign_id", Campaigns).nullable()
    val deviceToken = varchar("device_token", 500)
    val platform = varchar("platform", 50)
    val status = varchar("status", 20)
    val response = text("response").nullable()
    val error = text("error").nullable()
    val sentAt = datetime("sent_at")
    val createdAt = datetime("created_at")
}
