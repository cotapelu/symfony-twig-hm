package modules.identitymanagement

import identitymanagement.IdentitymanagementRepository
import identitymanagement.RegistrationResult
import identitymanagement.AuthenticationResult
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.security.MessageDigest
import java.time.LocalDateTime
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap

class IdentitymanagementService(private val repository: IdentitymanagementRepository) {
    private val users = ConcurrentHashMap<String, User>()
    private val emailToUserId = ConcurrentHashMap<String, String>()
    
    data class User(
        val id: String,
        val email: String,
        val passwordHash: String,
        val firstName: String,
        val lastName: String,
        val createdAt: LocalDateTime = LocalDateTime.now()
    )
    
    suspend fun getAllUsers(): List<Map<String, Any>> = withContext(Dispatchers.IO) {
        users.values.map { user ->
            mapOf(
                "id" to user.id,
                "email" to user.email,
                "firstName" to user.firstName,
                "lastName" to user.lastName,
                "createdAt" to user.createdAt.toString()
            )
        }
    }
    
    suspend fun getUserById(userId: String): Map<String, Any>? = withContext(Dispatchers.IO) {
        users[userId]?.let { user ->
            mapOf(
                "id" to user.id,
                "email" to user.email,
                "firstName" to user.firstName,
                "lastName" to user.lastName,
                "createdAt" to user.createdAt.toString()
            )
        }
    }
    
    suspend fun registerUser(
        email: String, 
        password: String,
        firstName: String,
        lastName: String
    ): RegistrationResult = withContext(Dispatchers.IO) {
        val normalizedEmail = email.lowercase().trim()
        
        if (emailToUserId.containsKey(normalizedEmail)) {
            return@withContext RegistrationResult.Error("Email already registered")
        }
        
        val userId = UUID.randomUUID().toString()
        val passwordHash = hashPassword(password)
        
        val user = User(
            id = userId,
            email = normalizedEmail,
            passwordHash = passwordHash,
            firstName = firstName,
            lastName = lastName
        )
        
        users[userId] = user
        emailToUserId[normalizedEmail] = userId
        
        RegistrationResult.Success(userId)
    }
    
    suspend fun authenticateUser(email: String, password: String): AuthenticationResult = withContext(Dispatchers.IO) {
        val normalizedEmail = email.lowercase().trim()
        val userId = emailToUserId[normalizedEmail]
        
        if (userId == null) {
            return@withContext AuthenticationResult.Error("Invalid email or password")
        }
        
        val user = users[userId]
        if (user == null) {
            return@withContext AuthenticationResult.Error("Invalid email or password")
        }
        
        val passwordHash = hashPassword(password)
        if (user.passwordHash != passwordHash) {
            return@withContext AuthenticationResult.Error("Invalid email or password")
        }
        
        AuthenticationResult.Success(
            userId = user.id,
            firstName = user.firstName,
            lastName = user.lastName
        )
    }
    
    private fun hashPassword(password: String): String {
        val digest = MessageDigest.getInstance("SHA-256")
        val hashBytes = digest.digest(password.toByteArray())
        return hashBytes.joinToString("") { "%02x".format(it) }
    }
}