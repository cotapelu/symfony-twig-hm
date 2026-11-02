package samplemodule.application

import org.junit.Assert.*
import org.junit.Test
import samplemodule.contracts.SampleServiceInterface
import samplemodule.infrastructure.SampleService

class SampleApplicationServiceTest {

    @Test
    fun `processAndNotify delegates to SampleService`() {
        val sampleService = SampleService()
        val appService = SampleApplicationService(sampleService)
        val result = appService.processAndNotify("input.txt")
        assertEquals("INPUT.TXT", result)
    }

    @Test
    fun `processAndNotify returns whatever SampleService returns`() {
        val mockService = object : SampleServiceInterface {
            override fun processAndNotify(filePath: String): String = "custom:$filePath"
        }
        val appService = SampleApplicationService(mockService)
        val result = appService.processAndNotify("test")
        assertEquals("custom:test", result)
    }
}
