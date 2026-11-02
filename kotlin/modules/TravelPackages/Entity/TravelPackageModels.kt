package travelpackages

import java.time.LocalDate
import java.time.LocalDateTime

data class TravelPackage(
    val id: String,
    val name: String,
    val description: String,
    val destination: String,
    val duration: Int,
    val inclusions: List<String>,
    val exclusions: List<String>,
    val itinerary: List<ItineraryDay>,
    val pricePerPerson: Double,
    val maxPeople: Int,
    val availableDates: List<LocalDate>,
    val images: List<String>,
    val rating: Double = 0.0,
    val totalBookings: Int = 0,
    val isActive: Boolean = true,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

data class ItineraryDay(
    val day: Int,
    val title: String,
    val description: String,
    val meals: List<String>,
    val activities: List<String>
)

data class PackageBooking(
    val id: String,
    val userId: String,
    val packageId: String,
    val travelDate: LocalDate,
    val participants: Int,
    val totalPrice: Double,
    val status: BookingStatus = BookingStatus.PENDING,
    val contactInfo: PackageContactInfo,
    val specialRequests: String? = null,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

data class PackageContactInfo(
    val email: String,
    val phone: String,
    val emergencyContact: String?
)
