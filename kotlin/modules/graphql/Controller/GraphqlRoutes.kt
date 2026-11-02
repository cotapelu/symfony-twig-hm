package graphql

import graphql.GraphqlHandler
import io.ktor.server.application.*
import io.ktor.server.routing.*
import io.ktor.server.request.*
import io.ktor.http.*

private fun authenticate(call: ApplicationCall): Boolean {
    val authHeader = call.request.headers["Authorization"]
    return authHeader != null && authHeader.startsWith("Bearer ") && authHeader.length > 7
}

private fun requireAuth(call: ApplicationCall) {
    if (!authenticate(call)) {
        call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
        throw IllegalArgumentException("Unauthorized")
    }
}

fun Application.configureGraphqlRoutes(handler: GraphqlHandler) {
    routing {
        route("/api/graphql") {
            post {
                requireAuth(call)
                handler.executeQuery()
            }
            get("/schema") {
                handler.getSchema()
            }
        }
    }
}
