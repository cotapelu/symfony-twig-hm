package restaurantbooking

import java.time.LocalDate
import java.time.LocalDateTime
import java.time.LocalTime
import java.util.UUID

class RestaurantBookingService(
    private val restaurantRepository: RestaurantRepository,
    private val tableRepository: TableRepository,
    private val reservationRepository: ReservationRepository,
    private val reviewRepository: ReviewRepository
) {

    fun searchRestaurants(request: RestaurantSearchRequest): List<Restaurant> {
        var restaurants = restaurantRepository.findAll()
            .filter { it.isActive }
            .toMutableList()

        // Apply filters
        if (request.query != null) {
            val query = request.query.lowercase()
            restaurants = restaurants.filter {
                it.name.lowercase().contains(query) ||
                it.cuisine.lowercase().contains(query) ||
                it.address.lowercase().contains(query)
            }.toMutableList()
        }

        if (request.cuisine != null) {
            restaurants = restaurants.filter {
                it.cuisine.lowercase() == request.cuisine.lowercase()
            }.toMutableList()
        }

        if (request.city != null) {
            restaurants = restaurants.filter {
                it.city.lowercase() == request.city.lowercase()
            }.toMutableList()
        }

        if (request.district != null) {
            restaurants = restaurants.filter {
                it.address.lowercase().contains(request.district.lowercase())
            }.toMutableList()
        }

        if (request.minRating != null) {
            restaurants = restaurants.filter {
                it.rating >= request.minRating
            }.toMutableList()
        }

        if (request.maxPrice != null) {
            restaurants = restaurants.filter {
                it.priceRange <= request.maxPrice
            }.toMutableList()
        }

        if (request.features.isNotEmpty()) {
            restaurants = restaurants.filter { restaurant ->
                request.features.all { feature ->
                    restaurant.features.any { it.lowercase() == feature.lowercase() }
                }
            }.toMutableList()
        }

        return restaurants.take(request.limit)
    }

    fun getRestaurant(restaurantId: String): Restaurant? {
        return restaurantRepository.findById(restaurantId)
    }

    fun getRestaurantMenu(restaurantId: String): List<MenuItem> {
        // In a real implementation, this would fetch from a menu repository
        return listOf(
            MenuItem(
                id = UUID.randomUUID().toString(),
                restaurantId = restaurantId,
                name = "Signature Dish",
                description = "Chef's special",
                price = 250000.0,
                category = "Main Course"
            )
        )
    }

    fun getAvailableTables(
        restaurantId: String,
        dateStr: String,
        timeStr: String,
        partySize: Int
    ): List<Table> {
        val date = LocalDate.parse(dateStr)
        val time = LocalTime.parse(timeStr)
        
        // Get all tables for the restaurant
        val allTables = tableRepository.findByRestaurantId(restaurantId)
        
        // Get existing reservations for the date/time
        val existingReservations = reservationRepository.findByRestaurantId(restaurantId)
            .filter { reservation ->
                reservation.date == date &&
                reservation.time.hour == time.hour &&
                reservation.status != BookingStatus.CANCELLED
            }
        
        // Find available tables
        val reservedTableIds = existingReservations.map { it.tableId }.toSet()
        
        return allTables.filter { table ->
            table.id !in reservedTableIds &&
            table.capacity >= partySize &&
            table.isAvailable
        }
    }

    fun getRestaurantReviews(restaurantId: String): List<RestaurantReview> {
        return reviewRepository.findByRestaurantIdOrderByCreatedAtDesc(restaurantId)
    }

    fun createReservation(request: ReservationRequest): RestaurantBooking {
        // Validate restaurant exists
        val restaurant = restaurantRepository.findById(request.restaurantId)
            ?: throw IllegalArgumentException("Restaurant not found")
        
        if (!restaurant.isActive) {
            throw IllegalStateException("Restaurant is not active")
        }
        
        // Parse date and time
        val date = LocalDate.parse(request.date)
        val time = LocalTime.parse(request.time)
        
        // Check if date is in the future
        if (date.isBefore(LocalDate.now())) {
            throw IllegalArgumentException("Cannot book for past dates")
        }
        
        // Find available table if not specified
        val tableId = request.tableId ?: run {
            val availableTables = getAvailableTables(
                request.restaurantId,
                request.date,
                request.time,
                request.partySize
            )
            if (availableTables.isEmpty()) {
                throw IllegalStateException("No tables available for the selected date and time")
            }
            availableTables.first().id
        }
        
        // Verify table exists and is available
        val table = tableRepository.findById(tableId)
            ?: throw IllegalArgumentException("Table not found")
        
        if (table.capacity < request.partySize) {
            throw IllegalArgumentException("Table capacity is insufficient for party size")
        }
        
        // Create booking
        val booking = RestaurantBooking(
            id = UUID.randomUUID().toString(),
            userId = request.userId,
            restaurantId = request.restaurantId,
            date = date,
            time = time,
            partySize = request.partySize,
            tableId = tableId,
            specialRequests = request.specialRequests,
            status = BookingStatus.PENDING,
            confirmationCode = generateConfirmationCode(),
            createdAt = LocalDateTime.now()
        )
        
        return reservationRepository.save(booking)
    }

    fun getReservation(reservationId: String): RestaurantBooking? {
        return reservationRepository.findById(reservationId)
    }

    fun getUserReservations(userId: String): List<RestaurantBooking> {
        return reservationRepository.findByUserIdOrderByCreatedAtDesc(userId)
    }

    fun cancelReservation(reservationId: String): RestaurantBooking? {
        val reservation = reservationRepository.findById(reservationId)
            ?: throw IllegalArgumentException("Reservation not found")
        
        if (reservation.status == BookingStatus.CANCELLED ||
            reservation.status == BookingStatus.COMPLETED) {
            throw IllegalStateException("Reservation cannot be cancelled")
        }
        
        // Check if cancellation is within allowed time (e.g., 2 hours before)
        val reservationDateTime = LocalDateTime.of(reservation.date, reservation.time)
        if (LocalDateTime.now().plusHours(2).isAfter(reservationDateTime)) {
            throw IllegalStateException("Cannot cancel within 2 hours of reservation time")
        }
        
        return reservationRepository.save(
            reservation.copy(status = BookingStatus.CANCELLED)
        )
    }

    fun modifyReservation(
        reservationId: String,
        request: ModifyReservationRequest
    ): RestaurantBooking? {
        val reservation = reservationRepository.findById(reservationId)
            ?: throw IllegalArgumentException("Reservation not found")
        
        if (reservation.status == BookingStatus.CANCELLED ||
            reservation.status == BookingStatus.COMPLETED) {
            throw IllegalStateException("Cannot modify this reservation")
        }
        
        var updatedReservation = reservation
        
        // Update date if provided
        if (request.date != null) {
            val newDate = LocalDate.parse(request.date)
            if (newDate.isBefore(LocalDate.now())) {
                throw IllegalArgumentException("Cannot book for past dates")
            }
            updatedReservation = updatedReservation.copy(date = newDate)
        }
        
        // Update time if provided
        if (request.time != null) {
            val newTime = LocalTime.parse(request.time)
            updatedReservation = updatedReservation.copy(time = newTime)
        }
        
        // Update party size if provided
        if (request.partySize != null) {
            if (request.partySize > reservation.partySize) {
                // Need to find a larger table
                val availableTables = getAvailableTables(
                    reservation.restaurantId,
                    updatedReservation.date.toString(),
                    updatedReservation.time.toString(),
                    request.partySize
                )
                if (availableTables.isEmpty()) {
                    throw IllegalStateException("No tables available for the new party size")
                }
                updatedReservation = updatedReservation.copy(
                    partySize = request.partySize,
                    tableId = availableTables.first().id
                )
            } else {
                updatedReservation = updatedReservation.copy(partySize = request.partySize)
            }
        }
        
        // Update table if provided
        if (request.tableId != null) {
            updatedReservation = updatedReservation.copy(tableId = request.tableId)
        }
        
        return reservationRepository.save(updatedReservation)
    }

    fun confirmReservation(reservationId: String): RestaurantBooking? {
        val reservation = reservationRepository.findById(reservationId)
            ?: throw IllegalArgumentException("Reservation not found")
        
        if (reservation.status != BookingStatus.PENDING) {
            throw IllegalStateException("Only pending reservations can be confirmed")
        }
        
        return reservationRepository.save(
            reservation.copy(status = BookingStatus.CONFIRMED)
        )
    }

    fun completeReservation(reservationId: String): RestaurantBooking? {
        val reservation = reservationRepository.findById(reservationId)
            ?: throw IllegalArgumentException("Reservation not found")
        
        if (reservation.status != BookingStatus.CONFIRMED) {
            throw IllegalStateException("Only confirmed reservations can be completed")
        }
        
        return reservationRepository.save(
            reservation.copy(status = BookingStatus.COMPLETED)
        )
    }

    fun addReview(
        userId: String,
        reservationId: String,
        rating: Int,
        comment: String
    ): RestaurantReview {
        // Validate reservation exists and is completed
        val reservation = reservationRepository.findById(reservationId)
            ?: throw IllegalArgumentException("Reservation not found")
        
        if (reservation.status != BookingStatus.COMPLETED) {
            throw IllegalStateException("Can only review completed reservations")
        }
        
        if (reservation.userId != userId) {
            throw IllegalStateException("Can only review your own reservations")
        }
        
        // Check if already reviewed
        val existingReview = reviewRepository.findByReservationId(reservationId)
        if (existingReview != null) {
            throw IllegalStateException("Reservation already reviewed")
        }
        
        val review = RestaurantReview(
            id = UUID.randomUUID().toString(),
            userId = userId,
            restaurantId = reservation.restaurantId,
            reservationId = reservationId,
            rating = rating,
            comment = comment,
            createdAt = LocalDateTime.now()
        )
        
        val savedReview = reviewRepository.save(review)
        
        // Update restaurant rating
        updateRestaurantRating(reservation.restaurantId)
        
        return savedReview
    }

    private fun updateRestaurantRating(restaurantId: String) {
        val reviews = reviewRepository.findByRestaurantIdOrderByCreatedAtDesc(restaurantId)
        if (reviews.isNotEmpty()) {
            val avgRating = reviews.map { it.rating }.average()
            restaurantRepository.updateRating(restaurantId, avgRating, reviews.size)
        }
    }

    private fun generateConfirmationCode(): String {
        val chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
        return (1..6)
            .map { chars.random() }
            .joinToString("")
    }

    fun getFeaturedRestaurants(): List<Restaurant> {
        return restaurantRepository.findAll()
            .filter { it.isActive }
            .sortedByDescending { it.rating }
            .take(10)
    }

    fun getCuisines(): List<String> {
        return restaurantRepository.findAll()
            .map { it.cuisine }
            .distinct()
            .sorted()
    }

    fun getCities(): List<String> {
        return restaurantRepository.findAll()
            .map { it.city }
            .distinct()
            .sorted()
    }
}

