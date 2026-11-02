package apigateway

import apigateway.ApigatewayRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class ApigatewayService(private val repository: ApigatewayRepository) {
    suspend fun process(data: Map<String, Any>) = withContext(Dispatchers.IO) {
        repository.findAll()
    }

    suspend fun addRoute(routeConfig: Map<String, Any>): String {
        return "route_added"
    }

    suspend fun removeRoute(routeId: String): Boolean {
        repository.delete(routeId)
        return true
    }
}
