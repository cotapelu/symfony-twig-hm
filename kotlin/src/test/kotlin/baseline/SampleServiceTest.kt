package baseline

import org.junit.jupiter.api.Test
import org.junit.jupiter.api.Assertions.assertEquals
import samplemodule.application.SampleApplicationService
import samplemodule.infrastructure.SampleService

/**
 * Unit tests for SampleApplicationService to demonstrate Kotlin test infrastructure.
 * These tests will produce measurable JaCoCo coverage.
 */
class SampleApplicationServiceTest {

    private val service = SampleApplicationService(SampleService())

    @Test
    fun `processAndNotify returns uppercased file path`() {
        // Arrange
        val input = "/path/to/file.txt"

        // Act
        val result = service.processAndNotify(input)

        // Assert
        assertEquals("/PATH/TO/FILE.TXT", result)
    }

    @Test
    fun `processAndNotify handles empty string`() {
        // Arrange
        val input = ""

        // Act
        val result = service.processAndNotify(input)

        // Assert
        assertEquals("", result)
    }

    @Test
    fun `processAndNotify handles single character`() {
        // Arrange
        val input = "a"

        // Act
        val result = service.processAndNotify(input)

        // Assert
        assertEquals("A", result)
    }

    @Test
    fun `processAndNotify handles mixed case`() {
        // Arrange
        val input = "HelloWorld.TXT"

        // Act
        val result = service.processAndNotify(input)

        // Assert
        assertEquals("HELLOWORLD.TXT", result)
    }
}
