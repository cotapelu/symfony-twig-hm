package experiences

import java.time.LocalDate
import java.time.LocalDateTime

data class Experience(
    val id: String,
    val title: String,
    val description: String,
    val location: String,
    val category: ExperienceCategory,
    val duration: Int,
    val maxParticipants: Int,
    val pricePerPerson: Double,
    val highlights: List<String>,
    val includes: List<String>,
    val images: List<String>,
    val hostName: String,
    val hostBio: String?,
    val rating: Double = 0.0,
    val reviewCount: Int = 0,
    val availableDates: List<LocalDate>,
    val isActive: Boolean = true,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

enum class ExperienceCategory {
    ADVENTURE, CULTURAL, FOOD, WELLNESS, ARTS, SPORTS, NATURE, NIGHTLIFE, FAMILY, EDUCATIONAL
}

data class ExperienceBooking(
    val id: String,
    val userId: String,
    val experienceId: String,
    val date: LocalDate,
    val participants: Int,
    val totalPrice: Double,
    val status: BookingStatus = BookingStatus.PENDING,
    val specialRequests: String? = null,
    val createdAt: LocalDateTime = LocalDateTime.now()
)
