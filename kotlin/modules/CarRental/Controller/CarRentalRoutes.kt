package carrental

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

fun Application.configureCarRentalRoutes(handler: CarRentalHandler) {
	routing {
		route("/api/cars") {
			get {
				handler.searchCars(call)
			}

			get("/featured") {
				handler.getFeaturedCars(call)
			}

			get("/{id}") {
				handler.getCar(call)
			}

			get("/{id}/availability") {
				handler.checkAvailability(call)
			}
		}

		route("/api/car-bookings") {
			get("/my") {
				requireAuth(call)
				handler.getUserBookings(call)
			}

			post {
				requireAuth(call)
				handler.createBooking(call)
			}

			get("/{id}") {
				requireAuth(call)
				handler.getBooking(call)
			}

			post("/{id}/confirm") {
				requireAuth(call)
				handler.confirmBooking(call)
			}

			post("/{id}/cancel") {
				requireAuth(call)
				handler.cancelBooking(call)
			}
		}
	}
}