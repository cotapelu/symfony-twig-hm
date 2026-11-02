package monitoring

import monitoring.MonitoringHandler
import io.ktor.server.application.*
import io.ktor.server.routing.*
import io.ktor.server.plugins.contentnegotiation.*
import io.ktor.serialization.kotlinx.json.*

fun Application.configureMonitoringRoutes(handler: MonitoringHandler) {
    routing {
        route("/api/monitoring") {
            get("/health") {
                handler.getHealth(call)
            }
            
            get("/metrics") {
                handler.getMetrics(call)
            }
            
            get("/metrics/{name}") {
                handler.getMetrics(call)
            }
            
            post("/metrics") {
                handler.recordMetric(call)
            }
            
            get("/health/status") {
                handler.getHealthStatus(call)
            }
            
            get("/alerts") {
                handler.getAlerts(call)
            }
            
            post("/alerts/{id}/resolve") {
                handler.resolveAlert(call)
            }
        }
    }
}
