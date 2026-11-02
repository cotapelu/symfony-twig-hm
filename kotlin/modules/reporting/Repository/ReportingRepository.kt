package reporting

import reporting.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.jetbrains.exposed.sql.*
import org.jetbrains.exposed.sql.transactions.transaction

data class Report(
    val id: Int,
    val name: String,
    val type: String,
    val description: String?,
    val createdBy: String,
    val status: String,
    val fileUrl: String?,
    val createdAt: java.time.LocalDateTime,
    val completedAt: java.time.LocalDateTime?
)

data class ReportTemplate(
    val id: Int,
    val name: String,
    val description: String?,
    val type: String,
    val query: String,
    val parameters: String?,
    val schedule: String?,
    val format: String,
    val isActive: Boolean
)

class ReportingRepository {
    suspend fun createReport(
        name: String,
        type: String,
        description: String?,
        createdBy: String,
        scheduledAt: java.time.LocalDateTime?
    ): Int = withContext(Dispatchers.IO) {
        transaction {
            val reportId = Reports.insert {
                it[Reports.name] = name
                it[Reports.type] = type
                it[Reports.description] = description
                it[Reports.createdBy] = createdBy
                it[Reports.status] = "pending"
                it[Reports.scheduledAt] = scheduledAt
                it[Reports.createdAt] = java.time.LocalDateTime.now()
                it[Reports.updatedAt] = java.time.LocalDateTime.now()
            } get Reports.id
            reportId.value
        }
    }

    suspend fun getReportById(reportId: Int) = withContext(Dispatchers.IO) {
        transaction {
            Reports.select { Reports.id eq reportId }
                .singleOrNull()?.let { row ->
                    Report(
                        id = row[Reports.id].value,
                        name = row[Reports.name],
                        type = row[Reports.type],
                        description = row[Reports.description],
                        createdBy = row[Reports.createdBy],
                        status = row[Reports.status],
                        fileUrl = row[Reports.fileUrl],
                        createdAt = row[Reports.createdAt],
                        completedAt = row[Reports.completedAt]
                    )
                }
        }
    }

    suspend fun listReports(limit: Int = 100, offset: Int = 0) = withContext(Dispatchers.IO) {
        transaction {
            Reports.selectAll()
                .orderBy(Reports.createdAt to SortOrder.DESC)
                .limit(limit, offset.toLong())
                .map { row ->
                    Report(
                        id = row[Reports.id].value,
                        name = row[Reports.name],
                        type = row[Reports.type],
                        description = row[Reports.description],
                        createdBy = row[Reports.createdBy],
                        status = row[Reports.status],
                        fileUrl = row[Reports.fileUrl],
                        createdAt = row[Reports.createdAt],
                        completedAt = row[Reports.completedAt]
                    )
                }
        }
    }

    suspend fun updateReportStatus(
        reportId: Int,
        status: String,
        fileUrl: String? = null,
        fileSize: Long? = null
    ) = withContext(Dispatchers.IO) {
        transaction {
            Reports.update({ Reports.id eq reportId }) {
                it[Reports.status] = status
                if (fileUrl != null) it[Reports.fileUrl] = fileUrl
                if (fileSize != null) it[Reports.fileSize] = fileSize
                if (status == "completed") {
                    it[Reports.completedAt] = java.time.LocalDateTime.now()
                }
                it[Reports.updatedAt] = java.time.LocalDateTime.now()
            }
        }
    }

    suspend fun createTemplate(
        name: String,
        type: String,
        description: String?,
        query: String,
        parameters: String?,
        schedule: String?,
        format: String,
        createdBy: String
    ) = withContext(Dispatchers.IO) {
        transaction {
            ReportTemplates.insert {
                it[ReportTemplates.name] = name
                it[ReportTemplates.type] = type
                it[ReportTemplates.description] = description
                it[ReportTemplates.query] = query
                it[ReportTemplates.parameters] = parameters
                it[ReportTemplates.schedule] = schedule
                it[ReportTemplates.format] = format
                it[ReportTemplates.isActive] = true
                it[ReportTemplates.createdBy] = createdBy
                it[ReportTemplates.createdAt] = java.time.LocalDateTime.now()
                it[ReportTemplates.updatedAt] = java.time.LocalDateTime.now()
            }
        }
    }

    suspend fun listTemplates(limit: Int = 100) = withContext(Dispatchers.IO) {
        transaction {
            ReportTemplates.select { ReportTemplates.isActive eq true }
                .orderBy(ReportTemplates.createdAt to SortOrder.DESC)
                .limit(limit)
                .map { row ->
                    ReportTemplate(
                        id = row[ReportTemplates.id].value,
                        name = row[ReportTemplates.name],
                        description = row[ReportTemplates.description],
                        type = row[ReportTemplates.type],
                        query = row[ReportTemplates.query],
                        parameters = row[ReportTemplates.parameters],
                        schedule = row[ReportTemplates.schedule],
                        format = row[ReportTemplates.format],
                        isActive = row[ReportTemplates.isActive]
                    )
                }
        }
    }

    suspend fun scheduleReport(
        templateId: Int,
        name: String,
        cronExpression: String,
        recipients: String,
        format: String
    ) = withContext(Dispatchers.IO) {
        transaction {
            ReportSchedules.insert {
                it[ReportSchedules.templateId] = templateId
                it[ReportSchedules.name] = name
                it[ReportSchedules.cronExpression] = cronExpression
                it[ReportSchedules.recipients] = recipients
                it[ReportSchedules.format] = format
                it[ReportSchedules.isActive] = true
                it[ReportSchedules.createdAt] = java.time.LocalDateTime.now()
                it[ReportSchedules.updatedAt] = java.time.LocalDateTime.now()
            }
        }
    }
}
