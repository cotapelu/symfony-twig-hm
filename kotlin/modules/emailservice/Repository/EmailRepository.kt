package emailservice

import java.time.LocalDateTime
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap

data class EmailQueue(
    val id: String = UUID.randomUUID().toString(),
    val recipient: String,
    val subject: String,
    val body: String,
    var status: String = "pending",
    val priority: Int = 0,
    val createdAt: LocalDateTime = LocalDateTime.now(),
    var updatedAt: LocalDateTime = LocalDateTime.now()
)

data class Email(
    val id: String = UUID.randomUUID().toString(),
    val recipient: String,
    val subject: String = "",
    val body: String = "",
    val templateId: String? = null,
    var status: String = "pending",
    val createdAt: LocalDateTime = LocalDateTime.now(),
    var updatedAt: LocalDateTime = LocalDateTime.now()
)

data class EmailTemplate(
    val id: String = UUID.randomUUID().toString(),
    val name: String,
    val type: String,
    val subject: String,
    val bodyHtml: String,
    val bodyText: String? = null,
    val variables: String? = null,
    var isActive: Boolean = true,
    var updatedAt: LocalDateTime = LocalDateTime.now()
)

class EmailRepository {
    private val emails = ConcurrentHashMap<String, Email>()

    fun save(email: Email): Email {
        emails[email.id] = email
        return email
    }

    fun findById(id: String): Email? {
        return emails[id]
    }

    fun findByRecipient(recipient: String): List<Email> {
        return emails.values.filter { it.recipient == recipient }.sortedByDescending { it.createdAt }
    }

    fun findByStatus(status: String): List<Email> {
        return emails.values.filter { it.status == status }
    }

    fun findByTemplateId(templateId: String): List<Email> {
        return emails.values.filter { it.templateId == templateId }
    }

    fun updateStatus(id: String, status: String): Email? {
        val email = emails[id]
        if (email != null) {
            email.status = status
            email.updatedAt = LocalDateTime.now()
            emails[id] = email
        }
        return email
    }

    fun getEmailStats(recipient: String): Map<String, Any> {
        val recipientEmails = emails.values.filter { it.recipient == recipient }
        return mapOf(
            "totalEmails" to recipientEmails.size,
            "sentEmails" to recipientEmails.filter { it.status == "sent" }.size,
            "failedEmails" to recipientEmails.filter { it.status == "failed" }.size,
            "pendingEmails" to recipientEmails.filter { it.status == "pending" }.size
        )
    }
}

class EmailTemplateRepository {
    private val templates = ConcurrentHashMap<String, EmailTemplate>()

    fun save(template: EmailTemplate): EmailTemplate {
        templates[template.id] = template
        return template
    }

    fun findById(id: String): EmailTemplate? {
        return templates[id]
    }

    fun findByName(name: String): EmailTemplate? {
        return templates.values.find { it.name == name }
    }

    fun findByType(type: String): List<EmailTemplate> {
        return templates.values.filter { it.type == type }
    }

    fun findActiveTemplates(): List<EmailTemplate> {
        return templates.values.filter { it.isActive }
    }

    fun updateStatus(id: String, isActive: Boolean): EmailTemplate? {
        val template = templates[id]
        if (template != null) {
            template.isActive = isActive
            template.updatedAt = LocalDateTime.now()
            templates[id] = template
        }
        return template
    }

    fun delete(id: String) {
        templates.remove(id)
    }
}

class EmailQueueRepository {
    private val queue = ConcurrentHashMap<String, EmailQueue>()

    fun save(emailQueue: EmailQueue): EmailQueue {
        queue[emailQueue.id] = emailQueue
        return emailQueue
    }

    fun findById(id: String): EmailQueue? {
        return queue[id]
    }

    fun findByStatus(status: String): List<EmailQueue> {
        return queue.values.filter { it.status == status }.sortedBy { it.priority }
    }

    fun findPendingEmails(limit: Int): List<EmailQueue> {
        return queue.values.filter { it.status == "pending" }.sortedBy { it.priority }.take(limit)
    }

    fun updateStatus(id: String, status: String): EmailQueue? {
        val emailQueue = queue[id]
        if (emailQueue != null) {
            emailQueue.status = status
            emailQueue.updatedAt = LocalDateTime.now()
            queue[id] = emailQueue
        }
        return emailQueue
    }

    fun delete(id: String) {
        queue.remove(id)
    }

    fun getQueueStats(): Map<String, Any> {
        return mapOf(
            "totalQueued" to queue.size,
            "pendingEmails" to queue.values.count { it.status == "pending" },
            "processingEmails" to queue.values.count { it.status == "processing" },
            "completedEmails" to queue.values.count { it.status == "completed" },
            "failedEmails" to queue.values.count { it.status == "failed" }
        )
    }
}