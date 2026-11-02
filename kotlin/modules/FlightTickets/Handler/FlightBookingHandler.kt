package flighttickets

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import java.time.LocalDateTime

class FlightBookingHandler(private val service: FlightBookingService) {

    private fun extractUserId(call: ApplicationCall): String? {
        return call.request.headers["X-User-Id"]
    }

    private fun requireAuth(call: ApplicationCall): String? {
        val userId = extractUserId(call)
        if (userId.isNullOrBlank()) {
            return null
        }
        return userId
    }

    private fun validateSeatClass(seatClass: String): String? {
        val allowed = listOf("ECONOMY", "PREMIUM_ECONOMY", "BUSINESS", "FIRST_CLASS")
        if (!allowed.contains(seatClass)) {
            return "Invalid seat class"
        }
        return null
    }

    private fun validateTripType(tripType: String): String? {
        val allowed = listOf("ONE_WAY", "ROUND_TRIP", "MULTI_CITY")
        if (!allowed.contains(tripType)) {
            return "Invalid trip type"
        }
        return null
    }

    private fun validatePassengers(passengers: Int): String? {
        if (passengers < 1 || passengers > 9) {
            return "Passengers must be between 1 and 9"
        }
        return null
    }

    suspend fun searchFlights(call: ApplicationCall) {
        try {
            val departureCity = call.request.queryParameters["departureCity"]
            val arrivalCity = call.request.queryParameters["arrivalCity"]
            val departureDate = call.request.queryParameters["departureDate"]
            val returnDate = call.request.queryParameters["returnDate"]
            val passengers = call.request.queryParameters["passengers"]?.toIntOrNull() ?: 1
            val seatClass = call.request.queryParameters["seatClass"] ?: "ECONOMY"
            val tripType = call.request.queryParameters["tripType"] ?: "ONE_WAY"

            val seatError = validateSeatClass(seatClass)
            if (seatError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to seatError))
                return
            }

            val tripError = validateTripType(tripType)
            if (tripError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to tripError))
                return
            }

            val passengerError = validatePassengers(passengers)
            if (passengerError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to passengerError))
                return
            }

            val request = FlightSearchRequest(
                departureCity = departureCity,
                arrivalCity = arrivalCity,
                departureDate = departureDate?.let { parseDateTime(it) },
                returnDate = returnDate?.let { parseDateTime(it) },
                passengers = passengers,
                seatClass = seatClass,
                tripType = tripType
            )

            val flights = service.searchFlights(request)
            call.respond(mapOf("flights" to flights, "count" to flights.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun getFlight(call: ApplicationCall) {
        try {
            val flightId = call.parameters["id"] ?: throw IllegalArgumentException("Flight ID required")
            
            if (flightId.length > 100) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Invalid flight ID"))
                return
            }
            
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
            val seatClass = call.request.queryParameters["seatClass"] ?: "ECONOMY"
            
            if (flightId.length > 100) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Invalid flight ID"))
                return
            }

            val seatError = validateSeatClass(seatClass)
            if (seatError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to seatError))
                return
            }
            
            val seats = service.getAvailableSeats(flightId, seatClass)
            call.respond(mapOf("seats" to seats, "count" to seats.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get seats")))
        }
    }

    suspend fun getAirports(call: ApplicationCall) {
        try {
            val search = call.request.queryParameters["search"]
            if (search != null && search.length > 100) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Search query too long"))
                return
            }
            val airports = service.getAirports(search)
            call.respond(mapOf("airports" to airports, "count" to airports.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get airports")))
        }
    }

    suspend fun getAirlines(call: ApplicationCall) {
        try {
            val airlines = service.getAirlines()
            call.respond(mapOf("airlines" to airlines, "count" to airlines.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get airlines")))
        }
    }

    suspend fun createBooking(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val request = call.receive<FlightBookingRequest>()
            
            if (request.flightId.isBlank() || request.seatClass.isBlank()) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Flight ID and seat class required"))
                return
            }

            if (request.passengers.isEmpty()) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "At least one passenger required"))
                return
            }

            if (request.passengers.size > 9) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Maximum 9 passengers per booking"))
                return
            }

            for (passenger in request.passengers) {
                if (passenger.firstName.isBlank() || passenger.lastName.isBlank()) {
                    call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Passenger first and last name required"))
                    return
                }
            }

            val seatError = validateSeatClass(request.seatClass)
            if (seatError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to seatError))
                return
            }

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

            val bookings = service.getUserBookings(userId)
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

    suspend fun checkIn(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val seatNumber = call.request.queryParameters["seatNumber"]
                ?: throw IllegalArgumentException("Seat number required")

            if (seatNumber.length > 10) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Invalid seat number"))
                return
            }

            val existingBooking = service.getBooking(bookingId)
            if (existingBooking == null) {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Booking not found"))
                return
            }
            
            if (existingBooking.userId != userId) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Cannot check-in other user's booking"))
                return
            }

            val booking = service.checkIn(bookingId, seatNumber)

            if (booking != null) {
                call.respond(mapOf("booking" to booking, "message" to "Check-in successful"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Check-in failed"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Check-in failed")))
        }
    }

    suspend fun getBoardingPass(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            
            val existingBooking = service.getBooking(bookingId)
            if (existingBooking == null) {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Booking not found"))
                return
            }
            
            if (existingBooking.userId != userId) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Access denied"))
                return
            }

            val boardingPass = service.getBoardingPass(bookingId)

            if (boardingPass != null) {
                call.respond(mapOf("boardingPass" to boardingPass))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Boarding pass not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get boarding pass")))
        }
    }

    private fun parseDateTime(dateStr: String): LocalDateTime {
        return try {
            LocalDateTime.parse(dateStr)
        } catch (e: Exception) {
            throw IllegalArgumentException("Invalid datetime format: $dateStr")
        }
    }
}

data class FlightSearchRequest(
    val departureCity: String? = null,
    val arrivalCity: String? = null,
    val departureDate: LocalDateTime? = null,
    val returnDate: LocalDateTime? = null,
    val passengers: Int = 1,
    val seatClass: String = "ECONOMY",
    val tripType: String = "ONE_WAY"
)

data class FlightBookingRequest(
    val userId: String,
    val flightId: String,
    val seatClass: String,
    val passengers: List<PassengerInfo>,
    val seatNumber: String? = null
)

data class PassengerInfo(
    val firstName: String,
    val lastName: String,
    val dateOfBirth: String,
    val passportNumber: String? = null,
    val nationality: String? = null
)