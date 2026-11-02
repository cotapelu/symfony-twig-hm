package automation

import automation.WorkflowengineService
import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*

class WorkflowengineHandler(private val service: WorkflowengineService) {
    suspend fun getHealth(call: ApplicationCall) {
        call.respondText("Workflow engine is running")
    }

    suspend fun getMetrics(call: ApplicationCall) {
        call.respondText("{\"status\": \"ok\"}")
    }

    suspend fun recordMetric(call: ApplicationCall) {
        call.respondText("Metric recorded")
    }

    suspend fun getHealthStatus(call: ApplicationCall) {
        call.respondText("{\"status\": \"healthy\"}")
    }

    suspend fun getWorkflows(call: ApplicationCall) {
        val workflows = service.getWorkflows()
        call.respond(workflows)
    }

    suspend fun createWorkflow(call: ApplicationCall) {
        val request = call.receive<Map<String, *>>()
        val name = request["name"] as String
        val definition = request["definition"] as? String ?: "{}"
        val result = service.createWorkflow(name, definition)
        call.respond(mapOf("status" to "created", "result" to result))
    }

    suspend fun executeWorkflow(call: ApplicationCall) {
        val request = call.receive<Map<String, *>>()
        val workflowId = request["workflowId"] as String
        val input = request["input"] as? String ?: "{}"
        val result = service.executeWorkflow(workflowId, input)
        call.respond(mapOf("result" to result))
    }
}
