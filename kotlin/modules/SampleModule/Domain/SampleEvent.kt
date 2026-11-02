// Sample Domain Event
package samplemodule.domain.event

import java.time.Instant

/**
 * Sample Domain Event
 * Represents something that happened in the domain
 */
data class SampleEvent(
    val entityId: Int,
    val name: String,
    val value: Int,
    val occurredOn: Instant = Instant.now()
)