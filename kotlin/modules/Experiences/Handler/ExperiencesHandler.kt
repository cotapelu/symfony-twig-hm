package experiences

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import java.time.LocalDate

class ExperiencesHandler(private val service: ExperiencesService) {
    
    suspend fun searchExperiences(call: ApplicationCall) {
        try {
            val query = call.request.queryParameters["query"]
            val category = call.request.queryParameters["category"]
            val city = call.request.queryParameters["city"]
            val minPrice = call.request.queryParameters["minPrice"]?.toDoubleOrNull()
            val maxPrice = call.request.queryParameters["maxPrice"]?.toDoubleOrNull()
            val duration = call.request.queryParameters["duration"]?.toIntOrNull()
            val rating = call.request.queryParameters["rating"]?.toDoubleOrNull()
            val limit = call.request.queryParameters["limit"]?.toIntOrNull() ?: 20
            
            val request = ExperienceSearchRequest(
                query = query,
                category = category,
                city = city,
                minPrice = minPrice,
                maxPrice = maxPrice,
                duration = duration,
                rating = rating,
                limit = limit
            )
            
            val experiences = service.searchExperiences(request)
            call.respond(mapOf("experiences" to experiences, "count" to experiences.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }
    
    suspend fun getExperience(call: ApplicationCall) {
        try {
            val experienceId = call.parameters["id"] ?: throw IllegalArgumentException("Experience ID required")
            val experience = service.getExperience(experienceId)
            
            if (experience != null) {
                call.respond(mapOf("experience" to experience))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Experience not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get experience")))
        }
    }
    
    suspend fun getExperienceSchedule(call: ApplicationCall) {
        try {
            val experienceId = call.parameters["id"] ?: throw IllegalArgumentException("Experience ID required")
            val schedule = service.getExperienceSchedule(experienceId)
            call.respond(mapOf("schedule" to schedule))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get schedule")))
        }
    }
    
    suspend fun getExperienceReviews(call: ApplicationCall) {
        try {
            val experienceId = call.parameters["id"] ?: throw IllegalArgumentException("Experience ID required")
            val reviews = service.getExperienceReviews(experienceId)
            call.respond(mapOf("reviews" to reviews, "count" to reviews.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reviews")))
        }
    }
    
    suspend fun getCategories(call: ApplicationCall) {
        try {
            val categories = service.getExperienceCategories()
            call.respond(mapOf("categories" to categories))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get categories")))
        }
    }
    
    suspend fun createBooking(call: ApplicationCall) {
        try {
            val experienceId = call.parameters["id"] ?: throw IllegalArgumentException("Experience ID required")
            val request = call.receive<ExperienceBookingRequest>()
            val booking = service.createBooking(experienceId, request)
            call.respond(HttpStatusCode.Created, mapOf("booking" to booking))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Booking failed")))
        }
    }
    
    suspend fun getBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val booking = service.getBooking(bookingId)
            
            if (booking != null) {
                call.respond(mapOf("booking" to booking))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Booking not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get booking")))
        }
    }
    
    suspend fun getUserBookings(call: ApplicationCall) {
        try {
            val userId = call.request.queryParameters["userId"] ?: throw IllegalArgumentException("User ID required")
            val bookings = service.getUserBookings(userId)
            call.respond(mapOf("bookings" to bookings, "count" to bookings.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get bookings")))
        }
    }
    
    suspend fun cancelBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val booking = service.cancelBooking(bookingId)
            
            if (booking != null) {
                call.respond(mapOf("booking" to booking, "message" to "Booking cancelled successfully"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot cancel booking"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Cancellation failed")))
        }
    }
    
    suspend fun confirmBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val booking = service.confirmBooking(bookingId)
            
            if (booking != null) {
                call.respond(mapOf("booking" to booking, "message" to "Booking confirmed"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot confirm booking"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Confirmation failed")))
        }
    }
    
    suspend fun completeBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val booking = service.completeBooking(bookingId)
            
            if (booking != null) {
                call.respond(mapOf("booking" to booking, "message" to "Booking completed"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot complete booking"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Completion failed")))
        }
    }
    
    suspend fun addReview(call: ApplicationCall) {
        try {
            val request = call.receive<ExperienceReviewRequest>()
            val review = service.addReview(
                request.userId,
                request.experienceId,
                request.bookingId,
                request.rating,
                request.title,
                request.comment,
                request.hostRating,
                request.valueRating
            )
            call.respond(HttpStatusCode.Created, mapOf("review" to review))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to add review")))
        }
    }
    
    suspend fun getFeaturedExperiences(call: ApplicationCall) {
        try {
            val limit = call.request.queryParameters["limit"]?.toIntOrNull() ?: 6
            val experiences = service.getFeaturedExperiences(limit)
            call.respond(mapOf("experiences" to experiences, "count" to experiences.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get featured experiences")))
        }
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
    val participants: Int,
    val specialRequests: String? = null
)

data class ExperienceReviewRequest(
    val userId: String,
    val experienceId: String,
    val bookingId: String,
    val rating: Int,
    val title: String,
    val comment: String,
    val hostRating: Int? = null,
    val valueRating: Int? = null
)
