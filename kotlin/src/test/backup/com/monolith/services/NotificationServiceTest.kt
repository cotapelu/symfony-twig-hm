package com.monolith.services
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Test
class NotificationServiceTest {
    private val service = NotificationService()
    @Test
    fun `test perform`() {
        val result = service.perform("test")
        assertEquals("NotificationService: test", result)
    }
}
