package experiences

import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import java.time.LocalDate
import java.time.LocalTime

class ExperiencesServiceTest {

    private val experienceRepo = FakeExperienceRepository()
    private val bookingRepo = FakeExperienceBookingRepository()
    private val reviewRepo = FakeExperienceReviewRepository()
    private val service = ExperiencesService(experienceRepo, bookingRepo, reviewRepo)

    @Test
    fun `createExperience should add experience with id`() {
        val exp = service.createExperience(
            name = "Cooking Class",
            description = "Learn to cook Thai food",
            hostId = "host1",
            location = "Bangkok",
            date = LocalDate.now().plusDays(10),
            startTime = LocalTime.of(10,0),
            endTime = LocalTime.of(13,0),
            capacity = 10,
            price = 75.0
        )
        assertNotNull(exp.id)
        assertEquals("Cooking Class", exp.name)
        assertEquals(10, exp.capacity)
    }

    @Test
    fun `getExperience should return by id`() {
        val exp = Experience(id = "exp1", name = "Yoga Retreat", hostId = "h1", location = "Goa", date = LocalDate.now(), startTime = LocalTime.of(6,0), endTime = LocalTime.of(8,0), capacity = 20, price = 30.0)
        experienceRepo.save(exp)

        val retrieved = service.getExperience("exp1")
        assertEquals("Yoga Retreat", retrieved!!.name)
    }

    @Test
    fun `bookExperience should succeed if slots available`() {
        val exp = Experience(id = "exp2", name = "Hiking Tour", hostId = "h2", location = "Alps", date = LocalDate.now(), startTime = LocalTime.of(8,0), endTime = LocalTime.of(17,0), capacity = 5, price = 100.0, availableSlots = 5)
        experienceRepo.save(exp)

        val booking = service.bookExperience(
            experienceId = "exp2",
            userId = "u1",
            participants = 2
        )

        assertEquals(2, booking.participants)
        val updatedExp = experienceRepo.findById("exp2")!!
        assertEquals(3, updatedExp.availableSlots)
    }

    @Test
    fun `bookExperience should fail if insufficient slots`() {
        val exp = Experience(id = "exp3", capacity = 3, availableSlots = 1, price = 50.0)
        experienceRepo.save(exp)

        val ex = assertThrows<IllegalStateException> {
            service.bookExperience("exp3", "u2", 2)
        }
        assertTrue(ex.message!!.contains("Not enough available slots"))
    }

    @Test
    fun `cancelBooking should free slots and mark cancelled`() {
        val exp = Experience(id = "exp4", capacity = 10, availableSlots = 8, price = 20.0)
        experienceRepo.save(exp)
        val booking = ExperienceBooking(id = "eb1", experienceId = "exp4", userId = "u3", participants = 2, totalAmount = 40.0, status = BookingStatus.CONFIRMED)
        bookingRepo.save(booking)

        val cancelled = service.cancelBooking("eb1")

        assertEquals(BookingStatus.CANCELLED, cancelled.status)
        val updatedExp = experienceRepo.findById("exp4")!!
        assertEquals(10, updatedExp.availableSlots) // 8 + 2
    }

    @Test
    fun `addReview should create review linked to booking`() {
        val exp = Experience(id = "exp5", hostId = "h5")
        experienceRepo.save(exp)
        val booking = ExperienceBooking(id = "eb2", experienceId = "exp5", userId = "u4", participants = 1, totalAmount = 25.0, status = BookingStatus.COMPLETED)
        bookingRepo.save(booking)

        val review = service.addReview(
            bookingId = "eb2",
            rating = 5,
            comment = "Amazing!",
            userId = "u4"
        )

        assertEquals(5, review.rating)
        assertEquals("Amazing!", review.comment)
    }

    // Fakes
    private class FakeExperienceRepository : ExperienceRepository {
        private val experiences = mutableMapOf<String, Experience>()
        override fun findAll(): List<Experience> = experiences.values.toList()
        override fun findById(id: String): Experience? = experiences[id]
        override fun save(exp: Experience): Experience { experiences[exp.id] = exp; return exp }
        override fun delete(id: String) { experiences.remove(id) }
        override fun getByHost(hostId: String): List<Experience> = experiences.values.filter { it.hostId == hostId }.toList()
        override fun search(location: String?, date: LocalDate?): List<Experience> {
            var result = experiences.values.toList()
            location?.let { result = result.filter { it.location == it } }
            date?.let { result = result.filter { it.date == it } }
            return result
        }
    }

    private class FakeExperienceBookingRepository : ExperienceBookingRepository {
        private val bookings = mutableMapOf<String, ExperienceBooking>()
        override fun findAll(): List<ExperienceBooking> = bookings.values.toList()
        override fun findById(id: String): ExperienceBooking? = bookings[id]
        override fun save(booking: ExperienceBooking): ExperienceBooking { bookings[booking.id] = booking; return booking }
        override fun delete(id: String) { bookings.remove(id) }
        override fun findByExperienceId(experienceId: String): List<ExperienceBooking> = bookings.values.filter { it.experienceId == experienceId }.toList()
        override fun findByUserId(userId: String): List<ExperienceBooking> = bookings.values.filter { it.userId == userId }.toList()
        override fun findByStatus(status: BookingStatus): List<ExperienceBooking> = bookings.values.filter { it.status == status }.toList()
    }

    private class FakeExperienceReviewRepository : ExperienceReviewRepository {
        private val reviews = mutableMapOf<String, ExperienceReview>()
        override fun findAll(): List<ExperienceReview> = reviews.values.toList()
        override fun findById(id: String): ExperienceReview? = reviews[id]
        override fun save(review: ExperienceReview): ExperienceReview { reviews[review.id] = review; return review }
        override fun delete(id: String) { reviews.remove(id) }
        override fun findByExperienceId(experienceId: String): List<ExperienceReview> = reviews.values.filter { it.experienceId == experienceId }.toList()
    }
}
