package travelpackages

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import com.auth0.jwt.interfaces.JWTPrincipal
import java.time.LocalDate

class TravelPackageHandler(private val service: TravelPackageService) {

    suspend fun searchPackages(call: ApplicationCall) {
        try {
            val destination = call.request.queryParameters["destination"]
            val minDuration = call.request.queryParameters["minDuration"]?.toIntOrNull()
            val maxDuration = call.request.queryParameters["maxDuration"]?.toIntOrNull()
            val maxPrice = call.request.queryParameters["maxPrice"]?.toDoubleOrNull()
            val travelDate = call.request.queryParameters["travelDate"]?.let { LocalDate.parse(it) }
            val sortBy = call.request.queryParameters["sortBy"]?.let { 
                PackageSortOption.valueOf(it.uppercase()) 
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
            call.respond(mapOf("packages" to packages))
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
            call.respond(mapOf("packages" to packages))
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
            val request = call.receive<CreatePackageBookingRequest>()
            val principal = call.principal<JWTPrincipal>()
            val userId = principal?.subject ?: "anonymous"
            
            val bookingRequest = request.copy(userId = userId)
            val booking = service.createBooking(bookingRequest)
            call.respond(HttpStatusCode.Created, mapOf("booking" to booking))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Booking creation failed")))
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
            val principal = call.principal<JWTPrincipal>()
                ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required")); return }
            val userId = principal.subject ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject")); return }
            val bookings = service.getUserBookings(userId)
            call.respond(mapOf("bookings" to bookings))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get bookings")))
        }
    }

    suspend fun cancelBooking(call: ApplicationCall) {
        try {
            val bookingId = call.parameters["id"] ?: throw IllegalArgumentException("Booking ID required")
            val booking = service.cancelBooking(bookingId)

            if (booking != null) {
                call.respond(mapOf("booking" to booking))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Booking not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to cancel booking")))
        }
    }
}