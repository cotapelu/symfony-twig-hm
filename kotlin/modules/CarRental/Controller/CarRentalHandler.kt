package carrental

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import com.auth0.jwt.interfaces.JWTPrincipal
import java.time.LocalDate
import java.time.LocalDateTime
import java.time.format.DateTimeParseException

class CarRentalHandler(private val service: CarRentalService) {

    /**
     * Require authentication and ownership of a booking.
     * Returns the authenticated user ID if successful, null if error (already responded).
     */
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

    suspend fun searchCars(call: ApplicationCall) {
        try {
            val carType = call.request.queryParameters["carType"]
            val transmission = call.request.queryParameters["transmission"]
            val maxPrice = call.request.queryParameters["maxPrice"]?.toDoubleOrNull()
            val seats = call.request.queryParameters["seats"]?.toIntOrNull()
            val location = call.request.queryParameters["location"]
            val sortBy = call.request.queryParameters["sortBy"]?.let { 
                SortOption.valueOf(it.uppercase()) 
            } ?: SortOption.RATING

            val request = CarSearchRequest(
                carType = carType,
                transmission = transmission,
                maxPricePerDay = maxPrice,
                seats = seats,
                location = location,
                sortBy = sortBy
            )

            val cars = service.searchCars(request)
            call.respond(mapOf("cars" to cars))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun getCar(call: ApplicationCall) {
        try {
            val carId = call.parameters["id"] ?: throw IllegalArgumentException("Car ID required")
            val car = service.getCar(carId)
            
            if (car != null) {
                call.respond(mapOf("car" to car))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Car not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get car")))
        }
    }

    suspend fun checkAvailability(call: ApplicationCall) {
        try {
            val carId = call.parameters["id"] ?: throw IllegalArgumentException("Car ID required")
            val startDate = parseDate(call.request.queryParameters["startDate"] ?: throw IllegalArgumentException("Start date required"))
            val endDate = parseDate(call.request.queryParameters["endDate"] ?: throw IllegalArgumentException("End date required"))
            
            val isAvailable = service.checkAvailability(carId, startDate, endDate)
            call.respond(mapOf("available" to isAvailable))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Availability check failed")))
        }
    }

    suspend fun createBooking(call: ApplicationCall) {
        try {
            val request = call.receive<CreateCarBookingRequest>()
            
            val booking = service.createBooking(request)
            call.respond(HttpStatusCode.Created, mapOf("booking" to booking))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Booking creation failed")))
        }
    }

    suspend fun getBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Booking ID required")); return }
            val authUserId = requireBookingOwnership(call, bookingId)
            if (authUserId == null) return // already responded
            val booking = service.getBooking(bookingId) // we already checked it exists and belongs to user
            call.respond(mapOf("booking" to booking!!))
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
            val bookingId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Booking ID required")); return }
            val authUserId = requireBookingOwnership(call, bookingId)
            if (authUserId == null) return
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

    suspend fun getFeaturedCars(call: ApplicationCall) {
        try {
            val cars = service.getFeaturedCars()
            call.respond(mapOf("cars" to cars))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get featured cars")))
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