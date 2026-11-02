package emailservice

import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test

class EmailserviceServiceTest {

    private val repository = FakeEmailRepository()
    private val service = EmailserviceService(repository)

    @Test
    fun `sendEmail should return success`() {
        val result = service.sendEmail("test@example.com", "Subject", "Body")
        assertTrue(result)
    }

    @Test
    fun `createTemplate should return template created status`() {
        val result = service.createTemplate("welcome", "Hello {{name}}")
        assertEquals("template_created", result)
    }

    @Test
    fun `process should return all emails from repository`() {
        repository.emails.add(mapOf("id" to 1, "to" to "test@example.com"))
        val result = service.process(emptyMap())
        assertEquals(1, result.size)
    }

    private class FakeEmailRepository : EmailserviceRepository {
        val emails = mutableListOf<Map<String, Any>>()
        override suspend fun findAll(): List<Any> = emails.map { it as Any }
        override suspend fun findById(id: String): Any? = null
        override suspend fun save(email: Any) {
            if (email is Map<*, *>) {
                @Suppress("UNCHECKED_CAST")
                emails.add(email as Map<String, Any>)
            }
        }
    }
}
