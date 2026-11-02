package com.qcoder.kotlin.observability

import io.ktor.http.HttpHeaders
import io.ktor.server.application.ApplicationCall

/**
 * Correlation ID generator and manager
 * Per GOAL.md Section 4.3: Correlation IDs (X-Request-ID)
 */
object CorrelationId {
    private val HEADER_NAME = HttpHeaders.XCorrelationId

    fun getOrGenerate(call: ApplicationCall): String {
        return call.request.headers[HEADER_NAME] ?: generate().also {
            call.response.headers.append(HEADER_NAME, it)
        }
    }

    fun generate(): String = java.util.UUID.randomUUID().toString()
}
