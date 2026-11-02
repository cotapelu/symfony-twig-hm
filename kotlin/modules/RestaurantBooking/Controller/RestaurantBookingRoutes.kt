package restaurantbooking

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

fun Application.configureRestaurantBookingRoutes(handler: RestaurantBookingHandler) {
	routing {
		route("/api/restaurants") {
			get {
				handler.searchRestaurants(call)
			}

			get("/featured") {
				handler.getFeaturedRestaurants(call)
			}

			get("/cuisines") {
				handler.getCuisines(call)
			}

			get("/{id}") {
				handler.getRestaurant(call)
			}

			get("/{id}/tables") {
				handler.getAvailableTables(call)
			}

			get("/{id}/reviews") {
				handler.getRestaurantReviews(call)
			}
		}

		route("/api/restaurant-reservations") {
			get("/my") {
				requireAuth(call)
				handler.getUserReservations(call)
			}

			post {
				requireAuth(call)
				handler.createReservation(call)
            }
            
            get("/{id}") {
                handler.getReservation(call)
            }
            
            post("/{id}/cancel") {
                handler.cancelReservation(call)
            }
        }
    }
}