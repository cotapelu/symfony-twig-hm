package appointmentbooking

import java.time.LocalDateTime

data class ServiceProvider(
    val id: String,
    val userId: String,
    val name: String,
    val profession: String,
    val bio: String?,
    val qualifications: List<String>,
    val experience: Int,
    val hourlyRate: Double,
    val rating: Double = 0.0,
    val reviewCount: Int = 0,
    val isVerified: Boolean = false,
    val isAvailable: Boolean = true,
    val services: List<ServiceOffered>,
    val availability: List<TimeSlot>,
    val images: List<String>,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

data class ServiceOffered(
    val id: String,
    val name: String,
    val description: String,
    val duration: Int,
    val price: Double
)

data class TimeSlot(
    val dayOfWeek: Int,
    val startTime: String,
    val endTime: String,
    val isAvailable: Boolean = true
)

data class Appointment(
    val id: String,
    val userId: String,
    val providerId: String,
    val serviceId: String,
    val dateTime: LocalDateTime,
    val duration: Int,
    val location: String,
    val status: AppointmentStatus = AppointmentStatus.SCHEDULED,
    val notes: String? = null,
    val totalPrice: Double,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

enum class AppointmentStatus {
    SCHEDULED, CONFIRMED, IN_PROGRESS, COMPLETED, CANCELLED, NO_SHOW
}

data class AppointmentRequest(
    val userId: String,
    val providerId: String,
    val serviceId: String,
    val preferredDateTime: LocalDateTime,
    val notes: String? = null
)
