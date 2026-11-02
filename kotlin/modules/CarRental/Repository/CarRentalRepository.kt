package carrental

interface CarRepository {
    fun findById(id: String): Car?
    fun findByStatus(status: String): List<Car>
    fun save(car: Car): Car
    fun updateRating(id: String, rating: Double, reviewCount: Int)
}

interface CarBookingRepository {
    fun findById(id: String): CarBooking?
    fun findByCarId(carId: String): List<CarBooking>
    fun findByCarIds(carIds: List<String>): List<CarBooking>  // Batch fetch for N+1 prevention
    fun findByUserIdOrderByCreatedAtDesc(userId: String): List<CarBooking>
    fun save(booking: CarBooking): CarBooking
}

interface CarReviewRepository {
    fun findById(id: String): CarReview?
    fun findByCarId(carId: String): List<CarReview>
    fun findByCarIdOrderByCreatedAtDesc(carId: String): List<CarReview>
    fun save(review: CarReview): CarReview
}

enum class CarBookingStatus {
    PENDING, CONFIRMED, IN_PROGRESS, COMPLETED, CANCELLED
}

data class CarReview(
    val id: String,
    val carId: String,
    val bookingId: String,
    val userId: String,
    val rating: Int,
    val comment: String,
    val createdAt: LocalDateTime = LocalDateTime.now()
)
