package carrental

import java.time.LocalDateTime
import java.util.concurrent.ConcurrentHashMap

class CarRepositoryImpl : CarRepository {
    private val cars = ConcurrentHashMap<String, Car>()

    override fun findById(id: String): Car? = cars[id]

    override fun findByStatus(status: String): List<Car> = 
        cars.values.filter { it.isAvailable }

    override fun save(car: Car): Car {
        cars[car.id] = car
        return car
    }

    override fun updateRating(id: String, rating: Double, reviewCount: Int) {
        cars[id]?.let { car ->
            cars[id] = car.copy(rating = rating, reviewCount = reviewCount)
        }
    }

    fun seedSampleData() {
        if (cars.isNotEmpty()) return

        listOf(
            Car(
                id = "car_1",
                model = "Vios",
                brand = "Toyota",
                year = 2023,
                type = CarType.COMPACT,
                seats = 5,
                transmission = "Automatic",
                fuelType = "Petrol",
                features = listOf("Air Conditioning", "Bluetooth", "Backup Camera"),
                images = listOf("car1.jpg"),
                pricePerDay = 45.0,
                location = CarLocation("Ho Chi Minh City", "Tan Son Nhat Airport", "Terminal 2"),
                isAvailable = true
            ),
            Car(
                id = "car_2",
                model = "CX-5",
                brand = "Mazda",
                year = 2023,
                type = CarType.SUV,
                seats = 5,
                transmission = "Automatic",
                fuelType = "Petrol",
                features = listOf("Air Conditioning", "GPS", "Leather Seats", "Sunroof"),
                images = listOf("car2.jpg"),
                pricePerDay = 75.0,
                location = CarLocation("Hanoi", "Noi Bai Airport", "Terminal 1"),
                isAvailable = true
            ),
            Car(
                id = "car_3",
                model = "Camry",
                brand = "Toyota",
                year = 2024,
                type = CarType.MIDSIZE,
                seats = 5,
                transmission = "Automatic",
                fuelType = "Hybrid",
                features = listOf("Air Conditioning", "GPS", "Premium Sound", "Heated Seats"),
                images = listOf("car3.jpg"),
                pricePerDay = 65.0,
                location = CarLocation("Da Nang", "Da Nang Airport", "Main Terminal"),
                isAvailable = true
            )
        ).forEach { save(it) }
    }
}

class CarBookingRepositoryImpl : CarBookingRepository {
    private val bookings = ConcurrentHashMap<String, CarBooking>()

    override fun findById(id: String): CarBooking? = bookings[id]

    override fun findByCarId(carId: String): List<CarBooking> = 
        bookings.values.filter { it.carId == carId }

    override fun findByCarIds(carIds: List<String>): List<CarBooking> =
        bookings.values.filter { it.carId in carIds }

    override fun findByUserIdOrderByCreatedAtDesc(userId: String): List<CarBooking> = 
        bookings.values.filter { it.userId == userId }
            .sortedByDescending { it.createdAt }

    override fun save(booking: CarBooking): CarBooking {
        bookings[booking.id] = booking
        return booking
    }
}

class CarReviewRepositoryImpl : CarReviewRepository {
    private val reviews = ConcurrentHashMap<String, CarReview>()

    override fun findById(id: String): CarReview? = reviews[id]

    override fun findByCarId(carId: String): List<CarReview> = 
        reviews.values.filter { it.carId == carId }

    override fun findByCarIdOrderByCreatedAtDesc(carId: String): List<CarReview> = 
        reviews.values.filter { it.carId == carId }
            .sortedByDescending { it.createdAt }

    override fun save(review: CarReview): CarReview {
        reviews[review.id] = review
        return review
    }
}