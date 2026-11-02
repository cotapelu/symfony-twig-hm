package eventsbooking

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import java.time.LocalDateTime

fun Route.eventsBookingRoutes() {
    val service = EventsBookingService(
        EventRepository(),
        VenueRepository(),
        EventBookingRepository(),
        EventReviewRepository()
    )
    
    // Event routes
    get("/api/events/search") {
        val request = call.receive<EventSearchRequest>()
        val events = service.searchEvents(request)
        call.respond(events)
    }
    
    get("/api/events/{eventId}") {
        val eventId = call.parameters["eventId"] ?: throw IllegalArgumentException("Event ID required")
        val event = service.getEvent(eventId)
        if (event != null) {
            call.respond(event)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Event not found")
        }
    }
    
    get("/api/events/{eventId}/schedule") {
        val eventId = call.parameters["eventId"] ?: throw IllegalArgumentException("Event ID required")
        val schedule = service.getEventSchedule(eventId)
        call.respond(schedule)
    }
    
    get("/api/events/{eventId}/tickets") {
        val eventId = call.parameters["eventId"] ?: throw IllegalArgumentException("Event ID required")
        val tickets = service.getAvailableTickets(eventId)
        call.respond(tickets)
    }
    
    get("/api/events/{eventId}/venue") {
        val eventId = call.parameters["eventId"] ?: throw IllegalArgumentException("Event ID required")
        val venue = service.getEventVenue(eventId)
        if (venue != null) {
            call.respond(venue)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Venue not found")
        }
    }
    
    get("/api/events/{eventId}/reviews") {
        val eventId = call.parameters["eventId"] ?: throw IllegalArgumentException("Event ID required")
        val reviews = service.getEventReviews(eventId)
        call.respond(reviews)
    }
    
    // Booking routes
    post("/api/events/{eventId}/book") {
        val eventId = call.parameters["eventId"] ?: throw IllegalArgumentException("Event ID required")
        val request = call.receive<EventBookingRequest>()
        val booking = service.createBooking(eventId, request)
        call.respond(booking)
    }
    
    get("/api/bookings/{bookingId}") {
        val bookingId = call.parameters["bookingId"] ?: throw IllegalArgumentException("Booking ID required")
        val booking = service.getBooking(bookingId)
        if (booking != null) {
            call.respond(booking)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Booking not found")
        }
    }
    
    get("/api/bookings/user/{userId}") {
        val userId = call.parameters["userId"] ?: throw IllegalArgumentException("User ID required")
        val bookings = service.getUserBookings(userId)
        call.respond(bookings)
    }
    
    post("/api/bookings/{bookingId}/cancel") {
        val bookingId = call.parameters["bookingId"] ?: throw IllegalArgumentException("Booking ID required")
        val booking = service.cancelBooking(bookingId)
        if (booking != null) {
            call.respond(booking)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot cancel booking")
        }
    }
    
    post("/api/bookings/{bookingId}/reschedule") {
        val bookingId = call.parameters["bookingId"] ?: throw IllegalArgumentException("Booking ID required")
        val request = call.receive<RescheduleRequest>()
        val booking = service.rescheduleBooking(bookingId, request.newDateTime)
        if (booking != null) {
            call.respond(booking)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot reschedule booking")
        }
    }
    
    post("/api/bookings/{bookingId}/review") {
        val bookingId = call.parameters["bookingId"] ?: throw IllegalArgumentException("Booking ID required")
        val request = call.receive<AddEventReviewRequest>()
        val review = service.addReview(
            request.userId,
            bookingId,
            request.rating,
            request.comment
        )
        call.respond(review)
    }
    
    // Venue routes
    get("/api/venues/{venueId}") {
        val venueId = call.parameters["venueId"] ?: throw IllegalArgumentException("Venue ID required")
        val venue = service.getVenue(venueId)
        if (venue != null) {
            call.respond(venue)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Venue not found")
        }
    }
    
    get("/api/venues/{venueId}/events") {
        val venueId = call.parameters["venueId"] ?: throw IllegalArgumentException("Venue ID required")
        val events = service.getVenueEvents(venueId)
        call.respond(events)
    }
    
    // Categories
    get("/api/events/categories") {
        val categories = service.getEventCategories()
        call.respond(categories)
    }
}

data class EventSearchRequest(
    val query: String? = null,
    val category: String? = null,
    val city: String? = null,
    val startDate: LocalDateTime? = null,
    val endDate: LocalDateTime? = null,
    val minPrice: Double? = null,
    val maxPrice: Double? = null,
    val limit: Int = 20
)

data class EventBookingRequest(
    val userId: String,
    val ticketTypeId: String,
    val quantity: Int = 1,
    val attendeeInfo: List<Map<String, String>> = emptyList()
)

data class RescheduleRequest(
    val newDateTime: LocalDateTime
)

data class AddEventReviewRequest(
    val userId: String,
    val rating: Int,
    val comment: String
)
