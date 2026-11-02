package emailservice

import org.jetbrains.exposed.dao.id.IntIdTable
import org.jetbrains.exposed.sql.Column
import org.jetbrains.exposed.sql.javatime.datetime

object Emails : IntIdTable("emails") {
    val from = varchar("from", 255)
    val to = text("to")
    val cc = text("cc").nullable()
    val bcc = text("bcc").nullable()
    val subject = varchar("subject", 500)
    val bodyHtml = text("body_html")
    val bodyText = text("body_text").nullable()
    val status = varchar("status", 20)
    val priority = varchar("priority", 20).default("normal")
    val sentAt = datetime("sent_at").nullable()
    val failedAt = datetime("failed_at").nullable()
    val errorMessage = text("error_message").nullable()
    val createdAt = datetime("created_at")
}

object EmailTemplates : IntIdTable("email_templates") {
    val name = varchar("name", 255)
    val description = text("description").nullable()
    val subject = varchar("subject", 500)
    val bodyHtml = text("body_html")
    val bodyText = text("body_text").nullable()
    val variables = text("variables").nullable()
    val category = varchar("category", 100).nullable()
    val isActive = bool("is_active")
    val createdBy = varchar("created_by", 255)
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object EmailCampaigns : IntIdTable("email_campaigns") {
    val name = varchar("name", 255)
    val description = text("description").nullable()
    val templateId = reference("template_id", EmailTemplates).nullable()
    val from = varchar("from", 255)
    val subject = varchar("subject", 500)
    val bodyHtml = text("body_html")
    val recipientCount = integer("recipient_count")
    val sentCount = integer("sent_count").default(0)
    val openedCount = integer("opened_count").default(0)
    val clickedCount = integer("clicked_count").default(0)
    val failedCount = integer("failed_count").default(0)
    val status = varchar("status", 20)
    val scheduledAt = datetime("scheduled_at").nullable()
    val sentAt = datetime("sent_at").nullable()
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}
