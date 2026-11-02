package hotelbooking

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import io.ktor.util.*
import java.time.LocalDate

class HotelBookingHandler(private val service: HotelBookingService) {

    private fun extractUserId(call: ApplicationCall): String? {
        return call.request.headers["X-User-Id"]
    }

    private fun extractToken(call: ApplicationCall): String? {
        val authHeader = call.request.headers["Authorization"]
        return authHeader?.removePrefix("Bearer ")
    }

    private fun requireAuth(call: ApplicationCall): String? {
        val userId = extractUserId(call)
        if (userId.isNullOrBlank()) {
            return null
        }
        return userId
    }

    private fun validateInput(value: String?, maxLength: Int, fieldName: String): String? {
        if (value != null && value.length > maxLength) {
            return "$fieldName too long (max $maxLength chars)"
        }
        return null
    }

    private fun validateRating(rating: Int): String? {
        if (rating < 1 || rating > 5) {
            return "Rating must be between 1 and 5"
        }
        return null
    }

    private fun validatePositive(value: Double?, fieldName: String): String? {
        if (value != null && value < 0) {
            return "$fieldName must be positive"
        }
        return null
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

            val validationError = validatePositive(minPrice, "minPrice") 
                ?: validatePositive(maxPrice, "maxPrice")
                ?: (if (guests < 1) "Guests must be at least 1" else null)
                ?: (if (rooms < 1) "Rooms must be at least 1" else null)
            
            if (validationError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to validationError))
                return
            }

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
            
            if (hotelId.length > 100) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Invalid hotel ID"))
                return
            }
            
            val hotel = service.getHotel(hotelId)

            if (hotel != null) {
                val rating = service.getHotelRating(hotelId)
                call.respond(mapOf("hotel" to hotel, "rating" to rating))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Hotel not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get hotel")))
        }
    }

    suspend fun getHotelRooms(call: ApplicationCall) {
        try {
            val hotelId = call.parameters["id"] ?: throw IllegalArgumentException("Hotel ID required")
            
            if (hotelId.length > 100) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Invalid hotel ID"))
                return
            }
            
            val rooms = service.getHotelRoomTypes(hotelId)
            call.respond(mapOf("rooms" to rooms, "count" to rooms.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get rooms")))
        }
    }

    suspend fun getHotelAvailability(call: ApplicationCall) {
        try {
            val hotelId = call.parameters["id"] ?: throw IllegalArgumentException("Hotel ID required")
            val checkIn = call.request.queryParameters["checkIn"] ?: throw IllegalArgumentException("Check-in date required")
            val checkOut = call.request.queryParameters["checkOut"] ?: throw IllegalArgumentException("Check-out date required")

            if (hotelId.length > 100) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Invalid hotel ID"))
                return
            }

            val checkInDate = parseDate(checkIn)
            val checkOutDate = parseDate(checkOut)

            if (checkOutDate.isBefore(checkInDate) || checkOutDate.isEqual(checkInDate)) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Check-out must be after check-in"))
                return
            }

            val availability = service.checkAvailability(hotelId, checkInDate, checkOutDate)
            call.respond(mapOf("availability" to availability))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Availability check failed")))
        }
    }

    suspend fun getHotelReviews(call: ApplicationCall) {
        try {
            val hotelId = call.parameters["id"] ?: throw IllegalArgumentException("Hotel ID required")
            val limit = call.request.queryParameters["limit"]?.toIntOrNull() ?: 20
            
            if (hotelId.length > 100) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Invalid hotel ID"))
                return
            }
            
            val reviews = service.getHotelReviews(hotelId, limit.coerceIn(1, 100))
            call.respond(mapOf("reviews" to reviews, "count" to reviews.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reviews")))
        }
    }

    suspend fun createBooking(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val request = call.receive<BookingRequest>()
            
            // Validate booking request
            if (request.hotelId.isBlank() || request.roomTypeId.isBlank()) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Hotel ID and Room Type ID required"))
                return
            }
            
            if (request.checkOut.isBefore(request.checkIn) || request.checkOut.isEqual(request.checkIn)) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Check-out must be after check-in"))
                return
            }
            
            if (request.guests < 1 || request.guests > 20) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Guests must be between 1 and 20"))
                return
            }
            
            if (request.totalPrice < 0) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Total price must be positive"))
                return
            }

            // Ensure user can only book for themselves
            val bookingRequest = request.copy(userId = userId)
            val booking = service.createBooking(bookingRequest)
            call.respond(HttpStatusCode.Created, mapOf("booking" to booking))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Booking failed")))
        }
    }

    suspend fun getBooking(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val booking = service.getBooking(bookingId)

            if (booking != null) {
                // Verify ownership
                if (booking.userId != userId) {
                    call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Access denied"))
                    return
                }
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
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val status = call.request.queryParameters["status"]
            val bookings = service.getUserBookings(userId, status)
            call.respond(mapOf("bookings" to bookings, "count" to bookings.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get bookings")))
        }
    }

    suspend fun cancelBooking(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            
            // Verify ownership before cancellation
            val existingBooking = service.getBooking(bookingId)
            if (existingBooking == null) {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Booking not found"))
                return
            }
            
            if (existingBooking.userId != userId) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Cannot cancel other user's booking"))
                return
            }

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

    suspend fun addReview(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val request = call.receive<ReviewRequest>()
            
            val ratingError = validateRating(request.rating)
            if (ratingError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to ratingError))
                return
            }
            
            if (request.comment.length > 2000) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Comment too long (max 2000 chars)"))
                return
            }

            // Ensure user can only review for themselves
            val reviewRequest = request.copy(userId = userId)
            val review = service.addReview(
                reviewRequest.userId,
                reviewRequest.hotelId,
                reviewRequest.rating,
                reviewRequest.comment,
                reviewRequest.title
            )
            call.respond(HttpStatusCode.Created, mapOf("review" to review))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to add review")))
        }
    }

    private fun parseDate(dateStr: String): LocalDate {
        return try {
            LocalDate.parse(dateStr)
        } catch (e: Exception) {
            throw IllegalArgumentException("Invalid date format: $dateStr")
        }
    }
}

data class SearchRequest(
    val city: String? = null,
    val country: String? = null,
    val checkIn: LocalDate = LocalDate.now(),
    val checkOut: LocalDate = LocalDate.now().plusDays(1),
    val guests: Int = 1,
    val rooms: Int = 1,
    val minPrice: Double? = null,
    val maxPrice: Double? = null,
    val starRating: Int? = null,
    val limit: Int = 20
)

data class BookingRequest(
    val userId: String,
    val hotelId: String,
    val roomTypeId: String,
    val checkIn: LocalDate,
    val checkOut: LocalDate,
    val guests: Int = 1,
    val totalPrice: Double,
    val guestInfo: GuestInfo? = null,
    val specialRequests: String? = null
)

data class ReviewRequest(
    val userId: String,
    val hotelId: String,
    val rating: Int,
    val title: String? = null,
    val comment: String
)