package samplemodule.api

import org.junit.Assert.*
import org.junit.Test
import samplemodule.SampleController
import samplemodule.application.SampleApplicationService
import samplemodule.dto.SampleDto
import samplemodule.infrastructure.SampleService

class SampleControllerTest {

    private fun createController(): SampleController {
        val appService = SampleApplicationService(SampleService())
        return SampleController(appService)
    }

    @Test
    fun `getSample returns expected message`() {
        val controller = createController()
        val result = controller.getSample(123)
        assertEquals("GET SAMPLE FOR ID: 123", result)
    }

    @Test
    fun `createSample returns confirmation with name and value`() {
        val controller = createController()
        val dto = SampleDto(name = "Widget", value = 99, description = "test")
        val result = controller.createSample(dto)
        assertEquals("CREATE SAMPLE: WIDGET, VALUE: 99", result)
    }
}
