package restaurantbooking

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import com.auth0.jwt.interfaces.JWTPrincipal

class RestaurantBookingHandler(private val service: RestaurantBookingService) {

    private suspend fun requireReservationOwnership(call: ApplicationCall, reservationId: String): String? {
        val principal = call.principal<JWTPrincipal>()
            ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required")); return null }
        val userId = principal.subject
            ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject")); return null }

        val reservation = service.getReservation(reservationId)
            ?: run { call.respond(HttpStatusCode.NotFound, mapOf("error" to "Reservation not found")); return null }

        if (reservation.userId != userId) {
            call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Access denied: not your reservation"))
            return null
        }

        return userId
    }

    suspend fun searchRestaurants(call: ApplicationCall) {
        try {
            val query = call.request.queryParameters["query"]
            val cuisine = call.request.queryParameters["cuisine"]
            val city = call.request.queryParameters["city"]
            val minRating = call.request.queryParameters["minRating"]?.toDoubleOrNull()
            val maxPrice = call.request.queryParameters["maxPrice"]?.toIntOrNull()

            val request = RestaurantSearchRequest(
                query = query,
                cuisine = cuisine,
                city = city,
                minRating = minRating,
                maxPrice = maxPrice
            )

            val restaurants = service.searchRestaurants(request)
            call.respond(mapOf("restaurants" to restaurants))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun getRestaurant(call: ApplicationCall) {
        try {
            val restaurantId = call.parameters["id"] ?: throw IllegalArgumentException("Restaurant ID required")
            val restaurant = service.getRestaurant(restaurantId)
            
            if (restaurant != null) {
                call.respond(mapOf("restaurant" to restaurant))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Restaurant not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get restaurant")))
        }
    }

    suspend fun getAvailableTables(call: ApplicationCall) {
        try {
            val restaurantId = call.parameters["id"] ?: throw IllegalArgumentException("Restaurant ID required")
            val date = call.request.queryParameters["date"] ?: throw IllegalArgumentException("Date required")
            val time = call.request.queryParameters["time"] ?: throw IllegalArgumentException("Time required")
            val partySize = call.request.queryParameters["partySize"]?.toIntOrNull() ?: 2

            val tables = service.getAvailableTables(restaurantId, date, time, partySize)
            call.respond(mapOf("tables" to tables))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get tables")))
        }
    }

    suspend fun createReservation(call: ApplicationCall) {
        try {
            val request = call.receive<ReservationRequest>()
            val principal = call.principal<JWTPrincipal>()
            val userId = principal?.subject ?: "anonymous"
            
            val reservationRequest = request.copy(userId = userId)
            val reservation = service.createReservation(reservationRequest)
            call.respond(HttpStatusCode.Created, mapOf("reservation" to reservation))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Reservation creation failed")))
        }
    }

    suspend fun getReservation(call: ApplicationCall) {
        try {
            val reservationId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Reservation ID required")); return }
            val authUserId = requireReservationOwnership(call, reservationId)
            if (authUserId == null) return
            call.respond(mapOf("reservation" to service.getReservation(reservationId)!!))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reservation")))
        }
    }

    suspend fun getUserReservations(call: ApplicationCall) {
        try {
            val principal = call.principal<JWTPrincipal>()
                ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required")); return }
            val userId = principal.subject ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject")); return }
            val reservations = service.getUserReservations(userId)
            call.respond(mapOf("reservations" to reservations))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reservations")))
        }
    }

    suspend fun cancelReservation(call: ApplicationCall) {
        try {
            val reservationId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Reservation ID required")); return }
            val authUserId = requireReservationOwnership(call, reservationId)
            if (authUserId == null) return
            val reservation = service.cancelReservation(reservationId)
            if (reservation != null) {
                call.respond(mapOf("reservation" to reservation))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Reservation not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to cancel reservation")))
        }
    }

    suspend fun getFeaturedRestaurants(call: ApplicationCall) {
        try {
            val restaurants = service.getFeaturedRestaurants()
            call.respond(mapOf("restaurants" to restaurants))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get featured restaurants")))
        }
    }

    suspend fun getCuisines(call: ApplicationCall) {
        try {
            val cuisines = service.getCuisines()
            call.respond(mapOf("cuisines" to cuisines))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get cuisines")))
        }
    }

    suspend fun getRestaurantReviews(call: ApplicationCall) {
        try {
            val restaurantId = call.parameters["id"] ?: throw IllegalArgumentException("Restaurant ID required")
            val reviews = service.getRestaurantReviews(restaurantId)
            call.respond(mapOf("reviews" to reviews))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reviews")))
        }
    }
}