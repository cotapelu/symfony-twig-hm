package experiences

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import io.ktor.http.*

private fun authenticate(call: ApplicationCall): Boolean {
	val authHeader = call.request.headers["Authorization"]
	return authHeader != null && authHeader.startsWith("Bearer ") && authHeader.length > 7
}

private fun requireAuth(call: ApplicationCall) {
	if (!authenticate(call)) {
		call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
		throw IllegalArgumentException("Unauthorized")
	}
}

fun Route.experiencesRoutes() {
    val service = ExperiencesService(
        ExperienceRepository(),
        ExperienceBookingRepository(),
        ExperienceReviewRepository()
    )
    
    // Experience routes
    get("/api/experiences/search") {
        val request = call.receive<ExperienceSearchRequest>()
        val experiences = service.searchExperiences(request)
        call.respond(experiences)
    }
    
    get("/api/experiences/{experienceId}") {
        val experienceId = call.parameters["experienceId"] ?: throw IllegalArgumentException("Experience ID required")
        val experience = service.getExperience(experienceId)
        if (experience != null) {
            call.respond(experience)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Experience not found")
        }
    }
    
    get("/api/experiences/{experienceId}/schedule") {
        val experienceId = call.parameters["experienceId"] ?: throw IllegalArgumentException("Experience ID required")
        val schedule = service.getExperienceSchedule(experienceId)
        call.respond(schedule)
    }
    
    get("/api/experiences/{experienceId}/reviews") {
        val experienceId = call.parameters["experienceId"] ?: throw IllegalArgumentException("Experience ID required")
        val reviews = service.getExperienceReviews(experienceId)
        call.respond(reviews)
    }
    
// Booking routes
	post("/api/experiences/{experienceId}/book") {
		requireAuth(call)
		val experienceId = call.parameters["experienceId"] ?: throw IllegalArgumentException("Experience ID required")
		val request = call.receive<ExperienceBookingRequest>()
		val booking = service.createBooking(experienceId, request)
		call.respond(booking)
	}
    
    get("/api/experience-bookings/{bookingId}") {
        val bookingId = call.parameters["bookingId"] ?: throw IllegalArgumentException("Booking ID required")
        val booking = service.getBooking(bookingId)
        if (booking != null) {
            call.respond(booking)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Booking not found")
        }
    }
    
    get("/api/experience-bookings/user/{userId}") {
        val userId = call.parameters["userId"] ?: throw IllegalArgumentException("User ID required")
        val bookings = service.getUserBookings(userId)
        call.respond(bookings)
    }
    
    post("/api/experience-bookings/{bookingId}/cancel") {
        val bookingId = call.parameters["bookingId"] ?: throw IllegalArgumentException("Booking ID required")
        val booking = service.cancelBooking(bookingId)
        if (booking != null) {
            call.respond(booking)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot cancel booking")
        }
    }
    
    // Categories
    get("/api/experiences/categories") {
        val categories = service.getExperienceCategories()
        call.respond(categories)
    }
}

data class ExperienceSearchRequest(
    val query: String? = null,
    val category: String? = null,
    val city: String? = null,
    val minPrice: Double? = null,
    val maxPrice: Double? = null,
    val duration: Int? = null,
    val rating: Double? = null,
    val limit: Int = 20
)

data class ExperienceBookingRequest(
    val userId: String,
    val date: String,
    val time: String,
    val participants: Int,
    val specialRequests: String? = null
)
