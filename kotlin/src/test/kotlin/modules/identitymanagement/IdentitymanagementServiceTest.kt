package modules.identitymanagement

import identitymanagement.AuthenticationResult
import identitymanagement.IdentitymanagementRepository
import identitymanagement.RegistrationResult
import kotlinx.coroutines.test.runTest
import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test

class IdentitymanagementServiceTest {

    private val service = IdentitymanagementService(IdentitymanagementRepository())

    @Test
    fun `registerUser should create new user`() = runTest {
        val result = service.registerUser("test@example.com", "password123", "John", "Doe")
        assertTrue(result is RegistrationResult.Success)
        val success = result as RegistrationResult.Success
        assertNotNull(success.userId)
    }

    @Test
    fun `registerUser should fail for duplicate email`() = runTest {
        service.registerUser("dup@example.com", "pass", "A", "B")
        val result = service.registerUser("dup@example.com", "pass2", "C", "D")
        assertTrue(result is RegistrationResult.Error)
        assertTrue((result as RegistrationResult.Error).message.contains("already registered"))
    }

    @Test
    fun `authenticateUser should succeed with correct credentials`() = runTest {
        service.registerUser("auth@example.com", "secret", "First", "Last")
        val result = service.authenticateUser("auth@example.com", "secret")
        assertTrue(result is AuthenticationResult.Success)
        val success = result as AuthenticationResult.Success
        assertEquals("First", success.firstName)
    }

    @Test
    fun `authenticateUser should fail for wrong password`() = runTest {
        service.registerUser("auth2@example.com", "correct", "F", "L")
        val result = service.authenticateUser("auth2@example.com", "wrong")
        assertTrue(result is AuthenticationResult.Error)
    }

    @Test
    fun `getUserById should return user data`() = runTest {
        service.registerUser("get@example.com", "pwd", "Get", "User")
        val allUsers = service.getAllUsers()
        assertTrue(allUsers.isNotEmpty())
        val userId = allUsers[0]["id"] as String
        val user = service.getUserById(userId)
        assertNotNull(user)
        assertEquals("Get", user!!["firstName"])
    }
}
