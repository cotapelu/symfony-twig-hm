// Sample Data Transfer Object for API layer
package samplemodule.dto

/**
 * Sample Data Transfer Object for API layer
 * Used to transfer data between client and server
 */
data class SampleDto(
    val name: String,
    val value: Int,
    val description: String? = null
)