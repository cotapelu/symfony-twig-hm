package parking

data class ParkingSpot(
    val id: Long = 0,
    val name: String,
    val address: String,
    val latitude: Double,
    val longitude: Double,
    val totalSpots: Int,
    val availableSpots: Int,
    val hourlyRate: Double,
    val currency: String = "VND",
    val openingTime: String = "00:00",
    val closingTime: String = "23:59",
    val isCovered: Boolean = true,
    val hasEVCharging: Boolean = false,
    val securityFeatures: List<String> = emptyList(),
    val rating: Double = 0.0,
    val status: String = "available",
    val createdAt: String = ""
)

data class ParkingReservation(
    val id: Long = 0,
    val spotId: Long,
    val userId: String,
    val startTime: String,
    val endTime: String,
    val totalAmount: Double,
    val status: String = "pending",
    val licensePlate: String = "",
    val createdAt: String = ""
)

data class ParkingSession(
    val id: Long = 0,
    val spotId: Long,
    val userId: String,
    val entryTime: String,
    val exitTime: String? = null,
    val actualAmount: Double = 0.0,
    val status: String = "active",
    val licensePlate: String = "",
    val createdAt: String = ""
)
