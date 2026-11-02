package reporting

import reporting.ReportingHandler
import io.ktor.server.application.*
import io.ktor.server.routing.*

fun Application.configureReportingRoutes(handler: ReportingHandler) {
    routing {
        route("/api/reporting") {
            post("/reports") {
                handler.generateReport(call)
            }
            
            get("/reports") {
                handler.getReports(call)
            }
            
            get("/reports/{id}") {
                handler.getReport(call)
            }
            
            post("/templates") {
                handler.createTemplate(call)
            }
            
            get("/templates") {
                handler.getTemplates(call)
            }
            
            post("/schedules") {
                handler.scheduleReport(call)
            }
        }
    }
}
