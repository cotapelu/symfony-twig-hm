package graphql

import graphql.GraphqlService
import io.ktor.server.application.*

class GraphqlHandler(private val service: GraphqlService) {
    suspend fun executeQuery() {}
    suspend fun getSchema() {}
}
