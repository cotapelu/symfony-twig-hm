package com.monolith.services
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Test
class AnalyticsServiceTest {
    private val service = AnalyticsService()
    @Test
    fun `test perform`() {
        val result = service.perform("test")
        assertEquals("AnalyticsService: test", result)
    }
}
