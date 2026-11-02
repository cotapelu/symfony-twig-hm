package travelpackages

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

fun Application.configureTravelPackageRoutes(handler: TravelPackageHandler) {
	routing {
		route("/api/packages") {
			get {
				handler.searchPackages(call)
			}

			get("/featured") {
				handler.getFeaturedPackages(call)
			}

			get("/destinations") {
				handler.getDestinations(call)
			}

			get("/{id}") {
				handler.getPackage(call)
			}
		}

		route("/api/package-bookings") {
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

			post("/{id}/cancel") {
				requireAuth(call)
				handler.cancelBooking(call)
            }
        }
    }
}