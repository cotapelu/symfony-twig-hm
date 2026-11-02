package samplemodule

import samplemodule.dto.SampleDto
import samplemodule.application.SampleApplicationService

/**
 * Sample Controller for API layer
 * Handles requests and delegates to Application layer
 */
class SampleController(private val applicationService: SampleApplicationService) {
    fun getSample(id: Int): String {
        // In a real implementation, this would delegate to Application layer
        return applicationService.processAndNotify("Get sample for id: $id")
    }

    fun createSample(dto: SampleDto): String {
        // Would receive DTO, validate, and delegate to Application layer
        return applicationService.processAndNotify("Create sample: ${dto.name}, value: ${dto.value}")
    }
}