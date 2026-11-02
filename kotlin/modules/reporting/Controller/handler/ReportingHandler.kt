package reporting

import reporting.ReportingService
import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*

class ReportingHandler(
    private val reportingService: ReportingService
) {
    suspend fun generateReport(call: ApplicationCall) {
        val params = call.receiveParameters()
        val name = params["name"] ?: return call.respondText("Missing name")
        val type = params["type"] ?: return call.respondText("Missing type")
        val descriptionVal = params["description"]
        val description: String? = if (descriptionVal != null) descriptionVal.toString() else null
        val query = params["query"] ?: return call.respondText("Missing query")
        val format = params["format"] ?: "PDF"
        val createdByHeader = call.request.headers.getAll("User-Id")
        val createdBy: String = createdByHeader?.firstOrNull() ?: "system"
        
        val reportId = reportingService.generateReport(
            name = name,
            type = type,
            description = description,
            createdBy = createdBy,
            query = query,
            parameters = emptyMap(),
            format = format
        )
        
        call.respond(mapOf("reportId" to reportId, "status" to "generating"))
    }

    suspend fun getReports(call: ApplicationCall) {
        val limit = call.request.queryParameters["limit"]?.toIntOrNull() ?: 100
        val offset = call.request.queryParameters["offset"]?.toIntOrNull() ?: 0
        val reports = reportingService.getReports(limit, offset)
        call.respond(reports)
    }

    suspend fun getReport(call: ApplicationCall) {
        val reportId = call.parameters["id"]?.toIntOrNull() ?: return call.respondText("Missing or invalid report ID")
        val report = reportingService.getReportById(reportId)
        if (report != null) {
            call.respond(report)
        } else {
            call.respond(mapOf("error" to "Report not found"))
        }
    }

    suspend fun createTemplate(call: ApplicationCall) {
        val params = call.receiveParameters()
        val name = params["name"] ?: return call.respondText("Missing name")
        val type = params["type"] ?: return call.respondText("Missing type")
        val descriptionVal = params["description"]
        val description: String? = if (descriptionVal != null) descriptionVal.toString() else null
        val query = params["query"] ?: return call.respondText("Missing query")
        val format = params["format"] ?: "PDF"
        val scheduleVal = params["schedule"]
        val schedule: String? = if (scheduleVal != null) scheduleVal.toString() else null
        val createdByHeader = call.request.headers.getAll("User-Id")
        val createdBy: String = createdByHeader?.firstOrNull() ?: "system"
        
        reportingService.createTemplate(
            name = name,
            type = type,
            description = description,
            query = query,
            parameters = emptyMap(),
            schedule = schedule,
            format = format,
            createdBy = createdBy
        )
        
        call.respond(mapOf("status" to "created"))
    }

    suspend fun getTemplates(call: ApplicationCall) {
        val limit = call.request.queryParameters["limit"]?.toIntOrNull() ?: 100
        val templates = reportingService.getTemplates(limit)
        call.respond(templates)
    }

    suspend fun scheduleReport(call: ApplicationCall) {
        val params = call.receiveParameters()
        val templateId = params["templateId"]?.toIntOrNull() ?: return call.respondText("Missing or invalid templateId")
        val name = params["name"] ?: return call.respondText("Missing name")
        val cronExpression = params["cron"] ?: return call.respondText("Missing cron expression")
        val recipients = listOf("admin@example.com")
        val format = params["format"] ?: "PDF"
        val createdByHeader = call.request.headers.getAll("User-Id")
        val createdBy: String = createdByHeader?.firstOrNull() ?: "system"
        
        reportingService.scheduleReport(
            templateId = templateId,
            name = name,
            cronExpression = cronExpression,
            recipients = recipients,
            format = format,
            createdBy = createdBy
        )
        
        call.respond(mapOf("status" to "scheduled"))
    }
}
