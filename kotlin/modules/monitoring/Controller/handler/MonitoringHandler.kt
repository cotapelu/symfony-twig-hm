package monitoring

import monitoring.*
import io.ktor.server.application.*
import io.ktor.server.response.*

class MonitoringHandler(
    private val monitoringService: MonitoringService
) {
    suspend fun getHealth(call: ApplicationCall) {
        call.respondText("OK")
    }

    suspend fun getMetrics(call: ApplicationCall) {
        val name = call.parameters["name"] ?: return call.respondText("Missing name parameter")
        val metrics = monitoringService.getMetricsByName(name)
        call.respond(metrics)
    }

    suspend fun getHealthStatus(call: ApplicationCall) {
        val status = monitoringService.getSystemHealth()
        call.respond(status)
    }

    suspend fun recordMetric(call: ApplicationCall) {
        val name = call.request.queryParameters["name"] ?: return call.respondText("Missing name parameter")
        val value = call.request.queryParameters["value"]?.toDoubleOrNull() ?: return call.respondText("Missing or invalid value parameter")
        val unit = call.request.queryParameters["unit"] ?: "count"
        
        monitoringService.recordMetric(name, value, unit)
        call.respond(mapOf("status" to "recorded"))
    }

    suspend fun getAlerts(call: ApplicationCall) {
        val alerts = monitoringService.getActiveAlerts()
        call.respond(alerts)
    }

    suspend fun resolveAlert(call: ApplicationCall) {
        val alertId = call.parameters["id"]?.toIntOrNull() ?: return call.respondText("Missing or invalid alert ID")
        monitoringService.resolveAlert(alertId)
        call.respond(mapOf("status" to "resolved"))
    }
}
