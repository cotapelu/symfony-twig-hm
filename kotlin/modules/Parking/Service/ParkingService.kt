package parking

import java.time.LocalDateTime
import java.time.temporal.ChronoUnit

class ParkingService(
    private val lotRepository: ParkingLotRepository,
    private val spotRepository: ParkingSpotRepository,
    private val reservationRepository: ParkingReservationRepository,
    private val sessionRepository: ParkingSessionRepository
) {

    fun searchParkingLots(request: ParkingSearchRequest): List<ParkingLot> {
        return lotRepository.search(
            query = request.query,
            city = request.city,
            minPrice = request.minPrice,
            maxPrice = request.maxPrice,
            hasEVCharging = request.hasEVCharging,
            hasCCTV = request.hasCCTV
        ).take(request.limit)
    }

    fun getParkingLot(lotId: String): ParkingLot? {
        return lotRepository.findById(lotId)
    }

    fun getAvailableSpots(lotId: String, spotType: String?): List<ParkingSpot> {
        return if (spotType != null) {
            spotRepository.findByLotAndType(lotId, spotType)
        } else {
            spotRepository.findAvailableByLotId(lotId)
        }
    }

    fun findNearbyLots(latitude: Double, longitude: Double, radiusKm: Double): List<ParkingLot> {
        val lots = lotRepository.findActive()
        return lots.filter { lot ->
            val distance = calculateDistance(latitude, longitude, lot.latitude, lot.longitude)
            distance <= radiusKm
        }.sortedBy { lot ->
            calculateDistance(latitude, longitude, lot.latitude, lot.longitude)
        }
    }

    private fun calculateDistance(lat1: Double, lon1: Double, lat2: Double, lon2: Double): Double {
        val r = 6371.0
        val dLat = Math.toRadians(lat2 - lat1)
        val dLon = Math.toRadians(lon2 - lon1)
        val a = kotlin.math.sin(dLat / 2) * kotlin.math.sin(dLat / 2) +
                kotlin.math.cos(Math.toRadians(lat1)) * kotlin.math.cos(Math.toRadians(lat2)) *
                kotlin.math.sin(dLon / 2) * kotlin.math.sin(dLon / 2)
        val c = 2 * kotlin.math.atan2(kotlin.math.sqrt(a), kotlin.math.sqrt(1 - a))
        return r * c
    }

    fun createReservation(lotId: String, request: ParkingReservationRequest): ParkingReservation {
        val lot = lotRepository.findById(lotId)
            ?: throw IllegalArgumentException("Parking lot not found")

        val spots = spotRepository.findAvailableByLotId(lotId)
        if (spots.isEmpty()) {
            throw IllegalStateException("No available spots in this parking lot")
        }

        val spot = spots.first()
        val startTime = LocalDateTime.parse(request.startTime)
        val endTime = LocalDateTime.parse(request.endTime)

        if (endTime.isBefore(startTime)) {
            throw IllegalArgumentException("End time must be after start time")
        }

        val hours = ChronoUnit.HOURS.between(startTime, endTime)
        if (hours < 1) {
            throw IllegalArgumentException("Minimum reservation is 1 hour")
        }

        val overlapping = reservationRepository.findOverlappingReservations(spot.id, startTime, endTime)
        if (overlapping.isNotEmpty()) {
            throw IllegalStateException("Selected time slot is not available")
        }

        val totalAmount = lot.hourlyRate * hours

        val reservation = ParkingReservation(
            lotId = lotId,
            spotId = spot.id,
            userId = request.userId,
            startTime = startTime,
            endTime = endTime,
            totalAmount = totalAmount,
            licensePlate = request.vehicleNumber,
            vehicleType = request.vehicleType ?: "car"
        )

        spotRepository.updateAvailability(spot.id, false)
        return reservationRepository.save(reservation)
    }

    fun getReservation(reservationId: String): ParkingReservation? {
        return reservationRepository.findById(reservationId)
    }

    fun getUserReservations(userId: String): List<ParkingReservation> {
        return reservationRepository.findByUserId(userId)
    }

    fun cancelReservation(reservationId: String): ParkingReservation? {
        val reservation = reservationRepository.findById(reservationId)
            ?: throw IllegalArgumentException("Reservation not found")

        if (reservation.status == "cancelled" || reservation.status == "completed") {
            throw IllegalStateException("Reservation cannot be cancelled")
        }

        val updated = reservation.copy(status = "cancelled")
        reservationRepository.updateStatus(reservationId, "cancelled")
        spotRepository.updateAvailability(reservation.spotId, true)

        return updated
    }

    fun startSession(spotId: String, request: ParkingSessionRequest): ParkingSession {
        val existingSession = sessionRepository.findActiveByUserId(request.userId)
        if (existingSession != null) {
            throw IllegalStateException("You already have an active parking session")
        }

        val spot = spotRepository.findById(spotId)
            ?: throw IllegalArgumentException("Parking spot not found")

        if (!spot.isAvailable) {
            throw IllegalStateException("Parking spot is not available")
        }

        val lot = lotRepository.findById(spot.lotId)
            ?: throw IllegalArgumentException("Parking lot not found")

        spotRepository.updateAvailability(spotId, false)

        val session = ParkingSession(
            lotId = spot.lotId,
            spotId = spotId,
            userId = request.userId,
            entryTime = LocalDateTime.now(),
            licensePlate = request.vehicleNumber,
            vehicleType = request.vehicleType ?: "car"
        )

        return sessionRepository.save(session)
    }

    fun endSession(sessionId: String): ParkingSession? {
        val session = sessionRepository.findById(sessionId)
            ?: throw IllegalArgumentException("Session not found")

        if (session.status != "active") {
            throw IllegalStateException("Session is not active")
        }

        val exitTime = LocalDateTime.now()
        val hours = ChronoUnit.MINUTES.between(session.entryTime, exitTime).toDouble() / 60.0
        val roundedHours = kotlin.math.ceil(hours * 2) / 2
        val lot = lotRepository.findById(session.lotId)
            ?: throw IllegalArgumentException("Parking lot not found")
        val amount = lot.hourlyRate * roundedHours

        spotRepository.updateAvailability(session.spotId, true)
        return sessionRepository.endSession(sessionId, exitTime, amount)
    }

    fun getSession(sessionId: String): ParkingSession? {
        return sessionRepository.findById(sessionId)
    }

    fun getUserActiveSession(userId: String): ParkingSession? {
        return sessionRepository.findActiveByUserId(userId)
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
    val startTime: String,
    val endTime: String,
    val vehicleNumber: String,
    val vehicleType: String? = null
)

data class ParkingSessionRequest(
    val userId: String,
    val vehicleNumber: String,
    val vehicleType: String? = null
)
