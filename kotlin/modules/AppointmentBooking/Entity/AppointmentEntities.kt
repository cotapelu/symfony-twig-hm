package appointmentbooking

import java.time.LocalDateTime

data class ProviderLocation(
    val address: String,
    val city: String,
    val district: String,
    val latitude: Double? = null,
    val longitude: Double? = null
)

data class ProviderSearchRequest(
    val profession: String,
    val city: String? = null,
    val maxPrice: Double? = null,
    val minRating: Double? = null,
    val sortBy: SortOption = SortOption.RATING,
    val limit: Int = 20
)

enum class SortOption {
    PRICE, RATING, EXPERIENCE
}
