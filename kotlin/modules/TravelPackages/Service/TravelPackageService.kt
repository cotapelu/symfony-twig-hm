package travelpackages

import java.time.LocalDate
import java.time.LocalDateTime
import java.util.UUID

class TravelPackageService(
    private val packageRepository: TravelPackageRepository,
    private val bookingRepository: PackageBookingRepository
) {

    fun searchPackages(request: PackageSearchRequest): List<TravelPackage> {
        var packages = packageRepository.findActive()

        if (request.destination != null) {
            packages = packages.filter { it.destination.contains(request.destination, ignoreCase = true) }
        }

        if (request.minDuration != null && request.maxDuration != null) {
            packages = packages.filter { it.duration in request.minDuration..request.maxDuration }
        }

        if (request.maxPrice != null) {
            packages = packages.filter { it.pricePerPerson <= request.maxPrice }
        }

        if (request.travelDate != null) {
            packages = packages.filter { it.availableDates.contains(request.travelDate) }
        }

        return when (request.sortBy) {
            PackageSortOption.PRICE -> packages.sortedBy { it.pricePerPerson }
            PackageSortOption.DURATION -> packages.sortedBy { it.duration }
            PackageSortOption.RATING -> packages.sortedByDescending { it.rating }
            PackageSortOption.POPULARITY -> packages.sortedByDescending { it.totalBookings }
        }
    }

    fun getPackage(packageId: String): TravelPackage? {
        return packageRepository.findById(packageId)
    }

    fun getFeaturedPackages(): List<TravelPackage> {
        return packageRepository.findActive()
            .sortedByDescending { it.rating }
            .take(6)
    }

    fun getDestinations(): List<String> {
        return packageRepository.findActive()
            .map { it.destination }
            .distinct()
    }

    fun createBooking(request: CreatePackageBookingRequest): PackageBooking = synchronized(this) {
        val travelPackage = packageRepository.findById(request.packageId)
            ?: throw IllegalArgumentException("Package not found")

        if (!travelPackage.availableDates.contains(request.travelDate)) {
            throw IllegalStateException("Selected date is not available for this package")
        }

        if (request.participants > travelPackage.maxPeople) {
            throw IllegalArgumentException("Number of participants exceeds package maximum")
        }

        val existingBookings = bookingRepository.findByPackageId(request.packageId)
            .filter { it.travelDate == request.travelDate && it.status != BookingStatus.CANCELLED }
        val totalBooked = existingBookings.sumOf { it.participants }

        if (totalBooked + request.participants > travelPackage.maxPeople) {
            throw IllegalStateException("Not enough spots available for the selected date")
        }

        val totalPrice = travelPackage.pricePerPerson * request.participants

        val booking = PackageBooking(
            id = UUID.randomUUID().toString(),
            userId = request.userId,
            packageId = request.packageId,
            travelDate = request.travelDate,
            participants = request.participants,
            totalPrice = totalPrice,
            contactInfo = request.contactInfo,
            specialRequests = request.specialRequests
        )

        return bookingRepository.save(booking)
    }

    fun getBooking(bookingId: String): PackageBooking? {
        return bookingRepository.findById(bookingId)
    }

    fun getUserBookings(userId: String): List<PackageBooking> {
        return bookingRepository.findByUserId(userId)
    }

    fun cancelBooking(bookingId: String): PackageBooking? {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.status == BookingStatus.CANCELLED || booking.status == BookingStatus.COMPLETED) {
            throw IllegalStateException("Booking cannot be cancelled")
        }

        return bookingRepository.save(booking.copy(status = BookingStatus.CANCELLED))
    }
}

data class PackageSearchRequest(
    val destination: String? = null,
    val minDuration: Int? = null,
    val maxDuration: Int? = null,
    val maxPrice: Double? = null,
    val travelDate: LocalDate? = null,
    val sortBy: PackageSortOption = PackageSortOption.RATING
)

enum class PackageSortOption {
    PRICE, DURATION, RATING, POPULARITY
}

data class CreatePackageBookingRequest(
    val userId: String,
    val packageId: String,
    val travelDate: LocalDate,
    val participants: Int,
    val contactInfo: PackageContactInfo,
    val specialRequests: String? = null
)

enum class BookingStatus {
    PENDING, CONFIRMED, COMPLETED, CANCELLED
}