package flighttickets

import io.ktor.server.application.*
import io.ktor.server.routing.*
import io.ktor.server.request.*
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

fun Application.configureFlightBookingRoutes(handler: FlightBookingHandler) {
	routing {
		route("/api/flights") {
			get("/airports") {
				handler.getAirports(call)
			}

			get("/airports/search") {
				handler.searchAirports(call)
			}

			get("/airlines") {
				handler.getAirlines(call)
			}

			get("/search") {
				handler.searchFlights(call)
			}

			get("/{id}") {
				handler.getFlight(call)
			}

			get("/{id}/seats") {
				handler.getAvailableSeats(call)
			}
		}

		route("/api/flight-bookings") {
			get("/my") {
				requireAuth(call)
				handler.getUserBookings(call)
			}

			post {
				requireAuth(call)
				handler.createBooking(call)
            }
            
            get("/{id}") {
                handler.getBooking(call)
            }
            
            post("/{id}/confirm") {
                handler.confirmBooking(call)
            }
            
            post("/{id}/cancel") {
                handler.cancelBooking(call)
            }
            
            post("/{id}/checkin") {
                handler.checkIn(call)
            }
            
            get("/{id}/boarding-pass") {
                handler.getBoardingPass(call)
            }
        }
    }
}