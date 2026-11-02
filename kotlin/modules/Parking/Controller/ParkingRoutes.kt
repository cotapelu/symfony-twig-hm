package parking

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
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

fun Route.parkingRoutes() {
    val service = ParkingService(
        ParkingLotRepository(),
        ParkingSpotRepository(),
        ParkingReservationRepository()
    )
    
    // Parking lot routes
    get("/api/parking/search") {
        val request = call.receive<ParkingSearchRequest>()
        val lots = service.searchParkingLots(request)
        call.respond(lots)
    }
    
    get("/api/parking/{lotId}") {
        val lotId = call.parameters["lotId"] ?: throw IllegalArgumentException("Lot ID required")
        val lot = service.getParkingLot(lotId)
        if (lot != null) {
            call.respond(lot)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Parking lot not found")
        }
    }
    
    get("/api/parking/{lotId}/spots") {
        val lotId = call.parameters["lotId"] ?: throw IllegalArgumentException("Lot ID required")
        val spotType = call.parameters["type"]
        val spots = service.getAvailableSpots(lotId, spotType)
        call.respond(spots)
    }
    
    get("/api/parking/nearby") {
        val lat = call.parameters["latitude"]?.toDoubleOrNull() ?: throw IllegalArgumentException("Latitude required")
        val lon = call.parameters["longitude"]?.toDoubleOrNull() ?: throw IllegalArgumentException("Longitude required")
        val radius = call.parameters["radius"]?.toDoubleOrNull() ?: 1.0
        val lots = service.findNearbyLots(lat, lon, radius)
        call.respond(lots)
    }
    
// Reservation routes
	post("/api/parking/{lotId}/reserve") {
		requireAuth(call)
		val lotId = call.parameters["lotId"] ?: throw IllegalArgumentException("Lot ID required")
		val request = call.receive<ParkingReservationRequest>()
		val reservation = service.createReservation(lotId, request)
		call.respond(reservation)
	}

	get("/api/parking/reservations/{reservationId}") {
		requireAuth(call)
		val reservationId = call.parameters["reservationId"] ?: throw IllegalArgumentException("Reservation ID required")
		val reservation = service.getReservation(reservationId)
		if (reservation != null) {
			call.respond(reservation)
		} else {
			call.respond(io.ktor.http.HttpStatusCode.NotFound, "Reservation not found")
		}
	}

	get("/api/parking/reservations/user/{userId}") {
		requireAuth(call)
		val userId = call.parameters["userId"] ?: throw IllegalArgumentException("User ID required")
		val reservations = service.getUserReservations(userId)
		call.respond(reservations)
	}

	post("/api/parking/reservations/{reservationId}/cancel") {
		requireAuth(call)
		val reservationId = call.parameters["reservationId"] ?: throw IllegalArgumentException("Reservation ID required")
		val reservation = service.cancelReservation(reservationId)
		if (reservation != null) {
			call.respond(reservation)
		} else {
			call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot cancel reservation")
		}
	}

	// Session routes (for hourly parking)
	post("/api/parking/{spotId}/start") {
		requireAuth(call)
		val spotId = call.parameters["spotId"] ?: throw IllegalArgumentException("Spot ID required")
		val request = call.receive<ParkingSessionRequest>()
		val session = service.startSession(spotId, request)
		call.respond(session)
	}

	post("/api/parking/sessions/{sessionId}/end") {
		requireAuth(call)
		val sessionId = call.parameters["sessionId"] ?: throw IllegalArgumentException("Session ID required")
		val session = service.endSession(sessionId)
		if (session != null) {
			call.respond(session)
		} else {
			call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot end session")
		}
	}

	get("/api/parking/sessions/{sessionId}") {
		requireAuth(call)
		val sessionId = call.parameters["sessionId"] ?: throw IllegalArgumentException("Session ID required")
		val session = service.getSession(sessionId)
		if (session != null) {
			call.respond(session)
		} else {
			call.respond(io.ktor.http.HttpStatusCode.NotFound, "Session not found")
		}
	}
}

data class ParkingSearchRequest(
    val query: String? = null,
    val city: String? = null,
    val spotType: String? = null,
    val minPrice: Double? = null,
    val maxPrice: Double? = null,
    val hasEVCharging: Boolean? = null,
    val hasCCTV: Boolean? = null,
    val limit: Int = 20
)

data class ParkingReservationRequest(
    val userId: String,
    val spotId: String,
    val startTime: String,
    val endTime: String,
    val vehicleNumber: String
)

data class ParkingSessionRequest(
    val userId: String,
    val vehicleNumber: String
)
