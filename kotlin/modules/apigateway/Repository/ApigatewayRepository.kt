package apigateway

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class ApigatewayRepository {
    suspend fun findAll() = withContext(Dispatchers.IO) { emptyList<Any>() }
    suspend fun findById(id: String) = withContext(Dispatchers.IO) { null }
    suspend fun save(route: Any) = withContext(Dispatchers.IO) { route }
    suspend fun delete(id: String) = withContext(Dispatchers.IO) { }
}
