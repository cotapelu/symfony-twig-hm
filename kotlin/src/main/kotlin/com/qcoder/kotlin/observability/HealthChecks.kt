package com.qcoder.kotlin.observability

import io.ktor.server.application.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import kotlinx.serialization.Serializable
import java.time.Instant

/**
 * Health check system with registration API
 */
class HealthChecks {
    private val checks = mutableMapOf<String, suspend () -> HealthResult>()

    data class HealthResult(
        val status: Status,
        val details: Map<String, Any> = emptyMap()
    )

    enum class Status { HEALTHY, DEGRADED, UNHEALTHY }

    /**
     * Register a new health check
     */
    fun register(name: String, check: suspend () -> HealthResult) {
        checks[name] = check
    }

    /**
     * Install health endpoints into Ktor application
     */
    fun install(application: Application) {
        application.routing {
            get("/health/live") {
                call.respond(mapOf("status" to "alive", "timestamp" to Instant.now().toString()))
            }
            get("/health/ready") {
                val results = runChecks()
                val overall = aggregateStatus(results)
                val response = HealthResponse(
                    status = overall.name,
                    timestamp = Instant.now().toString(),
                    checks = results.mapValues { it.value.status.name },
                    details = results.flatMap { (name, result) ->
                        listOf("$name:status" to result.status.name) + result.details.entries.map { (k, v) -> "$name:$k" to v.toString() }
                    }.toMap()
                )
                val statusCode = if (overall == Status.UNHEALTHY) io.ktor.http.HttpStatusCode.ServiceUnavailable else io.ktor.http.HttpStatusCode.OK
                call.respond(statusCode, response)
            }
        }
    }

    private suspend fun runChecks(): Map<String, HealthResult> {
        return checks.entries.associate { (name, check) ->
            try {
                name to check()
            } catch (e: Exception) {
                name to HealthResult(Status.UNHEALTHY, mapOf("error" to (e.message ?: "Unknown error")))
            }
        }
    }

    private fun aggregateStatus(results: Map<String, HealthResult>): Status {
        return when {
            results.values.any { it.status == Status.UNHEALTHY } -> Status.UNHEALTHY
            results.values.any { it.status == Status.DEGRADED } -> Status.DEGRADED
            else -> Status.HEALTHY
        }
    }

    @Serializable
    private data class HealthResponse(
        val status: String,
        val timestamp: String,
        val checks: Map<String, String>,
        val details: Map<String, String>
    )
}

/**
 * Extension to install health checks in Application
 */
fun Application.installHealthChecks(configure: HealthChecks.() -> Unit = {}) {
    val healthChecks = HealthChecks().apply(configure)
    healthChecks.install(this)
}
