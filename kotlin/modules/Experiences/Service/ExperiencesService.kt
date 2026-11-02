package experiences

import java.time.LocalDate
import java.time.LocalDateTime
import java.util.UUID

class ExperiencesService(
    private val experienceRepository: ExperienceRepository,
    private val bookingRepository: ExperienceBookingRepository,
    private val reviewRepository: ExperienceReviewRepository
) {

    fun searchExperiences(request: ExperienceSearchRequest): List<Experience> {
        val category = request.category?.let { 
            ExperienceCategory.valueOf(it.uppercase()) 
        }

        val experiences = experienceRepository.search(
            query = request.query,
            category = category,
            city = request.city,
            minPrice = request.minPrice,
            maxPrice = request.maxPrice,
            duration = request.duration,
            minRating = request.rating
        )

        return experiences.take(request.limit)
    }

    fun getExperience(experienceId: String): Experience? {
        return experienceRepository.findById(experienceId)
    }

    fun getExperienceSchedule(experienceId: String): List<LocalDate> {
        val experience = experienceRepository.findById(experienceId)
            ?: throw IllegalArgumentException("Experience not found")
        return experience.availableDates
    }

    fun getExperienceReviews(experienceId: String): List<ExperienceReview> {
        return reviewRepository.findByExperienceId(experienceId)
    }

    fun getExperienceCategories(): List<String> {
        return ExperienceCategory.values().map { it.name }
    }

    fun createBooking(experienceId: String, request: ExperienceBookingRequest): ExperienceBooking {
        val experience = experienceRepository.findById(experienceId)
            ?: throw IllegalArgumentException("Experience not found")

        if (!experience.isActive) {
            throw IllegalStateException("Experience is not available for booking")
        }

        val bookingDate = LocalDate.parse(request.date)
        
        if (!experience.availableDates.contains(bookingDate)) {
            throw IllegalArgumentException("Selected date is not available")
        }

        val existingBookings = bookingRepository.countBookingsForDate(experienceId, bookingDate)
        if (existingBookings + request.participants > experience.maxParticipants) {
            throw IllegalStateException("Not enough spots available for this date")
        }

        val totalPrice = experience.pricePerPerson * request.participants

        val booking = ExperienceBooking(
            id = UUID.randomUUID().toString(),
            userId = request.userId,
            experienceId = experienceId,
            date = bookingDate,
            participants = request.participants,
            totalPrice = totalPrice,
            status = BookingStatus.PENDING,
            specialRequests = request.specialRequests
        )

        return bookingRepository.save(booking)
    }

    fun getBooking(bookingId: String): ExperienceBooking? {
        return bookingRepository.findById(bookingId)
    }

    fun getUserBookings(userId: String): List<ExperienceBooking> {
        return bookingRepository.findByUserId(userId)
    }

    fun cancelBooking(bookingId: String): ExperienceBooking? {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.status == BookingStatus.CANCELLED) {
            throw IllegalStateException("Booking is already cancelled")
        }

        if (booking.status == BookingStatus.COMPLETED) {
            throw IllegalStateException("Cannot cancel a completed booking")
        }

        val updatedBooking = booking.copy(status = BookingStatus.CANCELLED)
        return bookingRepository.update(updatedBooking)
    }

    fun confirmBooking(bookingId: String): ExperienceBooking? {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.status != BookingStatus.PENDING) {
            throw IllegalStateException("Only pending bookings can be confirmed")
        }

        val updatedBooking = booking.copy(status = BookingStatus.CONFIRMED)
        return bookingRepository.update(updatedBooking)
    }

    fun completeBooking(bookingId: String): ExperienceBooking? {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.status != BookingStatus.CONFIRMED) {
            throw IllegalStateException("Only confirmed bookings can be completed")
        }

        val updatedBooking = booking.copy(status = BookingStatus.COMPLETED)
        return bookingRepository.update(updatedBooking)
    }

    fun addReview(
        userId: String,
        experienceId: String,
        bookingId: String,
        rating: Int,
        title: String,
        comment: String,
        hostRating: Int? = null,
        valueRating: Int? = null
    ): ExperienceReview {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.userId != userId) {
            throw IllegalArgumentException("You can only review your own bookings")
        }

        if (booking.status != BookingStatus.COMPLETED) {
            throw IllegalStateException("Can only review completed experiences")
        }

        if (reviewRepository.hasUserReviewed(userId, experienceId)) {
            throw IllegalStateException("You have already reviewed this experience")
        }

        val review = ExperienceReview(
            userId = userId,
            experienceId = experienceId,
            bookingId = bookingId,
            rating = rating,
            title = title,
            comment = comment,
            hostRating = hostRating,
            valueRating = valueRating
        )

        return reviewRepository.save(review)
    }

    fun getFeaturedExperiences(limit: Int = 6): List<Experience> {
        return experienceRepository.findActive()
            .sortedByDescending { it.rating }
            .take(limit)
    }

    fun getExperiencesByCategory(category: ExperienceCategory): List<Experience> {
        return experienceRepository.search(category = category)
    }
}
