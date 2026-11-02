package com.monolith.services
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Test
class FileServiceTest {
    private val service = FileService()
    @Test
    fun `test perform`() {
        val result = service.perform("test")
        assertEquals("FileService: test", result)
    }
}
