package carrental

import java.time.LocalDate
import java.time.LocalDateTime

data class Car(
    val id: String,
    val model: String,
    val brand: String,
    val year: Int,
    val type: CarType,
    val seats: Int,
    val transmission: String,
    val fuelType: String,
    val features: List<String>,
    val images: List<String>,
    val pricePerDay: Double,
    val location: CarLocation,
    val isAvailable: Boolean = true,
    val rating: Double = 0.0,
    val reviewCount: Int = 0
)

data class CarLocation(
    val city: String,
    val address: String,
    val pickupInstructions: String? = null
)

enum class CarType {
    ECONOMY, COMPACT, MIDSIZE, SUV, LUXURY, VAN, CONVERTIBLE
}

data class RentalCompany(
    val id: String,
    val name: String,
    val location: String,
    val rating: Double = 0.0,
    val reviewCount: Int = 0
)

data class CarBooking(
    val id: String,
    val userId: String,
    val carId: String,
    val companyId: String,
    val pickupLocation: String,
    val pickupDate: LocalDateTime,
    val dropoffLocation: String,
    val dropoffDate: LocalDateTime,
    val totalDays: Int,
    val totalPrice: Double,
    val status: BookingStatus = BookingStatus.PENDING,
    val createdAt: LocalDateTime = LocalDateTime.now()
)
