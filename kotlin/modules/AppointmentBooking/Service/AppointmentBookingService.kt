package appointmentbooking

import java.time.LocalDateTime
import java.time.temporal.ChronoUnit
import java.util.UUID

class AppointmentBookingService(
    private val providerRepository: ServiceProviderRepository,
    private val appointmentRepository: AppointmentRepository,
    private val reviewRepository: AppointmentReviewRepository
) {

    fun searchProviders(request: ProviderSearchRequest): List<ServiceProvider> {
        var providers = providerRepository.findByProfession(request.profession)
        
        if (request.city != null) {
            providers = providers.filter { it.location?.city == request.city }
        }
        
        if (request.maxPrice != null) {
            providers = providers.filter { it.hourlyRate <= request.maxPrice }
        }
        
        if (request.minRating != null) {
            providers = providers.filter { it.rating >= request.minRating }
        }
        
        return when (request.sortBy) {
            SortOption.PRICE -> providers.sortedBy { it.hourlyRate }
            SortOption.RATING -> providers.sortedByDescending { it.rating }
            SortOption.EXPERIENCE -> providers.sortedByDescending { it.experience }
        }.take(request.limit)
    }

    fun getProvider(providerId: String): ServiceProvider? {
        return providerRepository.findById(providerId)
    }

    fun getProviderAvailability(providerId: String, date: LocalDateTime): List<TimeSlot> {
        val provider = providerRepository.findById(providerId) 
            ?: throw IllegalArgumentException("Provider not found")
        
        val dayOfWeek = date.dayOfWeek.value
        return provider.availability.filter { it.dayOfWeek == dayOfWeek && it.isAvailable }
    }

    fun getProviderServices(providerId: String): List<ServiceOffered> {
        val provider = providerRepository.findById(providerId)
            ?: throw IllegalArgumentException("Provider not found")
        return provider.services
    }

    fun bookAppointment(request: AppointmentRequest): Appointment {
        val provider = providerRepository.findById(request.providerId)
            ?: throw IllegalArgumentException("Provider not found")
        
        val service = provider.services.find { it.id == request.serviceId }
            ?: throw IllegalArgumentException("Service not found")
        
        if (!provider.isAvailable) {
            throw IllegalStateException("Provider is not available")
        }
        
        // Validate that preferredDateTime is in the future
        if (request.preferredDateTime.isBefore(LocalDateTime.now())) {
            throw IllegalArgumentException("Appointment date and time must be in the future")
        }
        
        // Validate that service duration is positive
        if (service.duration <= 0) {
            throw IllegalArgumentException("Service duration must be positive")
        }
        
        // Validate that service price is non-negative
        if (service.price < 0) {
            throw IllegalArgumentException("Service price cannot be negative")
        }
        
        val isSlotAvailable = checkSlotAvailability(
            request.providerId,
            request.preferredDateTime,
            service.duration
        )
        
        if (!isSlotAvailable) {
            throw IllegalStateException("Time slot is not available")
        }
        
        val appointment = Appointment(
            id = UUID.randomUUID().toString(),
            userId = request.userId,
            providerId = request.providerId,
            serviceId = request.serviceId,
            dateTime = request.preferredDateTime,
            duration = service.duration,
            location = provider.location?.address ?: "TBD",
            status = AppointmentStatus.SCHEDULED,
            notes = request.notes,
            totalPrice = service.price
        )
        
        return appointmentRepository.save(appointment)
    }

    private fun checkSlotAvailability(providerId: String, dateTime: LocalDateTime, duration: Int): Boolean {
        val existingAppointments = appointmentRepository.findByProviderAndDate(
            providerId,
            dateTime.toLocalDate()
        )
        
        val requestedEnd = dateTime.plusMinutes(duration.toLong())
        
        return existingAppointments.none { existing ->
            val existingEnd = existing.dateTime.plusMinutes(existing.duration.toLong())
            dateTime.isBefore(existingEnd) && requestedEnd.isAfter(existing.dateTime)
        }
    }

    fun getAppointment(appointmentId: String): Appointment? {
        return appointmentRepository.findById(appointmentId)
    }

    fun getUserAppointments(userId: String): List<Appointment> {
        return appointmentRepository.findByUserIdOrderByDateTimeDesc(userId)
    }

    fun getProviderAppointments(providerId: String): List<Appointment> {
        return appointmentRepository.findByProviderIdOrderByDateTimeDesc(providerId)
    }

    fun confirmAppointment(appointmentId: String): Appointment? {
        val appointment = appointmentRepository.findById(appointmentId)
            ?: throw IllegalArgumentException("Appointment not found")
        
        if (appointment.status != AppointmentStatus.SCHEDULED) {
            throw IllegalStateException("Appointment cannot be confirmed")
        }
        
        return appointmentRepository.save(appointment.copy(status = AppointmentStatus.CONFIRMED))
    }

    fun startAppointment(appointmentId: String): Appointment? {
        val appointment = appointmentRepository.findById(appointmentId)
            ?: throw IllegalArgumentException("Appointment not found")
        
        if (appointment.status != AppointmentStatus.CONFIRMED) {
            throw IllegalStateException("Appointment cannot be started")
        }
        
        return appointmentRepository.save(appointment.copy(status = AppointmentStatus.IN_PROGRESS))
    }

    fun completeAppointment(appointmentId: String): Appointment? {
        val appointment = appointmentRepository.findById(appointmentId)
            ?: throw IllegalArgumentException("Appointment not found")
        
        if (appointment.status != AppointmentStatus.IN_PROGRESS) {
            throw IllegalStateException("Appointment cannot be completed")
        }
        
        return appointmentRepository.save(appointment.copy(status = AppointmentStatus.COMPLETED))
    }

    fun cancelAppointment(appointmentId: String): Appointment? {
        val appointment = appointmentRepository.findById(appointmentId)
            ?: throw IllegalArgumentException("Appointment not found")
        
        if (appointment.status == AppointmentStatus.COMPLETED ||
            appointment.status == AppointmentStatus.CANCELLED) {
            throw IllegalStateException("Appointment cannot be cancelled")
        }
        
        return appointmentRepository.save(appointment.copy(status = AppointmentStatus.CANCELLED))
    }

    fun markNoShow(appointmentId: String): Appointment? {
        val appointment = appointmentRepository.findById(appointmentId)
            ?: throw IllegalArgumentException("Appointment not found")
        
        // Validate that appointment is in a state that allows marking as no-show
        // Only scheduled or confirmed appointments can be marked as no-show
        when (appointment.status) {
            AppointmentStatus.SCHEDULED, AppointmentStatus.CONFIRMED -> {
                // Valid states for marking as no-show
            }
            else -> {
                throw IllegalStateException("Appointment cannot be marked as no-show from status: ${appointment.status}")
            }
        }
        
        return appointmentRepository.save(appointment.copy(status = AppointmentStatus.NO_SHOW))
    }

    fun addReview(
        userId: String,
        appointmentId: String,
        rating: Int,
        comment: String
    ): AppointmentReview {
        val appointment = appointmentRepository.findById(appointmentId)
            ?: throw IllegalArgumentException("Appointment not found")
        
        if (appointment.status != AppointmentStatus.COMPLETED) {
            throw IllegalStateException("Can only review completed appointments")
        }
        
        val review = AppointmentReview(
            id = UUID.randomUUID().toString(),
            appointmentId = appointmentId,
            userId = userId,
            providerId = appointment.providerId,
            rating = rating,
            comment = comment
        )
        
        // Update provider rating
        val existingReviews = reviewRepository.findByProviderId(appointment.providerId)
        val newAvgRating = (existingReviews.sumOf { it.rating } + rating).toDouble() / (existingReviews.size + 1)
        val provider = providerRepository.findById(appointment.providerId)
        provider?.let {
            providerRepository.save(it.copy(
                rating = newAvgRating,
                reviewCount = it.reviewCount + 1
            ))
        }
        
        return reviewRepository.save(review)
    }

    fun getProviderReviews(providerId: String): List<AppointmentReview> {
        return reviewRepository.findByProviderIdOrderByCreatedAtDesc(providerId)
    }

    fun getUpcomingAppointments(providerId: String, days: Int): List<Appointment> {
        val now = LocalDateTime.now()
        val futureDate = now.plusDays(days.toLong())
        return appointmentRepository.findByProviderAndDateRange(providerId, now, futureDate)
            .filter { it.status == AppointmentStatus.SCHEDULED || it.status == AppointmentStatus.CONFIRMED }
    }
}

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
