package travelpackages

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.routing.*

fun Route.travelPackagesRoutes() {
    val service = TravelPackagesService(
        PackageRepository(),
        PackageBookingRepository(),
        PackageReviewRepository()
    )
    
    // Package routes
    get("/api/packages/search") {
        val request = call.receive<PackageSearchRequest>()
        val packages = service.searchPackages(request)
        call.respond(packages)
    }
    
    get("/api/packages/{packageId}") {
        val packageId = call.parameters["packageId"] ?: throw IllegalArgumentException("Package ID required")
        val package = service.getPackage(packageId)
        if (package != null) {
            call.respond(package)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Package not found")
        }
    }
    
    get("/api/packages/{packageId}/itinerary") {
        val packageId = call.parameters["packageId"] ?: throw IllegalArgumentException("Package ID required")
        val itinerary = service.getPackageItinerary(packageId)
        call.respond(itinerary)
    }
    
    get("/api/packages/{packageId}/pricing") {
        val packageId = call.parameters["packageId"] ?: throw IllegalArgumentException("Package ID required")
        val pricing = service.getPackagePricing(packageId)
        call.respond(pricing)
    }
    
    get("/api/packages/{packageId}/reviews") {
        val packageId = call.parameters["packageId"] ?: throw IllegalArgumentException("Package ID required")
        val reviews = service.getPackageReviews(packageId)
        call.respond(reviews)
    }
    
    get("/api/packages/{packageId}/availability") {
        val packageId = call.parameters["packageId"] ?: throw IllegalArgumentException("Package ID required")
        val dateStr = call.parameters["date"] ?: throw IllegalArgumentException("Date required")
        val availability = service.checkAvailability(packageId, dateStr)
        call.respond(mapOf("available" to availability))
    }
    
    // Booking routes
    post("/api/packages/{packageId}/book") {
        val packageId = call.parameters["packageId"] ?: throw IllegalArgumentException("Package ID required")
        val request = call.receive<PackageBookingRequest>()
        val booking = service.createBooking(packageId, request)
        call.respond(booking)
    }
    
    get("/api/package-bookings/{bookingId}") {
        val bookingId = call.parameters["bookingId"] ?: throw IllegalArgumentException("Booking ID required")
        val booking = service.getBooking(bookingId)
        if (booking != null) {
            call.respond(booking)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Booking not found")
        }
    }
    
    get("/api/package-bookings/user/{userId}") {
        val userId = call.parameters["userId"] ?: throw IllegalArgumentException("User ID required")
        val bookings = service.getUserBookings(userId)
        call.respond(bookings)
    }
    
    post("/api/package-bookings/{bookingId}/cancel") {
        val bookingId = call.parameters["bookingId"] ?: throw IllegalArgumentException("Booking ID required")
        val booking = service.cancelBooking(bookingId)
        if (booking != null) {
            call.respond(booking)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot cancel booking")
        }
    }
    
    post("/api/package-bookings/{bookingId}/review") {
        val bookingId = call.parameters["bookingId"] ?: throw IllegalArgumentException("Booking ID required")
        val request = call.receive<AddPackageReviewRequest>()
        val review = service.addReview(request.userId, bookingId, request.rating, request.comment)
        call.respond(review)
    }
    
    // Categories
    get("/api/packages/categories") {
        val categories = service.getPackageCategories()
        call.respond(categories)
    }
}

data class PackageSearchRequest(
    val query: String? = null,
    val category: String? = null,
    val destination: String? = null,
    val minDuration: Int? = null,
    val maxDuration: Int? = null,
    val minPrice: Double? = null,
    val maxPrice: Double? = null,
    val departureCity: String? = null,
    val rating: Double? = null,
    val limit: Int = 20
)

data class PackageBookingRequest(
    val userId: String,
    val departureDate: String,
    val travelers: Int,
    val travelerInfo: List<Map<String, String>> = emptyList(),
    val addOns: List<String> = emptyList(),
    val specialRequests: String? = null
)

data class AddPackageReviewRequest(
    val userId: String,
    val rating: Int,
    val comment: String
)
