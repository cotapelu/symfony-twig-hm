package com.example.metric

import com.qcoder.kotlin.observability.*
import io.ktor.server.application.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import io.ktor.http.*

fun Application.module() {
    // Observability: correlation ID + metrics interceptor
    intercept(ApplicationCallPipeline.Call) {
        val correlationId = CorrelationId.getOrGenerate(call)
        val start = System.currentTimeMillis()
        try { proceed() } finally {
            val duration = System.currentTimeMillis() - start
            val status = call.response.status()
            Metrics.record(status?.value ?: 200, duration)
        }
    }

    // Observability endpoints
    installObservabilityEndpoints()

    routing {
        // Metric API endpoints (existing)
        get("/metrics/counter/{name}") {
            val name = call.parameters["name"] ?: return@get call.respond(HttpStatusCode.BadRequest, "Missing metric name")
            call.respond(mapOf("metric" to name, "value" to 0))
        }
        get("/metrics/gauges") { call.respond(mapOf("gauges" to emptyList<String>())) }
        get("/metrics/histograms") { call.respond(mapOf("histograms" to emptyList<String>())) }
        get("/admin/metrics/health") { call.respond(mapOf("status" to "healthy", "service" to "metric")) }
    }
}
