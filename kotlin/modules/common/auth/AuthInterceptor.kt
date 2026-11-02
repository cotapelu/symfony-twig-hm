// Auth Interceptor for Kotlin modules
// Usage: Add to each endpoint or use in Route.kt

package auth

import io.ktor.server.application.*
import io.ktor.server.auth.*
import io.ktor.http.*
import io.ktor.server.response.*

// JWT authentication configuration
// In production, use real JWT validation with secret key
fun Application.configureAuth() {
	install(Authentication) {
		bearer("jwt") {
			validate { credentials ->
				val token = credentials.token
				// In production: validate JWT token properly
				// For demo: extract user_id from token format "user-{id}"
				if (token.startsWith("user-")) {
					val userId = token.removePrefix("user-")
					val roles = listOf("user") // Default role
					UserPrincipal(userId, roles)
				} else {
					null // Invalid token
				}
			}
			challenge { _, _ ->
				call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
			}
		}
	}
}

data class UserPrincipal(
	val userId: String,
	val roles: List<String>
) : Principal

// Role-based authorization
class RoleAuthorization(val requiredRole: String) {
	suspend fun invoke(call: ApplicationCall, principal: UserPrincipal?) {
		if (principal == null) {
			call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
			return
		}
		if (!principal.roles.contains(requiredRole)) {
			call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Insufficient permissions"))
			return
		}
	}
}

// Authorization helpers
fun requireRole(role: String): PrincipalCallInterceptor = { call, principal ->
	val userPrincipal = principal as? UserPrincipal
	if (userPrincipal == null || !userPrincipal.roles.contains(role)) {
		call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Role $role required"))
		return@PrincipalCallInterceptor
	}
}

fun authorizeResource(userId: String, resourceOwnerId: String): Boolean {
	return userId == resourceOwnerId
}

// Extension to get current user from context
fun ApplicationCall.currentUser(): UserPrincipal? {
	return authentication.principal<UserPrincipal>()
}
