package restaurantbooking

import java.time.LocalDate
import java.time.LocalDateTime
import java.time.LocalTime

data class Restaurant(
    val id: String,
    val name: String,
    val cuisine: String,
    val address: String,
    val city: String,
    val priceRange: Int,
    val ambiance: String,
    val features: List<String>,
    val images: List<String>,
    val rating: Double = 0.0,
    val reviewCount: Int = 0,
    val isActive: Boolean = true
)

data class Table(
    val id: String,
    val restaurantId: String,
    val capacity: Int,
    val section: String,
    val isAvailable: Boolean = true
)

data class RestaurantTimeSlot(
    val id: String,
    val restaurantId: String,
    val date: LocalDate,
    val time: LocalTime,
    val availableTables: Int
)

data class RestaurantBooking(
    val id: String,
    val userId: String,
    val restaurantId: String,
    val date: LocalDate,
    val time: LocalTime,
    val partySize: Int,
    val tableId: String,
    val specialRequests: String? = null,
    val status: BookingStatus = BookingStatus.PENDING,
    val confirmationCode: String,
    val createdAt: LocalDateTime = LocalDateTime.now()
)
