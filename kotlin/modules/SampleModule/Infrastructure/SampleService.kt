package samplemodule.infrastructure

import samplemodule.contracts.SampleServiceInterface

/**
 * Sample Infrastructure Service
 * Handles external concerns like file system, network, etc.
 * Used by Application layer when needed.
 */
class SampleService : SampleServiceInterface {
    override fun processAndNotify(filePath: String): String {
        // Simulate file processing (in real implementation, would read file)
        val processed = filePath.uppercase()
        // Simulate sending notification
        println("Notification sent: Processed file: $processed")
        return processed
    }
}