package hotelbooking

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import com.auth0.jwt.interfaces.JWTPrincipal
import java.time.LocalDate
import java.time.format.DateTimeParseException

class HotelBookingHandler(private val service: HotelBookingService) {

    private suspend fun requireBookingOwnership(call: ApplicationCall, bookingId: String): String? {
        val principal = call.principal<JWTPrincipal>()
            ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required")); return null }
        val userId = principal.subject
            ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject")); return null }

        val booking = service.getBooking(bookingId)
            ?: run { call.respond(HttpStatusCode.NotFound, mapOf("error" to "Booking not found")); return null }

        if (booking.userId != userId) {
            call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Access denied: not your booking"))
            return null
        }

        return userId
    }

    suspend fun searchHotels(call: ApplicationCall) {
        try {
            val city = call.request.queryParameters["city"]
            val country = call.request.queryParameters["country"]
            val checkIn = call.request.queryParameters["checkIn"]?.let { parseDate(it) }
            val checkOut = call.request.queryParameters["checkOut"]?.let { parseDate(it) }
            val guests = call.request.queryParameters["guests"]?.toIntOrNull() ?: 1
            val rooms = call.request.queryParameters["rooms"]?.toIntOrNull() ?: 1
            val minPrice = call.request.queryParameters["minPrice"]?.toDoubleOrNull()
            val maxPrice = call.request.queryParameters["maxPrice"]?.toDoubleOrNull()
            val starRating = call.request.queryParameters["starRating"]?.toIntOrNull()

            val request = SearchRequest(
                city = city,
                country = country,
                checkIn = checkIn ?: LocalDate.now(),
                checkOut = checkOut ?: LocalDate.now().plusDays(1),
                guests = guests,
                rooms = rooms,
                minPrice = minPrice,
                maxPrice = maxPrice,
                starRating = starRating
            )

            val hotels = service.searchHotels(request)
            call.respond(mapOf("hotels" to hotels, "count" to hotels.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun getHotel(call: ApplicationCall) {
        try {
            val hotelId = call.parameters["id"] ?: throw IllegalArgumentException("Hotel ID required")
            val hotel = service.getHotel(hotelId)

            if (hotel != null) {
                val rating = service.getHotelRating(hotelId)
                call.respond(mapOf(
                    "hotel" to hotel,
                    "averageRating" to rating.first,
                    "reviewCount" to rating.second
                ))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Hotel not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get hotel")))
        }
    }

    suspend fun getHotelRoomTypes(call: ApplicationCall) {
        try {
            val hotelId = call.parameters["id"] ?: throw IllegalArgumentException("Hotel ID required")
            val roomTypes = service.getHotelRoomTypes(hotelId)
            call.respond(mapOf("roomTypes" to roomTypes))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get room types")))
        }
    }

    suspend fun checkAvailability(call: ApplicationCall) {
        try {
            val hotelId = call.parameters["id"] ?: throw IllegalArgumentException("Hotel ID required")
            val checkIn = call.request.queryParameters["checkIn"]?.let { parseDate(it) }
                ?: throw IllegalArgumentException("Check-in date required")
            val checkOut = call.request.queryParameters["checkOut"]?.let { parseDate(it) }
                ?: throw IllegalArgumentException("Check-out date required")
            val rooms = call.request.queryParameters["rooms"]?.toIntOrNull() ?: 1

            val availableRoomTypes = service.checkAvailability(hotelId, checkIn, checkOut, rooms)
            call.respond(mapOf("availableRoomTypes" to availableRoomTypes))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Availability check failed")))
        }
    }

    suspend fun createBooking(call: ApplicationCall) {
        try {
            val request = call.receive<CreateBookingRequest>()
            val principal = call.principal<JWTPrincipal>()
            val userId = principal?.subject ?: "anonymous" // allow anonymous for demo, but enforce later if needed

            val booking = service.createBooking(
                userId = userId,
                hotelId = request.hotelId,
                roomTypeId = request.roomTypeId,
                checkIn = parseDate(request.checkIn),
                checkOut = parseDate(request.checkOut),
                guestCount = request.guestCount,
                guestInfo = request.guestInfo,
                specialRequests = request.specialRequests
            )

            call.respond(HttpStatusCode.Created, mapOf("booking" to booking))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Booking creation failed")))
        }
    }

    suspend fun getBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Booking ID required")); return }
            val authUserId = requireBookingOwnership(call, bookingId)
            if (authUserId == null) return
            call.respond(mapOf("booking" to service.getBooking(bookingId)!!))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get booking")))
        }
    }

    suspend fun getUserBookings(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required")); return }
            val userId = principal.subject ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject")); return }
            val bookings = service.getUserBookings(userId)
            call.respond(mapOf("bookings" to bookings))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get bookings")))
        }
    }

    suspend fun confirmBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Booking ID required")); return }
            val authUserId = requireBookingOwnership(call, bookingId)
            if (authUserId == null) return
            val booking = service.confirmBooking(bookingId)
            if (booking != null) {
                call.respond(mapOf("booking" to booking))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Booking not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to confirm booking")))
        }
    }

    suspend fun cancelBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val booking = service.cancelBooking(bookingId)

            if (booking != null) {
                call.respond(mapOf("booking" to booking))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Booking not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to cancel booking")))
        }
    }

    suspend fun addReview(call: ApplicationCall) {
        try {
            val request = call.receive<AddReviewRequest>()
            val principal = call.principal<JWTPrincipal>()
            val userId = principal?.subject ?: "anonymous" // optional for demo

            val review = service.addReview(
                userId = userId,
                hotelId = request.hotelId,
                bookingId = request.bookingId,
                rating = request.rating,
                title = request.title,
                content = request.content,
                categories = request.categories
            )

            call.respond(HttpStatusCode.Created, mapOf("review" to review))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to add review")))
        }
    }

    suspend fun getHotelReviews(call: ApplicationCall) {
        try {
            val hotelId = call.parameters["id"] ?: throw IllegalArgumentException("Hotel ID required")
            val reviews = service.getHotelReviews(hotelId)
            call.respond(mapOf("reviews" to reviews))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reviews")))
        }
    }

    private fun parseDate(dateStr: String): LocalDate {
        return try {
            LocalDate.parse(dateStr)
        } catch (e: DateTimeParseException) {
            throw IllegalArgumentException("Invalid date format. Use YYYY-MM-DD")
        }
    }
}

data class CreateBookingRequest(
    val hotelId: String,
    val roomTypeId: String,
    val checkIn: String,
    val checkOut: String,
    val guestCount: Int,
    val guestInfo: GuestInfo,
    val specialRequests: String? = null
)

data class AddReviewRequest(
    val hotelId: String,
    val bookingId: String,
    val rating: Int,
    val title: String,
    val content: String,
    val categories: Map<String, Int> = emptyMap()
)