package appointmentbooking

import java.time.LocalDateTime

interface ServiceProviderRepository {
    fun findById(id: String): ServiceProvider?
    fun findByProfession(profession: String): List<ServiceProvider>
    fun findByCity(city: String): List<ServiceProvider>
    fun save(provider: ServiceProvider): ServiceProvider
}

interface AppointmentRepository {
    fun findById(id: String): Appointment?
    fun findByUserIdOrderByDateTimeDesc(userId: String): List<Appointment>
    fun findByProviderIdOrderByDateTimeDesc(providerId: String): List<Appointment>
    fun findByProviderAndDate(providerId: String, date: LocalDateTime): List<Appointment>
    fun findByProviderAndDateRange(providerId: String, start: LocalDateTime, end: LocalDateTime): List<Appointment>
    fun save(appointment: Appointment): Appointment
}

interface AppointmentReviewRepository {
    fun findById(id: String): AppointmentReview?
    fun findByProviderIdOrderByCreatedAtDesc(providerId: String): List<AppointmentReview>
    fun save(review: AppointmentReview): AppointmentReview
}

data class AppointmentReview(
    val id: String,
    val appointmentId: String,
    val userId: String,
    val providerId: String,
    val rating: Int,
    val comment: String,
    val createdAt: LocalDateTime = LocalDateTime.now()
)
