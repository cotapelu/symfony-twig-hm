package apigateway

import apigateway.ApigatewayHandler
import io.ktor.server.application.*
import io.ktor.server.routing.*

fun Application.configureApigatewayRoutes(handler: ApigatewayHandler) {
    routing {
        route("/api/apigateway") {
            get("/routes") {
                handler.getRoutes()
            }
            post("/routes") {
                handler.createRoute()
            }
            get("/health") {
                handler.healthCheck()
            }
        }
    }
}
