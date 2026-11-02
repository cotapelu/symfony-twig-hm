package restaurantbooking

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import java.time.LocalDate
import java.time.LocalTime

class RestaurantBookingHandler(private val service: RestaurantBookingService) {

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

    private fun validateRating(rating: Int): String? {
        if (rating < 1 || rating > 5) {
            return "Rating must be between 1 and 5"
        }
        return null
    }

    private fun validatePartySize(size: Int): String? {
        if (size < 1 || size > 20) {
            return "Party size must be between 1 and 20"
        }
        return null
    }

    private fun validatePrice(price: Int?): String? {
        if (price != null && price < 0) {
            return "Price must be positive"
        }
        return null
    }

    suspend fun searchRestaurants(call: ApplicationCall) {
        try {
            val query = call.request.queryParameters["query"]
            val cuisine = call.request.queryParameters["cuisine"]
            val city = call.request.queryParameters["city"]
            val minRating = call.request.queryParameters["minRating"]?.toDoubleOrNull()
            val maxPrice = call.request.queryParameters["maxPrice"]?.toIntOrNull()
            val features = call.request.queryParameters["features"]?.split(",")

            if (query != null && query.length > 200) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Query too long"))
                return
            }

            val priceError = validatePrice(maxPrice)
            if (priceError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to priceError))
                return
            }

            val request = RestaurantSearchRequest(
                query = query,
                cuisine = cuisine,
                city = city,
                minRating = minRating,
                maxPrice = maxPrice,
                features = features
            )

            val restaurants = service.searchRestaurants(request)
            call.respond(mapOf("restaurants" to restaurants, "count" to restaurants.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun getFeaturedRestaurants(call: ApplicationCall) {
        try {
            val restaurants = service.getFeaturedRestaurants()
            call.respond(mapOf("restaurants" to restaurants, "count" to restaurants.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get featured restaurants")))
        }
    }

    suspend fun getCuisines(call: ApplicationCall) {
        try {
            val cuisines = service.getCuisines()
            call.respond(mapOf("cuisines" to cuisines, "count" to cuisines.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get cuisines")))
        }
    }

    suspend fun getRestaurant(call: ApplicationCall) {
        try {
            val restaurantId = call.parameters["id"] ?: throw IllegalArgumentException("Restaurant ID required")
            
            if (restaurantId.length > 100) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Invalid restaurant ID"))
                return
            }
            
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

            if (restaurantId.length > 100) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Invalid restaurant ID"))
                return
            }

            val partyError = validatePartySize(partySize)
            if (partyError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to partyError))
                return
            }

            val tables = service.getAvailableTables(
                restaurantId,
                parseDate(date),
                LocalTime.parse(time),
                partySize
            )
            call.respond(mapOf("tables" to tables, "count" to tables.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get tables")))
        }
    }

    suspend fun getRestaurantReviews(call: ApplicationCall) {
        try {
            val restaurantId = call.parameters["id"] ?: throw IllegalArgumentException("Restaurant ID required")
            
            if (restaurantId.length > 100) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Invalid restaurant ID"))
                return
            }
            
            val reviews = service.getRestaurantReviews(restaurantId)
            call.respond(mapOf("reviews" to reviews, "count" to reviews.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reviews")))
        }
    }

    suspend fun createReservation(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val request = call.receive<ReservationRequest>()
            
            if (request.restaurantId.isBlank() || request.tableId.isBlank()) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Restaurant ID and table ID required"))
                return
            }

            val partyError = validatePartySize(request.partySize)
            if (partyError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to partyError))
                return
            }

            if (request.specialRequests != null && request.specialRequests.length > 500) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Special requests too long (max 500 chars)"))
                return
            }

            val reservationRequest = request.copy(userId = userId)
            val reservation = service.createReservation(reservationRequest)
            call.respond(HttpStatusCode.Created, mapOf("reservation" to reservation))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Reservation failed")))
        }
    }

    suspend fun getReservation(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val reservationId = call.parameters["id"] ?: throw IllegalArgumentException("Reservation ID required")
            val reservation = service.getReservation(reservationId)

            if (reservation != null) {
                if (reservation.userId != userId) {
                    call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Access denied"))
                    return
                }
                call.respond(mapOf("reservation" to reservation))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Reservation not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reservation")))
        }
    }

    suspend fun getUserReservations(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val reservations = service.getUserReservations(userId)
            call.respond(mapOf("reservations" to reservations, "count" to reservations.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reservations")))
        }
    }

    suspend fun cancelReservation(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val reservationId = call.parameters["id"] ?: throw IllegalArgumentException("Reservation ID required")
            
            val existingReservation = service.getReservation(reservationId)
            if (existingReservation == null) {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Reservation not found"))
                return
            }
            
            if (existingReservation.userId != userId) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Cannot cancel other user's reservation"))
                return
            }

            val reservation = service.cancelReservation(reservationId)

            if (reservation != null) {
                call.respond(mapOf("reservation" to reservation, "message" to "Reservation cancelled successfully"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot cancel reservation"))
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

            val request = call.receive<RestaurantReviewRequest>()
            
            if (request.restaurantId.isBlank()) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Restaurant ID required"))
                return
            }

            val ratingError = validateRating(request.rating)
            if (ratingError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to ratingError))
                return
            }

            if (request.comment.length > 1000) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Comment too long (max 1000 chars)"))
                return
            }

            val reviewRequest = request.copy(userId = userId)
            val review = service.addReview(
                reviewRequest.userId,
                reviewRequest.restaurantId,
                reviewRequest.rating,
                reviewRequest.comment
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

data class RestaurantSearchRequest(
    val query: String? = null,
    val cuisine: String? = null,
    val city: String? = null,
    val minRating: Double? = null,
    val maxPrice: Int? = null,
    val features: List<String>? = null,
    val atmosphere: String? = null,
    val limit: Int = 20
)

data class ReservationRequest(
    val userId: String,
    val restaurantId: String,
    val tableId: String,
    val date: LocalDate,
    val time: LocalTime,
    val partySize: Int,
    val guestInfo: GuestInfo? = null,
    val specialRequests: String? = null,
    val confirmationCode: String? = null
)

data class GuestInfo(
    val firstName: String,
    val lastName: String,
    val phone: String,
    val email: String? = null
)

data class RestaurantReviewRequest(
    val userId: String,
    val restaurantId: String,
    val rating: Int,
    val comment: String
)