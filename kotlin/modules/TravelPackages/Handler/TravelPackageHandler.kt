package travelpackages

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.auth.*
import io.ktor.http.*
import java.time.LocalDate

class TravelPackageHandler(private val service: TravelPackageService) {
    
    /**
     * Extract user ID from JWT token or X-User-Id header
     */
    private fun extractUserId(call: ApplicationCall): String? {
        // First try JWT principal
        val principal = call.authentication.principal<JWTPrincipal>()
        if (principal != null) {
            return principal.subject
        }
        // Fallback to header (for backwards compatibility)
        return call.request.headers["X-User-Id"]
    }
    
    /**
     * Require authentication or return 401
     */
    private fun requireAuth(call: ApplicationCall): String? {
        val userId = extractUserId(call)
        if (userId.isNullOrBlank()) {
            return null
        }
        return userId
    }
    
    suspend fun searchPackages(call: ApplicationCall) {
        try {
            val destination = call.request.queryParameters["destination"]
            val minDuration = call.request.queryParameters["minDuration"]?.toIntOrNull()
            val maxDuration = call.request.queryParameters["maxDuration"]?.toIntOrNull()
            val maxPrice = call.request.queryParameters["maxPrice"]?.toDoubleOrNull()
            val travelDate = call.request.queryParameters["travelDate"]?.let { parseDate(it) }
            val sortBy = call.request.queryParameters["sortBy"]?.let {
                try { PackageSortOption.valueOf(it.uppercase()) } catch (e: Exception) { PackageSortOption.RATING }
            } ?: PackageSortOption.RATING
            
            val request = PackageSearchRequest(
                destination = destination,
                minDuration = minDuration,
                maxDuration = maxDuration,
                maxPrice = maxPrice,
                travelDate = travelDate,
                sortBy = sortBy
            )
            
            val packages = service.searchPackages(request)
            call.respond(mapOf("packages" to packages, "count" to packages.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }
    
    suspend fun getPackage(call: ApplicationCall) {
        try {
            val packageId = call.parameters["id"] ?: throw IllegalArgumentException("Package ID required")
            val travelPackage = service.getPackage(packageId)
            
            if (travelPackage != null) {
                call.respond(mapOf("package" to travelPackage))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Package not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get package")))
        }
    }
    
    suspend fun getFeaturedPackages(call: ApplicationCall) {
        try {
            val packages = service.getFeaturedPackages()
            call.respond(mapOf("packages" to packages, "count" to packages.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get featured packages")))
        }
    }
    
    suspend fun getDestinations(call: ApplicationCall) {
        try {
            val destinations = service.getDestinations()
            call.respond(mapOf("destinations" to destinations))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get destinations")))
        }
    }
    
    suspend fun createBooking(call: ApplicationCall) {
        try {
            // Require authentication
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }
            
            val request = call.receive<CreatePackageBookingRequest>()
            // Add user ID from authenticated session
            val booking = service.createBooking(request.copy(userId = userId))
            call.respond(HttpStatusCode.Created, mapOf("booking" to booking))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Booking failed")))
        }
    }
    
    suspend fun getBooking(call: ApplicationCall) {
        try {
            // Require authentication
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }
            
            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val booking = service.getBooking(bookingId)
            
            if (booking != null) {
                // Verify ownership - prevent IDOR
                if (booking.userId != userId) {
                    call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Access denied"))
                    return
                }
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
            // Require authentication - get user ID from JWT token, not query params (prevents IDOR)
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }
            
            // Only return the authenticated user's bookings
            val bookings = service.getUserBookings(userId)
            call.respond(mapOf("bookings" to bookings, "count" to bookings.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get bookings")))
        }
    }
    
    suspend fun cancelBooking(call: ApplicationCall) {
        try {
            // Require authentication
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }
            
            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            
            // Verify ownership before cancellation - prevent IDOR
            val existingBooking = service.getBooking(bookingId)
            if (existingBooking == null) {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Booking not found"))
                return
            }
            if (existingBooking.userId != userId) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Access denied"))
                return
            }
            
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
    
    private fun parseDate(dateStr: String): LocalDate {
        return try {
            LocalDate.parse(dateStr)
        } catch (e: Exception) {
            throw IllegalArgumentException("Invalid date format: $dateStr")
        }
    }
}

data class PackageContactInfo(
    val name: String,
    val email: String,
    val phone: String
)
