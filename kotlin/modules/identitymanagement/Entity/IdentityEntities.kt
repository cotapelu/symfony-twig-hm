package identitymanagement

import org.jetbrains.exposed.dao.id.IntIdTable
import org.jetbrains.exposed.sql.Column
import org.jetbrains.exposed.sql.javatime.datetime

object Users : IntIdTable("users") {
    val email = varchar("email", 255)
    val passwordHash = varchar("password_hash", 255)
    val firstName = varchar("first_name", 100)
    val lastName = varchar("last_name", 100)
    val avatarUrl = varchar("avatar_url", 500).nullable()
    val isActive = bool("is_active")
    val emailVerified = bool("email_verified")
    val lastLoginAt = datetime("last_login_at").nullable()
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object OAuthProviders : IntIdTable("oauth_providers") {
    val userId = reference("user_id", Users)
    val provider = varchar("provider", 50)
    val providerUserId = varchar("provider_user_id", 255)
    val accessToken = text("access_token")
    val refreshToken = text("refresh_token").nullable()
    val expiresAt = datetime("expires_at").nullable()
    val scopes = text("scopes").nullable()
    val createdAt = datetime("created_at")
    val updatedAt = datetime("updated_at")
}

object Roles : IntIdTable("roles") {
    val name = varchar("name", 100)
    val description = text("description").nullable()
    val permissions = text("permissions").nullable()
    val createdAt = datetime("created_at")
}

object UserRoles : IntIdTable("user_roles") {
    val userId = reference("user_id", Users)
    val roleId = reference("role_id", Roles)
    val assignedAt = datetime("assigned_at")
}

object RefreshTokens : IntIdTable("refresh_tokens") {
    val userId = reference("user_id", Users)
    val token = varchar("token", 500)
    val expiresAt = datetime("expires_at")
    val isRevoked = bool("is_revoked").default(false)
    val createdAt = datetime("created_at")
}
