package automation

import automation.WorkflowengineHandler
import io.ktor.server.application.*
import io.ktor.server.routing.*

fun Application.configureWorkflowengineRoutes(handler: WorkflowengineHandler) {
    routing {
        route("/api/workflowengine") {
            get("/workflows") {
                handler.getWorkflows(call)
            }
            post("/workflows") {
                handler.createWorkflow(call)
            }
            post("/execute") {
                handler.executeWorkflow(call)
            }
        }
    }
}
