package apigateway

import org.jetbrains.exposed.dao.id.IntIdTable
import org.jetbrains.exposed.sql.Column
import org.jetbrains.exposed.sql.javatime.datetime

object Routes : IntIdTable("gateway_routes") {
    val path = varchar("path", 500)
    val method = varchar("method", 10)
    val serviceUrl = varchar("service_url", 500)
    val timeout = integer("timeout").default(30000)
    val retries = integer("retries").default(3)
    val authRequired = bool("auth_required").default(true)
    val rateLimit = integer("rate_limit").nullable()
    val rateWindow = integer("rate_window").nullable()
    val isActive = bool("is_active").default(true)
    val priority = integer("priority").default(0)
    val version = varchar("version", 50).nullable()
    val description = text("description").nullable()
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object RateLimitRules : IntIdTable("rate_limit_rules") {
    val routeId = reference("route_id", Routes)
    val key = varchar("key", 255)
    val limit = integer("limit")
    val window = integer("window")
    val blockDuration = integer("block_duration").nullable()
    val isActive = bool("is_active")
    val createdAt = datetime("created_at")
}

object GatewayLogs : IntIdTable("gateway_logs") {
    val routeId = reference("route_id", Routes)
    val method = varchar("method", 10)
    val path = varchar("path", 500)
    val userId = varchar("user_id", 255).nullable()
    val statusCode = integer("status_code")
    val responseTime = integer("response_time")
    val requestHeaders = text("request_headers").nullable()
    val requestHeadersSize = integer("request_headers_size").default(0)
    val responseHeaders = text("response_headers").nullable()
    val responseSize = integer("response_size").default(0)
    val error = text("error").nullable()
    val createdAt = datetime("created_at")
}
