package experiences

import java.time.LocalDate
import java.time.LocalDateTime
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap

class ExperienceBookingRepository {
    private val bookings = ConcurrentHashMap<String, ExperienceBooking>()

    fun save(booking: ExperienceBooking): ExperienceBooking {
        bookings[booking.id] = booking
        return booking
    }

    fun findById(id: String): ExperienceBooking? {
        return bookings[id]
    }

    fun findByUserId(userId: String): List<ExperienceBooking> {
        return bookings.values.filter { it.userId == userId }
    }

    fun findByExperienceId(experienceId: String): List<ExperienceBooking> {
        return bookings.values.filter { it.experienceId == experienceId }
    }

    fun findByDate(experienceId: String, date: LocalDate): List<ExperienceBooking> {
        return bookings.values.filter { 
            it.experienceId == experienceId && it.date == date 
        }
    }

    fun update(booking: ExperienceBooking): ExperienceBooking {
        bookings[booking.id] = booking
        return booking
    }

    fun countBookingsForDate(experienceId: String, date: LocalDate): Int {
        return findByDate(experienceId, date).sumOf { it.participants }
    }
}

data class BookingStatus {
    companion object {
        const val PENDING = "PENDING"
        const val CONFIRMED = "CONFIRMED"
        const val COMPLETED = "COMPLETED"
        const val CANCELLED = "CANCELLED"
    }
}
