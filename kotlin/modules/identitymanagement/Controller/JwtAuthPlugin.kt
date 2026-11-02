package identitymanagement

import com.auth0.jwt.JWT
import com.auth0.jwt.algorithms.Algorithm
import com.auth0.jwt.interfaces.DecodedJWT
import io.ktor.server.application.*
import io.ktor.server.auth.*
import io.ktor.server.auth.jwt.*
import io.ktor.server.response.*
import io.ktor.http.*

/**
 * JWT Authentication configuration for Kotlin Ktor
 */
fun Application.configureJwtAuth(jwtSecret: String) {
    install(Authentication) {
        jwt("auth-jwt") {
            realm = "Community Platform"
            verifier(
                JWT.require(Algorithm.HMAC256(jwtSecret))
                    .withIssuer("kotlin-backend")
                    .build()
            )
            validate { credential ->
                if (credential.payload.subject != null) {
                    JWTPrincipal(credential.payload)
                } else {
                    null
                }
            }
        }
    }
}

/**
 * Extract user ID from JWT token
 */
suspend fun ApplicationCall.getUserId(): String? {
    val principal = authentication.principal<JWTPrincipal>()
    return principal?.subject
}

/**
 * Require authentication or return 401
 */
suspend fun ApplicationCall.requireAuth(): Boolean {
    val principal = authentication.principal<JWTPrincipal>()
    if (principal == null) {
        respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
        return false
    }
    return true
}

/**
 * JWT utility functions
 */
object JwtUtils {
    fun createToken(userId: String, email: String, jwtSecret: String): String {
        return JWT.create()
            .withIssuer("kotlin-backend")
            .withSubject(userId)
            .withClaim("email", email)
            .withIssuedAt(java.util.Date())
            .withExpiresAt(java.util.Date(System.currentTimeMillis() + 24 * 60 * 60 * 1000))
            .sign(Algorithm.HMAC256(jwtSecret))
    }
    
    fun verifyToken(token: String, jwtSecret: String): Result<DecodedJWT> {
        return try {
            val algorithm = Algorithm.HMAC256(jwtSecret)
            val verifier = JWT.require(algorithm)
                .withIssuer("kotlin-backend")
                .build()
            Result.success(verifier.verify(token))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}