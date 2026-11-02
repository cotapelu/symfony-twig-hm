package graphql

import org.jetbrains.exposed.dao.id.IntIdTable
import org.jetbrains.exposed.sql.Column
import org.jetbrains.exposed.sql.javatime.datetime

object GQLSchemas : IntIdTable("graphql_schemas") {
    val name = varchar("name", 255)
    val version = varchar("version", 50)
    val schema = text("schema")
    val isActive = bool("is_active")
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object GQLQueries : IntIdTable("graphql_queries") {
    val schemaId = reference("schema_id", GQLSchemas)
    val name = varchar("name", 255)
    val type = varchar("type", 50)
    val resolver = text("resolver")
    val args = text("args").nullable()
    val description = text("description").nullable()
    val isActive = bool("is_active")
    val createdAt = datetime("created_at")
}

object GQLMutations : IntIdTable("graphql_mutations") {
    val schemaId = reference("schema_id", GQLSchemas)
    val name = varchar("name", 255)
    val type = varchar("type", 50)
    val resolver = text("resolver")
    val args = text("args").nullable()
    val description = text("description").nullable()
    val isActive = bool("is_active")
    val createdAt = datetime("created_at")
}

object GQLSubscriptions : IntIdTable("graphql_subscriptions") {
    val schemaId = reference("schema_id", GQLSchemas)
    val name = varchar("name", 255)
    val type = varchar("type", 50)
    val resolver = text("resolver")
    val args = text("args").nullable()
    val description = text("description").nullable()
    val isActive = bool("is_active")
    val createdAt = datetime("created_at")
}
