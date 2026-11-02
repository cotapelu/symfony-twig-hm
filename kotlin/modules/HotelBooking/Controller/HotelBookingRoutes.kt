package hotelbooking

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

fun Application.configureHotelBookingRoutes(handler: HotelBookingHandler) {
	routing {
		route("/api/hotels") {
			get {
				handler.searchHotels(call)
			}

			get("/{id}") {
				handler.getHotel(call)
			}

			get("/{id}/room-types") {
				handler.getHotelRoomTypes(call)
			}

			get("/{id}/availability") {
				handler.checkAvailability(call)
			}

			get("/{id}/reviews") {
				handler.getHotelReviews(call)
			}

			post("/{id}/reviews") {
				requireAuth(call)
				handler.addReview(call)
			}
		}

		route("/api/bookings") {
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
        }
    }
}