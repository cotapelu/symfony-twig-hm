package travelpackages

import java.time.LocalDate
import java.time.LocalDateTime
import java.util.concurrent.ConcurrentHashMap

class TravelPackageRepository {
    private val packages = ConcurrentHashMap<String, TravelPackage>()

    fun findAll(): List<TravelPackage> = packages.values.toList()

    fun findById(id: String): TravelPackage? = packages[id]

    fun findActive(): List<TravelPackage> = packages.values.filter { it.isActive }

    fun findByDestination(destination: String): List<TravelPackage> =
        packages.values.filter { it.destination.contains(destination, ignoreCase = true) && it.isActive }

    fun findByDuration(minDays: Int, maxDays: Int): List<TravelPackage> =
        packages.values.filter { it.duration in minDays..maxDays && it.isActive }

    fun findByMaxPrice(maxPrice: Double): List<TravelPackage> =
        packages.values.filter { it.pricePerPerson <= maxPrice && it.isActive }

    fun save(travelPackage: TravelPackage): TravelPackage {
        packages[travelPackage.id] = travelPackage
        return travelPackage
    }

    fun seedSampleData() {
        if (packages.isNotEmpty()) return

        listOf(
            TravelPackage(
                id = "package_1",
                name = "Vietnam Heritage Tour",
                description = "Explore the rich cultural heritage of Vietnam from North to South",
                destination = "Vietnam",
                duration = 10,
                inclusions = listOf("Hotels", "Breakfast", "Tour Guide", "Transportation", "Entrance Fees"),
                exclusions = listOf("Flights", "Lunch", "Dinner", "Personal Expenses"),
                itinerary = listOf(
                    ItineraryDay(1, "Arrival in Hanoi", "Welcome to Vietnam! Airport transfer and hotel check-in", listOf("Dinner"), listOf("Airport Pickup")),
                    ItineraryDay(2, "Hanoi City Tour", "Visit Hoan Kiem Lake, Old Quarter, and Temple of Literature", listOf("Breakfast"), listOf("City Tour", "Cyclo Ride")),
                    ItineraryDay(3, "Halong Bay Cruise", "Overnight cruise in the stunning Halong Bay", listOf("Breakfast", "Lunch", "Dinner"), listOf("Cruise", "Kayaking"))
                ),
                pricePerPerson = 1299.0,
                maxPeople = 20,
                availableDates = listOf(LocalDate.now().plusDays(30), LocalDate.now().plusDays(60)),
                images = listOf("package1_1.jpg", "package1_2.jpg"),
                rating = 4.8,
                totalBookings = 156
            ),
            TravelPackage(
                id = "package_2",
                name = "Thailand Beach Escape",
                description = "Relax on the pristine beaches of Phuket and Krabi",
                destination = "Thailand",
                duration = 7,
                inclusions = listOf("Resort", "Breakfast", "Airport Transfers", "Island Tour"),
                exclusions = listOf("Flights", "Lunch", "Dinner", "Spa Services"),
                itinerary = listOf(
                    ItineraryDay(1, "Arrival in Phuket", "Airport pickup and resort check-in", listOf("Dinner"), listOf("Airport Transfer")),
                    ItineraryDay(2, "Phuket Exploration", "Visit Big Buddha and Old Phuket Town", listOf("Breakfast"), listOf("City Tour")),
                    ItineraryDay(3, "Phi Phi Islands", "Full day tour of the famous Phi Phi Islands", listOf("Breakfast", "Lunch"), listOf("Boat Tour", "Snorkeling"))
                ),
                pricePerPerson = 899.0,
                maxPeople = 15,
                availableDates = listOf(LocalDate.now().plusDays(15), LocalDate.now().plusDays(45)),
                images = listOf("package2_1.jpg", "package2_2.jpg"),
                rating = 4.6,
                totalBookings = 203
            ),
            TravelPackage(
                id = "package_3",
                name = "Japan Cherry Blossom",
                description = "Experience the magical cherry blossom season in Japan",
                destination = "Japan",
                duration = 12,
                inclusions = listOf("Hotels", "Breakfast", "JR Pass", "Tour Guide", "Some Meals"),
                exclusions = listOf("International Flights", "Personal Expenses", "Travel Insurance"),
                itinerary = listOf(
                    ItineraryDay(1, "Arrival in Tokyo", "Airport transfer and hotel check-in", listOf("Dinner"), listOf("Airport Pickup")),
                    ItineraryDay(2, "Tokyo Highlights", "Visit Senso-ji Temple, Tokyo Skytree, and Shibuya", listOf("Breakfast"), listOf("City Tour")),
                    ItineraryDay(3, "Mt. Fuji Day Trip", "Full day excursion to Mt. Fuji and Hakone", listOf("Breakfast", "Lunch"), listOf("Day Trip", "Cable Car"))
                ),
                pricePerPerson = 2499.0,
                maxPeople = 12,
                availableDates = listOf(LocalDate.now().plusDays(90), LocalDate.now().plusDays(120)),
                images = listOf("package3_1.jpg", "package3_2.jpg"),
                rating = 4.9,
                totalBookings = 89
            )
        ).forEach { save(it) }
    }
}

class PackageBookingRepository {
    private val bookings = ConcurrentHashMap<String, PackageBooking>()

    fun findById(id: String): PackageBooking? = bookings[id]

    fun findByUserId(userId: String): List<PackageBooking> =
        bookings.values.filter { it.userId == userId }.sortedByDescending { it.createdAt }

    fun findByPackageId(packageId: String): List<PackageBooking> =
        bookings.values.filter { it.packageId == packageId }

    fun save(booking: PackageBooking): PackageBooking {
        bookings[booking.id] = booking
        return booking
    }
}