// Sample Entity representing a domain object
package samplemodule.domain.model

/**
 * Sample Entity representing a domain object
 * Contains business rules and state
 */
data class SampleEntity(
    val id: Int = 0, // 0 indicates new entity
    val name: String,
    val value: Int,
    val description: String? = null
) {
    init {
        // Business rules validation
        require(!name.isBlank()) { "Name cannot be empty" }
        require(value >= 0) { "Value must be non-negative" }
    }
}