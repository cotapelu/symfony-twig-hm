package apigateway

import apigateway.ApigatewayService
import io.ktor.server.application.*
import io.ktor.server.response.*

class ApigatewayHandler(private val service: ApigatewayService) {
    suspend fun getRoutes() {}
    suspend fun createRoute() {}
    suspend fun healthCheck() {}
}
