package graphql

import graphql.GraphqlRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class GraphqlService(private val repository: GraphqlRepository) {
    suspend fun process(data: Map<String, Any>) = withContext(Dispatchers.IO) {
        repository.findAll()
    }

    suspend fun executeQuery(query: String, variables: Map<String, Any>): Map<String, Any> {
        return mapOf("data" to emptyMap<String, Any>())
    }
}
