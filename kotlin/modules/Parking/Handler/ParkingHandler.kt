package parking

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import com.auth0.jwt.interfaces.JWTPrincipal

class ParkingHandler(private val parkingService: ParkingService) {

    private fun getUserId(call: ApplicationCall): String? {
        val principal = call.principal<JWTPrincipal>() ?: return null
        return principal.subject
    }

    suspend fun searchSpots(call: ApplicationCall) {
        try {
            val latitude = call.parameters["lat"]?.toDoubleOrNull()
                ?: call.request.queryParams["lat"]?.toDoubleOrNull()
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Latitude required"))
            val longitude = call.parameters["lng"]?.toDoubleOrNull()
                ?: call.request.queryParams["lng"]?.toDoubleOrNull()
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Longitude required"))
            val radius = call.request.queryParams["radius"]?.toDoubleOrNull() ?: 5.0
            val limit = call.request.queryParams["limit"]?.toIntOrNull() ?: 20

            val spots = parkingService.searchNearbySpots(latitude, longitude, radius, limit)

            call.respond(mapOf(
                "spots" to spots,
                "count" to spots.size,
                "center" to mapOf("lat" to latitude, "lng" to longitude)
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun getSpotDetails(call: ApplicationCall) {
        try {
            val spotId = call.parameters["id"]?.toLongOrNull()
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Spot ID required"))

            val spot = parkingService.getSpotById(spotId)
                ?: return call.respond(HttpStatusCode.NotFound, mapOf("error" to "Spot not found"))

            call.respond(mapOf("spot" to spot))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get spot")))
        }
    }

    suspend fun createReservation(call: ApplicationCall) {
        try {
            val request = call.receive<ParkingReservationRequest>()
            val userId = getUserId(call)
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))

            if (request.startTime.isBlank() || request.endTime.isBlank()) {
                return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Start time and end time required"))
            }

            val reservation = parkingService.createReservation(
                spotId = request.spotId,
                userId = userId,
                startTime = request.startTime,
                endTime = request.endTime,
                licensePlate = request.licensePlate
            )

            call.respond(HttpStatusCode.Created, mapOf(
                "reservation" to reservation,
                "status" to "success",
                "message" to "Reservation created successfully"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to create reservation")))
        }
    }

    suspend fun startSession(call: ApplicationCall) {
        try {
            val reservationId = call.parameters["id"]?.toLongOrNull()
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Reservation ID required"))
            val licensePlate = call.receive<LicensePlateRequest>().licensePlate

            val session = parkingService.startSession(reservationId, licensePlate)
                ?: return call.respond(HttpStatusCode.NotFound, mapOf("error" to "Reservation not found"))

            call.respond(HttpStatusCode.Created, mapOf(
                "session" to session,
                "status" to "active",
                "message" to "Parking session started"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to start session")))
        }
    }

    suspend fun endSession(call: ApplicationCall) {
        try {
            val sessionId = call.parameters["id"]?.toLongOrNull()
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Session ID required"))

            val session = parkingService.endSession(sessionId)
                ?: return call.respond(HttpStatusCode.NotFound, mapOf("error" to "Session not found"))

            call.respond(mapOf(
                "session" to session,
                "status" to "completed",
                "message" to "Parking session ended successfully",
                "totalAmount" to session.actualAmount
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to end session")))
        }
    }

    suspend fun getUserReservations(call: ApplicationCall) {
        try {
            val userId = getUserId(call)
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
            val status = call.request.queryParams["status"]

            val reservations = parkingService.getUserReservations(userId, status)

            call.respond(mapOf(
                "reservations" to reservations,
                "count" to reservations.size
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reservations")))
        }
    }

    suspend fun getUserSessions(call: ApplicationCall) {
        try {
            val userId = getUserId(call)
                ?: return call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))

            val sessions = parkingService.getUserSessions(userId)

            call.respond(mapOf(
                "sessions" to sessions,
                "count" to sessions.size,
                "active" to sessions.count { it.status == "active" }
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get sessions")))
        }
    }

    suspend fun cancelReservation(call: ApplicationCall) {
        try {
            val reservationId = call.parameters["id"]?.toLongOrNull()
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Reservation ID required"))

            val cancelled = parkingService.cancelReservation(reservationId)
            if (!cancelled) {
                return call.respond(HttpStatusCode.NotFound, mapOf("error" to "Reservation not found or cannot be cancelled"))
            }

            call.respond(mapOf(
                "status" to "cancelled",
                "message" to "Reservation cancelled successfully"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to cancel reservation")))
        }
    }

    suspend fun estimateCost(call: ApplicationCall) {
        try {
            val request = call.receive<CostEstimateRequest>()

            if (request.spotId <= 0 || request.startTime.isBlank() || request.endTime.isBlank()) {
                return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Spot ID, start time, and end time required"))
            }

            val estimate = parkingService.estimateCost(request.spotId, request.startTime, request.endTime)

            call.respond(mapOf(
                "estimatedCost" to estimate,
                "currency" to "VND",
                "message" to "This is an estimate, actual cost may vary"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to estimate cost")))
        }
    }

    suspend fun getAvailableSpots(call: ApplicationCall) {
        try {
            val spotId = call.parameters["id"]?.toLongOrNull()
                ?: return call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Spot ID required"))
            val date = call.request.queryParams["date"] ?: ""

            val available = parkingService.getAvailableSpots(spotId, date)
                ?: return call.respond(HttpStatusCode.NotFound, mapOf("error" to "Spot not found"))

            call.respond(mapOf(
                "spotId" to spotId,
                "totalSpots" to available.totalSpots,
                "availableSpots" to available.availableSpots,
                "availability" to if (available.availableSpots > 10) "high" else if (available.availableSpots > 0) "low" else "none"
            ))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get availability")))
        }
    }
}

data class ParkingReservationRequest(
    val spotId: Long,
    val userId: String? = null,
    val startTime: String,
    val endTime: String,
    val licensePlate: String = ""
)

data class LicensePlateRequest(
    val licensePlate: String
)

data class CostEstimateRequest(
    val spotId: Long,
    val startTime: String,
    val endTime: String
)