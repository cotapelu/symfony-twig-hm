package com.qcoder.kotlin.observability

import io.ktor.server.application.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import java.time.Instant

/**
 * Observability endpoints: health checks and metrics
 * Call this from Application.module() after routing setup
 */
fun Application.installObservabilityEndpoints() {
    routing {
        get("/health/live") {
            call.respond(mapOf("status" to "alive", "timestamp" to Instant.now().toString()))
        }
        get("/health/ready") {
            call.respond(mapOf("status" to "ready", "timestamp" to Instant.now().toString()))
        }
        get("/metrics") {
            call.respond(Metrics.snapshot())
        }
    }
}