// Supporting classes
data class MenuItem(
    val id: String,
    val restaurantId: String,
    val name: String,
    val description: String,
    val price: Double,
    val category: String,
    val isAvailable: Boolean = true
)

data class RestaurantReview(
    val id: String,
    val userId: String,
    val restaurantId: String,
    val reservationId: String,
    val rating: Int,
    val comment: String,
    val createdAt: LocalDateTime
)

enum class BookingStatus {
    PENDING, CONFIRMED, COMPLETED, CANCELLED, NO_SHOW
}

// Repository interfaces (would be implemented separately)
interface RestaurantRepository {
    fun findById(id: String): Restaurant?
    fun findAll(): List<Restaurant>
    fun save(restaurant: Restaurant): Restaurant
    fun updateRating(id: String, rating: Double, reviewCount: Int)
}

interface TableRepository {
    fun findById(id: String): Table?
    fun findByRestaurantId(restaurantId: String): List<Table>
    fun save(table: Table): Table
}

interface ReservationRepository {
    fun findById(id: String): RestaurantBooking?
    fun findByRestaurantId(restaurantId: String): List<RestaurantBooking>
    fun findByUserIdOrderByCreatedAtDesc(userId: String): List<RestaurantBooking>
    fun save(booking: RestaurantBooking): RestaurantBooking
}

interface ReviewRepository {
    fun findById(id: String): RestaurantReview?
    fun findByRestaurantIdOrderByCreatedAtDesc(restaurantId: String): List<RestaurantReview>
    fun findByReservationId(reservationId: String): RestaurantReview?
    fun save(review: RestaurantReview): RestaurantReview
}
