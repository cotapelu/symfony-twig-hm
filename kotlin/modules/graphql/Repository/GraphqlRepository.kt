package graphql

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class GraphqlRepository {
    suspend fun findAll() = withContext(Dispatchers.IO) { emptyList<Any>() }
    suspend fun findById(id: String) = withContext(Dispatchers.IO) { null }
}
