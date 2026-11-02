package identitymanagement

import com.auth0.jwt.JWT
import com.auth0.jwt.algorithms.Algorithm
import io.ktor.server.application.*
import io.ktor.server.auth.authentication
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import io.ktor.server.auth.jwt.JWTPrincipal
import modules.identitymanagement.IdentitymanagementService
import java.util.Date

class IdentitymanagementHandler(
    private val service: IdentitymanagementService,
    private val jwtSecret: String
) {
    private val algorithm = Algorithm.HMAC256(jwtSecret)
    
    suspend fun getUsers(call: ApplicationCall) {
        try {
            val users = service.getAllUsers()
            call.respond(mapOf("users" to users))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.InternalServerError, mapOf("error" to (e.message ?: "Failed to get users")))
        }
    }
    
    suspend fun register(call: ApplicationCall) {
        try {
            val request = call.receive<RegisterRequest>()
            
            if (request.email.isBlank() || request.password.isBlank()) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Email and password are required"))
                return
            }
            
            if (request.password.length < 6) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Password must be at least 6 characters"))
                return
            }
            
            val result = service.registerUser(
                email = request.email,
                password = request.password,
                firstName = request.firstName,
                lastName = request.lastName
            )
            
            when (result) {
                is RegistrationResult.Success -> {
                    val token = generateToken(result.userId, request.email)
                    call.respond(HttpStatusCode.Created, mapOf(
                        "message" to "User registered successfully",
                        "user" to mapOf(
                            "id" to result.userId,
                            "email" to request.email,
                            "firstName" to request.firstName,
                            "lastName" to request.lastName
                        ),
                        "token" to token
                    ))
                }
                is RegistrationResult.Error -> {
                    call.respond(HttpStatusCode.BadRequest, mapOf("error" to result.message))
                }
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Registration failed")))
        }
    }
    
    suspend fun login(call: ApplicationCall) {
        try {
            val request = call.receive<LoginRequest>()
            
            if (request.email.isBlank() || request.password.isBlank()) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Email and password are required"))
                return
            }
            
            val result = service.authenticateUser(request.email, request.password)
            
            when (result) {
                is AuthenticationResult.Success -> {
                    val token = generateToken(result.userId, request.email)
                    call.respond(mapOf(
                        "message" to "Login successful",
                        "user" to mapOf(
                            "id" to result.userId,
                            "email" to request.email,
                            "firstName" to result.firstName,
                            "lastName" to result.lastName
                        ),
                        "token" to token
                    ))
                }
                is AuthenticationResult.Error -> {
                    call.respond(HttpStatusCode.Unauthorized, mapOf("error" to result.message))
                }
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Login failed")))
        }
    }
    
    suspend fun getCurrentUser(call: ApplicationCall) {
        try {
            val principal = call.authentication.principal<io.ktor.server.auth.jwt.JWTPrincipal>()
            val userId = principal?.subject ?: throw IllegalArgumentException("User ID required")
            val user = service.getUserById(userId)
            
            if (user != null) {
                call.respond(mapOf("user" to user))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "User not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get user")))
        }
    }
    
    private fun generateToken(userId: String, email: String): String {
        return JWT.create()
            .withIssuer("kotlin-backend")
            .withSubject(userId)
            .withClaim("email", email)
            .withIssuedAt(Date())
            .withExpiresAt(Date(System.currentTimeMillis() + 24 * 60 * 60 * 1000))
            .sign(algorithm)
    }
}

data class RegisterRequest(
    val email: String,
    val password: String,
    val firstName: String = "",
    val lastName: String = ""
)

data class LoginRequest(
    val email: String,
    val password: String
)

sealed class RegistrationResult {
    data class Success(val userId: String) : RegistrationResult()
    data class Error(val message: String) : RegistrationResult()
}

sealed class AuthenticationResult {
    data class Success(
        val userId: String,
        val firstName: String,
        val lastName: String
    ) : AuthenticationResult()
    data class Error(val message: String) : AuthenticationResult()
}