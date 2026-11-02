package hotelbooking

import java.time.LocalDate
import java.time.LocalDateTime

data class Hotel(
    val id: String,
    val name: String,
    val description: String,
    val address: String,
    val city: String,
    val country: String,
    val latitude: Double,
    val longitude: Double,
    val starRating: Int,
    val amenities: List<String>,
    val policies: HotelPolicies,
    val images: List<String>,
    val contactInfo: HotelContactInfo,
    val isActive: Boolean = true,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

data class HotelPolicies(
    val checkInTime: String = "14:00",
    val checkOutTime: String = "12:00",
    val cancellationPolicy: String = "Free cancellation up to 24 hours before check-in",
    val childPolicy: String = "Children of all ages are welcome",
    val petPolicy: String = "Pets are not allowed",
    val smokingPolicy: String = "Smoking is not allowed"
)

data class HotelContactInfo(
    val phone: String,
    val email: String,
    val website: String?
)

data class RoomType(
    val id: String,
    val hotelId: String,
    val name: String,
    val description: String,
    val maxOccupancy: Int,
    val bedType: String,
    val size: Double,
    val amenities: List<String>,
    val images: List<String>,
    val basePrice: Double,
    val totalRooms: Int,
    val availableRooms: Int
)

data class Room(
    val id: String,
    val roomTypeId: String,
    val hotelId: String,
    val floor: Int,
    val roomNumber: String,
    val status: RoomStatus = RoomStatus.AVAILABLE
)

enum class RoomStatus {
    AVAILABLE, OCCUPIED, MAINTENANCE, BLOCKED
}

data class Booking(
    val id: String,
    val userId: String,
    val hotelId: String,
    val roomTypeId: String,
    val checkInDate: LocalDate,
    val checkOutDate: LocalDate,
    val guestCount: Int,
    val guestInfo: GuestInfo,
    val rooms: List<Room>,
    val totalPrice: Double,
    val discount: Double = 0.0,
    val finalPrice: Double,
    val paymentStatus: PaymentStatus = PaymentStatus.PENDING,
    val bookingStatus: BookingStatus = BookingStatus.PENDING,
    val specialRequests: String?,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

data class GuestInfo(
    val firstName: String,
    val lastName: String,
    val email: String,
    val phone: String,
    val country: String
)

enum class BookingStatus {
    PENDING, CONFIRMED, CANCELLED, COMPLETED, NO_SHOW
}

enum class PaymentStatus {
    PENDING, PAID, REFUNDED, FAILED
}

data class Review(
    val id: String,
    val userId: String,
    val hotelId: String,
    val bookingId: String,
    val rating: Int,
    val title: String,
    val content: String,
    val categories: Map<String, Int>,
    val images: List<String>,
    val isVerified: Boolean = false,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

data class SearchRequest(
    val city: String?,
    val country: String?,
    val checkIn: LocalDate,
    val checkOut: LocalDate,
    val guests: Int,
    val rooms: Int = 1,
    val minPrice: Double? = null,
    val maxPrice: Double? = null,
    val starRating: Int? = null,
    val amenities: List<String> = emptyList(),
    val sortBy: SortOption = SortOption.PRICE
)

enum class SortOption {
    PRICE, RATING, DISTANCE, POPULARITY
}

data class Availability(
    val roomTypeId: String,
    val date: LocalDate,
    val available: Int,
    val price: Double
)
