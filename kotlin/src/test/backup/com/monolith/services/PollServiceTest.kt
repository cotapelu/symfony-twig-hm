package com.monolith.services
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Test
class PollServiceTest {
    private val service = PollService()
    @Test
    fun `test perform`() {
        val result = service.perform("test")
        assertEquals("PollService: test", result)
    }
}
