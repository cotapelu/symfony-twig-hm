package com.monolith.services
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Test
class PaymentServiceTest {
    private val service = PaymentService()
    @Test
    fun `test perform`() {
        val result = service.perform("test")
        assertEquals("PaymentService: test", result)
    }
}
