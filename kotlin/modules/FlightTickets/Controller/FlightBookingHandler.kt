package flighttickets

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import com.auth0.jwt.interfaces.JWTPrincipal
import java.time.LocalDate
import java.time.LocalDateTime
import java.time.LocalTime
import java.time.format.DateTimeParseException

class FlightBookingHandler(private val service: FlightBookingService) {

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

    suspend fun searchAirports(call: ApplicationCall) {
        try {
            val query = call.request.queryParameters["query"] ?: ""
            val airports = service.searchAirports(query)
            call.respond(mapOf("airports" to airports))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun searchFlights(call: ApplicationCall) {
        try {
            val departureCity = call.request.queryParameters["departureCity"] ?: throw IllegalArgumentException("Departure city required")
            val arrivalCity = call.request.queryParameters["arrivalCity"] ?: throw IllegalArgumentException("Arrival city required")
            val departureDate = parseDate(call.request.queryParameters["departureDate"] ?: throw IllegalArgumentException("Departure date required"))
            val returnDate = call.request.queryParameters["returnDate"]?.let { parseDate(it) }
            val passengers = call.request.queryParameters["passengers"]?.toIntOrNull() ?: 1
            val seatClass = call.request.queryParameters["seatClass"]?.let { SeatClass.valueOf(it.uppercase()) } ?: SeatClass.ECONOMY
            val tripType = call.request.queryParameters["tripType"]?.let { TripType.valueOf(it.uppercase()) } ?: TripType.ONE_WAY

            val request = FlightSearchRequest(
                departureCity = departureCity,
                arrivalCity = arrivalCity,
                departureDate = departureDate,
                returnDate = returnDate,
                passengers = passengers,
                seatClass = seatClass,
                tripType = tripType
            )

            val result = service.searchFlights(request)
            call.respond(result)
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun getFlight(call: ApplicationCall) {
        try {
            val flightId = call.parameters["id"] ?: throw IllegalArgumentException("Flight ID required")
            val flight = service.getFlight(flightId)
            
            if (flight != null) {
                call.respond(mapOf("flight" to flight))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Flight not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get flight")))
        }
    }

    suspend fun getAvailableSeats(call: ApplicationCall) {
        try {
            val flightId = call.parameters["id"] ?: throw IllegalArgumentException("Flight ID required")
            val seatClass = call.request.queryParameters["seatClass"]?.let { SeatClass.valueOf(it.uppercase()) } ?: SeatClass.ECONOMY
            
            val seats = service.getAvailableSeats(flightId, seatClass)
            call.respond(mapOf("seats" to seats))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get seats")))
        }
    }

    suspend fun createBooking(call: ApplicationCall) {
        try {
            val request = call.receive<CreateFlightBookingRequest>()
            val principal = call.principal<JWTPrincipal>()
            val userId = principal?.subject ?: "anonymous"

            val booking = service.createBooking(
                userId = userId,
                flightId = request.flightId,
                seats = request.seats,
                passengers = request.passengers,
                contactInfo = request.contactInfo,
                luggageInfo = request.luggageInfo,
                mealPreference = request.mealPreference,
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

    suspend fun checkIn(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Booking ID required")); return }
            val authUserId = requireBookingOwnership(call, bookingId)
            if (authUserId == null) return
            val request = call.receive<CheckInRequest>()
            val checkInInfo = service.checkIn(bookingId, request.passengerIndex, request.seatNumber)
            call.respond(mapOf("checkIn" to checkInInfo))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Check-in failed")))
        }
    }

    suspend fun getBoardingPass(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Booking ID required")); return }
            val authUserId = requireBookingOwnership(call, bookingId)
            if (authUserId == null) return
            val passengerIndex = call.request.queryParameters["passengerIndex"]?.toIntOrNull() ?: 0
            val boardingPass = service.getBoardingPass(bookingId, passengerIndex)
            if (boardingPass != null) {
                call.respond(mapOf("boardingPass" to boardingPass))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Boarding pass not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get boarding pass")))
        }
    }

    suspend fun getAirlines(call: ApplicationCall) {
        try {
            val airlines = service.getAirlines()
            call.respond(mapOf("airlines" to airlines))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get airlines")))
        }
    }

    suspend fun getAirports(call: ApplicationCall) {
        try {
            val airports = service.getAirports()
            call.respond(mapOf("airports" to airports))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get airports")))
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

data class CreateFlightBookingRequest(
    val flightId: String,
    val seats: List<String>,
    val passengers: List<Passenger>,
    val contactInfo: FlightContactInfo,
    val luggageInfo: FlightLuggageInfo = FlightLuggageInfo(),
    val mealPreference: String? = null,
    val specialRequests: String? = null
)

data class CheckInRequest(
    val passengerIndex: Int,
    val seatNumber: String
)