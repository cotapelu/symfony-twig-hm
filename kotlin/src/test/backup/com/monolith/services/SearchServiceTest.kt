package com.monolith.services
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Test
class SearchServiceTest {
    private val service = SearchService()
    @Test
    fun `test perform`() {
        val result = service.perform("test")
        assertEquals("SearchService: test", result)
    }
}
