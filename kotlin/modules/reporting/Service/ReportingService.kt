package reporting

import reporting.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class ReportingService(
    private val reportingRepository: ReportingRepository
) {
    suspend fun generateReport(
        name: String,
        type: String,
        description: String?,
        createdBy: String,
        query: String,
        parameters: Map<String, Any>,
        format: String = "PDF"
    ): Int = withContext(Dispatchers.IO) {
        val reportId = reportingRepository.createReport(name, type, description, createdBy, null)
        
        val reportData = executeQuery(query, parameters)
        
        val fileUrl = when (format.uppercase()) {
            "PDF" -> generatePDF(name, reportData)
            "EXCEL" -> generateExcel(name, reportData)
            "CSV" -> generateCSV(name, reportData)
            else -> generatePDF(name, reportData)
        }
        
        reportingRepository.updateReportStatus(reportId, "completed", fileUrl)
        reportId
    }

    suspend fun scheduleReport(
        templateId: Int,
        name: String,
        cronExpression: String,
        recipients: List<String>,
        format: String,
        createdBy: String
    ) = withContext(Dispatchers.IO) {
        val recipientsJson = recipients.joinToString(",")
        reportingRepository.scheduleReport(templateId, name, cronExpression, recipientsJson, format)
    }

    suspend fun createTemplate(
        name: String,
        type: String,
        description: String?,
        query: String,
        parameters: Map<String, String>,
        schedule: String?,
        format: String,
        createdBy: String
    ) = withContext(Dispatchers.IO) {
        val parametersJson = parameters.entries.joinToString(",") { "\"${it.key}\":\"${it.value}\"" }
        reportingRepository.createTemplate(
            name = name,
            type = type,
            description = description,
            query = query,
            parameters = "[$parametersJson]",
            schedule = schedule,
            format = format,
            createdBy = createdBy
        )
    }

    suspend fun getReports(limit: Int = 100, offset: Int = 0) = withContext(Dispatchers.IO) {
        reportingRepository.listReports(limit, offset)
    }

    suspend fun getReportById(reportId: Int) = withContext(Dispatchers.IO) {
        reportingRepository.getReportById(reportId)
    }

    suspend fun getTemplates(limit: Int = 100) = withContext(Dispatchers.IO) {
        reportingRepository.listTemplates(limit)
    }

    private fun executeQuery(query: String, parameters: Map<String, Any>): List<Map<String, Any>> {
        return try {
            val result = mutableListOf<Map<String, Any>>()
            for (i in 1..10) {
                result.add(mapOf(
                    "id" to i,
                    "name" to "Item $i",
                    "value" to (i * 100),
                    "date" to java.time.LocalDate.now().toString(),
                    "category" to if (i % 2 == 0) "A" else "B"
                ))
            }
            result
        } catch (e: Exception) {
            emptyList()
        }
    }

    private fun generatePDF(name: String, data: List<Map<String, Any>>): String {
        return "https://storage.example.com/reports/$name-${System.currentTimeMillis()}.pdf"
    }

    private fun generateExcel(name: String, data: List<Map<String, Any>>): String {
        return "https://storage.example.com/reports/$name-${System.currentTimeMillis()}.xlsx"
    }

    private fun generateCSV(name: String, data: List<Map<String, Any>>): String {
        return "https://storage.example.com/reports/$name-${System.currentTimeMillis()}.csv"
    }
}
