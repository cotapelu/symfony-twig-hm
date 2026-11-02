package parking

import java.time.LocalDateTime
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap

data class ParkingLot(
    val id: String = UUID.randomUUID().toString(),
    val name: String,
    val address: String,
    val city: String,
    val latitude: Double,
    val longitude: Double,
    val totalSpots: Int,
    val hourlyRate: Double,
    val currency: String = "VND",
    val openingTime: String = "00:00",
    val closingTime: String = "23:59",
    val isCovered: Boolean = true,
    val hasEVCharging: Boolean = false,
    val hasCCTV: Boolean = true,
    val securityFeatures: List<String> = emptyList(),
    val rating: Double = 0.0,
    val reviewCount: Int = 0,
    val status: String = "active",
    val createdAt: LocalDateTime = LocalDateTime.now()
)

class ParkingLotRepository {
    private val lots = ConcurrentHashMap<String, ParkingLot>()

    fun save(lot: ParkingLot): ParkingLot {
        lots[lot.id] = lot
        return lot
    }

    fun findById(id: String): ParkingLot? {
        return lots[id]
    }

    fun findActive(): List<ParkingLot> {
        return lots.values.filter { it.status == "active" }
    }

    fun findByCity(city: String): List<ParkingLot> {
        return lots.values.filter { it.city.equals(city, ignoreCase = true) && it.status == "active" }
    }

    fun search(
        query: String? = null,
        city: String? = null,
        minPrice: Double? = null,
        maxPrice: Double? = null,
        hasEVCharging: Boolean? = null,
        hasCCTV: Boolean? = null
    ): List<ParkingLot> {
        var result = lots.values.filter { it.status == "active" }

        if (query != null) {
            val q = query.lowercase()
            result = result.filter { 
                it.name.lowercase().contains(q) || 
                it.address.lowercase().contains(q)
            }
        }

        if (city != null) {
            result = result.filter { it.city.equals(city, ignoreCase = true) }
        }

        if (minPrice != null) {
            result = result.filter { it.hourlyRate >= minPrice }
        }

        if (maxPrice != null) {
            result = result.filter { it.hourlyRate <= maxPrice }
        }

        if (hasEVCharging == true) {
            result = result.filter { it.hasEVCharging }
        }

        if (hasCCTV == true) {
            result = result.filter { it.hasCCTV }
        }

        return result.sortedByDescending { it.rating }
    }

    fun updateStatus(id: String, status: String): Boolean {
        val lot = lots[id] ?: return false
        lots[id] = lot.copy(status = status)
        return true
    }
}

data class ParkingSpot(
    val id: String = UUID.randomUUID().toString(),
    val lotId: String,
    val spotNumber: String,
    val spotType: String = "regular",
    val isAvailable: Boolean = true,
    val isEVCharging: Boolean = false,
    val isHandicap: Boolean = false,
    val floor: Int = 1,
    val section: String = "A"
)

class ParkingSpotRepository {
    private val spots = ConcurrentHashMap<String, ParkingSpot>()

    fun save(spot: ParkingSpot): ParkingSpot {
        spots[spot.id] = spot
        return spot
    }

    fun findById(id: String): ParkingSpot? {
        return spots[id]
    }

    fun findByLotId(lotId: String): List<ParkingSpot> {
        return spots.values.filter { it.lotId == lotId }
    }

    fun findAvailableByLotId(lotId: String): List<ParkingSpot> {
        return spots.values.filter { it.lotId == lotId && it.isAvailable }
    }

    fun findByLotAndType(lotId: String, type: String): List<ParkingSpot> {
        return spots.values.filter { it.lotId == lotId && it.spotType == type && it.isAvailable }
    }

    fun updateAvailability(id: String, isAvailable: Boolean): Boolean {
        val spot = spots[id] ?: return false
        spots[id] = spot.copy(isAvailable = isAvailable)
        return true
    }

    fun countAvailable(lotId: String): Int {
        return spots.values.count { it.lotId == lotId && it.isAvailable }
    }
}

data class ParkingReservation(
    val id: String = UUID.randomUUID().toString(),
    val lotId: String,
    val spotId: String,
    val userId: String,
    val startTime: LocalDateTime,
    val endTime: LocalDateTime,
    val totalAmount: Double,
    val currency: String = "VND",
    val status: String = "pending",
    val licensePlate: String = "",
    val vehicleType: String = "car",
    val specialRequests: String? = null,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

class ParkingReservationRepository {
    private val reservations = ConcurrentHashMap<String, ParkingReservation>()

    fun save(reservation: ParkingReservation): ParkingReservation {
        reservations[reservation.id] = reservation
        return reservation
    }

    fun findById(id: String): ParkingReservation? {
        return reservations[id]
    }

    fun findByUserId(userId: String): List<ParkingReservation> {
        return reservations.values.filter { it.userId == userId }
            .sortedByDescending { it.createdAt }
    }

    fun findByLotId(lotId: String): List<ParkingReservation> {
        return reservations.values.filter { it.lotId == lotId }
    }

    fun findBySpotAndDate(spotId: String, date: LocalDateTime): List<ParkingReservation> {
        val dateStart = date.toLocalDate().atStartOfDay()
        val dateEnd = dateStart.plusDays(1)
        return reservations.values.filter { 
            it.spotId == spotId && 
            it.startTime >= dateStart && 
            it.startTime < dateEnd &&
            it.status != "cancelled"
        }
    }

    fun updateStatus(id: String, status: String): Boolean {
        val reservation = reservations[id] ?: return false
        reservations[id] = reservation.copy(status = status)
        return true
    }

    fun findOverlappingReservations(spotId: String, startTime: LocalDateTime, endTime: LocalDateTime): List<ParkingReservation> {
        return reservations.values.filter { res ->
            res.spotId == spotId &&
            res.status != "cancelled" &&
            (startTime < res.endTime && endTime > res.startTime)
        }
    }
}

data class ParkingSession(
    val id: String = UUID.randomUUID().toString(),
    val lotId: String,
    val spotId: String,
    val userId: String,
    val entryTime: LocalDateTime,
    val exitTime: LocalDateTime? = null,
    val plannedExitTime: LocalDateTime? = null,
    val actualAmount: Double = 0.0,
    val currency: String = "VND",
    val status: String = "active",
    val licensePlate: String = "",
    val vehicleType: String = "car"
)

class ParkingSessionRepository {
    private val sessions = ConcurrentHashMap<String, ParkingSession>()

    fun save(session: ParkingSession): ParkingSession {
        sessions[session.id] = session
        return session
    }

    fun findById(id: String): ParkingSession? {
        return sessions[id]
    }

    fun findByUserId(userId: String): List<ParkingSession> {
        return sessions.values.filter { it.userId == userId }
            .sortedByDescending { it.entryTime }
    }

    fun findActiveByUserId(userId: String): ParkingSession? {
        return sessions.values.find { it.userId == userId && it.status == "active" }
    }

    fun findByLotId(lotId: String): List<ParkingSession> {
        return sessions.values.filter { it.lotId == lotId }
    }

    fun findActiveByLotId(lotId: String): List<ParkingSession> {
        return sessions.values.filter { it.lotId == lotId && it.status == "active" }
    }

    fun update(session: ParkingSession): ParkingSession {
        sessions[session.id] = session
        return session
    }

    fun endSession(id: String, exitTime: LocalDateTime, amount: Double): ParkingSession? {
        val session = sessions[id] ?: return null
        val ended = session.copy(
            exitTime = exitTime,
            actualAmount = amount,
            status = "completed"
        )
        sessions[id] = ended
        return ended
    }
}
