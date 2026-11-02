package featureflags

import org.jetbrains.exposed.dao.id.IntIdTable
import org.jetbrains.exposed.sql.Column
import org.jetbrains.exposed.sql.javatime.datetime

object FeatureFlags : IntIdTable("feature_flags") {
    val key = varchar("key", 255)
    val name = varchar("name", 255)
    val description = text("description").nullable()
    val isEnabled = bool("is_enabled")
    val type = varchar("type", 50).default("boolean")
    val value = text("value").nullable()
    val percentage = integer("percentage").nullable()
    val segments = text("segments").nullable()
    val createdBy = varchar("created_by", 255)
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object FeatureFlagEvaluations : IntIdTable("feature_flag_evaluations") {
    val flagId = reference("flag_id", FeatureFlags)
    val userId = varchar("user_id", 255).nullable()
    val sessionId = varchar("session_id", 255).nullable()
    val ipAddress = varchar("ip_address", 50).nullable()
    val userAgent = text("user_agent").nullable()
    val result = bool("result")
    val value = text("value").nullable()
    val evaluatedAt = datetime("evaluated_at")
}
