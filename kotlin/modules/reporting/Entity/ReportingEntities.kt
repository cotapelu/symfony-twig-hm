package reporting

import org.jetbrains.exposed.dao.id.IntIdTable
import org.jetbrains.exposed.sql.Column
import org.jetbrains.exposed.sql.javatime.datetime

object Reports : IntIdTable("reports") {
    val name = varchar("name", 255)
    val type = varchar("type", 50)
    val description = text("description").nullable()
    val createdBy = varchar("created_by", 255)
    val status = varchar("status", 20)
    val scheduledAt = datetime("scheduled_at").nullable()
    val completedAt = datetime("completed_at").nullable()
    val expiresAt = datetime("expires_at").nullable()
    val fileSize = long("file_size").nullable()
    val fileUrl = varchar("file_url", 500).nullable()
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object ReportTemplates : IntIdTable("report_templates") {
    val name = varchar("name", 255)
    val description = text("description").nullable()
    val type = varchar("type", 50)
    val query = text("query")
    val parameters = text("parameters").nullable()
    val schedule = varchar("schedule", 50).nullable()
    val format = varchar("format", 20)
    val isActive = bool("is_active")
    val createdBy = varchar("created_by", 255)
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object ReportSchedules : IntIdTable("report_schedules") {
    val templateId = reference("template_id", ReportTemplates)
    val name = varchar("name", 255)
    val cronExpression = varchar("cron_expression", 100)
    val recipients = text("recipients")
    val format = varchar("format", 20)
    val isActive = bool("is_active")
    val lastRunAt = datetime("last_run_at").nullable()
    val nextRunAt = datetime("next_run_at").nullable()
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object ReportData : IntIdTable("report_data") {
    val reportId = reference("report_id", Reports)
    val key = varchar("key", 255)
    val value = text("value")
    val type = varchar("type", 50)
    val createdAt = datetime("created_at")
}
