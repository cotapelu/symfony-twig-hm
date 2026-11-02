package com.monolith.services
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Test
class MonitoringServiceTest {
    private val service = MonitoringService()
    @Test
    fun `test perform`() {
        val result = service.perform("test")
        assertEquals("MonitoringService: test", result)
    }
}
