package emailservice

import kotlinx.coroutines.test.runTest
import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test

class EmailserviceServiceTest {

    @Test
    fun `sendEmail should return success`() = runTest {
        val repository = EmailserviceRepository()
        val service = EmailserviceService(repository)
        val result = service.sendEmail("test@example.com", "Subject", "Body")
        assertTrue(result)
    }

    @Test
    fun `createTemplate should return template created status`() = runTest {
        val repository = EmailserviceRepository()
        val service = EmailserviceService(repository)
        val result = service.createTemplate("welcome", "Hello {{name}}")
        assertEquals("template_created", result)
    }

    @Test
    fun `process should return all emails from repository`() = runTest {
        val repository = EmailserviceRepository()
        val service = EmailserviceService(repository)
        val result = service.process(emptyMap())
        assertTrue(result.isEmpty())
    }
}
