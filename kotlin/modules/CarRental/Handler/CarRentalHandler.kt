package carrental

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import java.time.LocalDate

class CarRentalHandler(private val service: CarRentalService) {
    
    suspend fun searchCars(call: ApplicationCall) {
        try {
            val location = call.request.queryParameters["location"]
            val carType = call.request.queryParameters["carType"]
            val minPrice = call.request.queryParameters["minPrice"]?.toDoubleOrNull()
            val maxPrice = call.request.queryParameters["maxPrice"]?.toDoubleOrNull()
            val minSeats = call.request.queryParameters["minSeats"]?.toIntOrNull()
            val features = call.request.queryParameters["features"]?.split(",")
            
            val request = CarSearchRequest(
                location = location,
                carType = carType,
                minPrice = minPrice,
                maxPrice = maxPrice,
                minSeats = minSeats,
                features = features
            )
            
            val cars = service.searchCars(request)
            call.respond(mapOf("cars" to cars, "count" to cars.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }
    
    suspend fun getFeaturedCars(call: ApplicationCall) {
        try {
            val cars = service.getFeaturedCars()
            call.respond(mapOf("cars" to cars, "count" to cars.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get featured cars")))
        }
    }
    
    suspend fun getCar(call: ApplicationCall) {
        try {
            val carId = call.parameters["id"] ?: throw IllegalArgumentException("Car ID required")
            val car = service.getCar(carId)
            
            if (car != null) {
                call.respond(mapOf("car" to car))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Car not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get car")))
        }
    }
    
    suspend fun checkAvailability(call: ApplicationCall) {
        try {
            val carId = call.parameters["id"] ?: throw IllegalArgumentException("Car ID required")
            val startDate = call.request.queryParameters["startDate"] 
                ?: throw IllegalArgumentException("Start date required")
            val endDate = call.request.queryParameters["endDate"] 
                ?: throw IllegalArgumentException("End date required")
            
            val available = service.checkAvailability(
                carId,
                parseDate(startDate),
                parseDate(endDate)
            )
            call.respond(mapOf("available" to available))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Availability check failed")))
        }
    }
    
    suspend fun createBooking(call: ApplicationCall) {
        try {
            val request = call.receive<CarBookingRequest>()
            val booking = service.createBooking(request)
            call.respond(HttpStatusCode.Created, mapOf("booking" to booking))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Booking failed")))
        }
    }
    
    suspend fun getBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val booking = service.getBooking(bookingId)
            
            if (booking != null) {
                call.respond(mapOf("booking" to booking))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Booking not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get booking")))
        }
    }
    
    suspend fun getUserBookings(call: ApplicationCall) {
        try {
            val userId = call.request.queryParameters["userId"] 
                ?: throw IllegalArgumentException("User ID required")
            val bookings = service.getUserBookings(userId)
            call.respond(mapOf("bookings" to bookings, "count" to bookings.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get bookings")))
        }
    }
    
    suspend fun cancelBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val booking = service.cancelBooking(bookingId)
            
            if (booking != null) {
                call.respond(mapOf("booking" to booking, "message" to "Booking cancelled successfully"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot cancel booking"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Cancellation failed")))
        }
    }
    
    suspend fun addReview(call: ApplicationCall) {
        try {
            val request = call.receive<CarReviewRequest>()
            val review = service.addReview(
                request.userId,
                request.carId,
                request.rating,
                request.comment
            )
            call.respond(HttpStatusCode.Created, mapOf("review" to review))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to add review")))
        }
    }
    
    suspend fun getCarReviews(call: ApplicationCall) {
        try {
            val carId = call.parameters["id"] ?: throw IllegalArgumentException("Car ID required")
            val reviews = service.getCarReviews(carId)
            call.respond(mapOf("reviews" to reviews, "count" to reviews.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reviews")))
        }
    }
    
    private fun parseDate(dateStr: String): LocalDate {
        return try {
            LocalDate.parse(dateStr)
        } catch (e: Exception) {
            throw IllegalArgumentException("Invalid date format: $dateStr")
        }
    }
}

data class CarSearchRequest(
    val location: String? = null,
    val carType: String? = null,
    val minPrice: Double? = null,
    val maxPrice: Double? = null,
    val minSeats: Int? = null,
    val features: List<String>? = null,
    val transmission: String? = null,
    val fuelType: String? = null
)

data class CarBookingRequest(
    val userId: String,
    val carId: String,
    val startDate: LocalDate,
    val endDate: LocalDate,
    val pickupLocation: String,
    val dropoffLocation: String? = null,
    val extras: List<String>? = null,
    val totalPrice: Double,
    val driverInfo: DriverInfo? = null
)

data class DriverInfo(
    val firstName: String,
    val lastName: String,
    val licenseNumber: String,
    val licenseCountry: String
)

data class CarReviewRequest(
    val userId: String,
    val carId: String,
    val rating: Int,
    val comment: String
)
