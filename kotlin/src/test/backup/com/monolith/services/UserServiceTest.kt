package com.monolith.services
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Test
class UserServiceTest {
    private val service = UserService()
    @Test
    fun `test perform`() {
        val result = service.perform("test")
        assertEquals("UserService: test", result)
    }
}
