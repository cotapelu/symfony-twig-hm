package samplemodule.application

import samplemodule.contracts.SampleServiceInterface

/**
 * Sample Application Service demonstrating application layer responsibilities
 * Contains business logic and coordinates between domain and infrastructure
 */
class SampleApplicationService(
    private val sampleService: SampleServiceInterface
) : SampleServiceInterface {
    override fun processAndNotify(filePath: String): String {
        // Delegate to infrastructure/service layer
        return sampleService.processAndNotify(filePath)
    }
}
