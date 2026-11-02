package appointmentbooking

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import com.auth0.jwt.interfaces.JWTPrincipal
import java.time.LocalDateTime

class AppointmentBookingHandler(private val service: AppointmentBookingService) {

    private suspend fun requireAppointmentOwnership(call: ApplicationCall, appointmentId: String): String? {
        val principal = call.principal<JWTPrincipal>()
            ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required")); return null }
        val userId = principal.subject
            ?: run { call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Invalid token: missing subject")); return null }

        val appointment = service.getAppointment(appointmentId)
            ?: run { call.respond(HttpStatusCode.NotFound, mapOf("error" to "Appointment not found")); return null }

        // Ownership: either the user is the client OR the provider
        if (appointment.userId != userId && appointment.providerId != userId) {
            call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Access denied: not your appointment"))
            return null
        }

        return userId
    }
    
    suspend fun searchProviders(call: ApplicationCall) {
        try {
            val profession = call.request.queryParameters["profession"] ?: throw IllegalArgumentException("Profession required")
            val city = call.request.queryParameters["city"]
            val maxPrice = call.request.queryParameters["maxPrice"]?.toDoubleOrNull()
            val minRating = call.request.queryParameters["minRating"]?.toDoubleOrNull()
            val sortBy = call.request.queryParameters["sortBy"]?.let {
                try { SortOption.valueOf(it.uppercase()) } catch (e: Exception) { SortOption.RATING }
            } ?: SortOption.RATING
            val limit = call.request.queryParameters["limit"]?.toIntOrNull() ?: 20
            
            val request = ProviderSearchRequest(
                profession = profession,
                city = city,
                maxPrice = maxPrice,
                minRating = minRating,
                sortBy = sortBy,
                limit = limit
            )
            
            val providers = service.searchProviders(request)
            call.respond(mapOf("providers" to providers, "count" to providers.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }
    
    suspend fun getProvider(call: ApplicationCall) {
        try {
            val providerId = call.parameters["id"] ?: throw IllegalArgumentException("Provider ID required")
            val provider = service.getProvider(providerId)
            
            if (provider != null) {
                call.respond(mapOf("provider" to provider))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Provider not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get provider")))
        }
    }
    
    suspend fun getProviderAvailability(call: ApplicationCall) {
        try {
            val providerId = call.parameters["id"] ?: throw IllegalArgumentException("Provider ID required")
            val dateStr = call.request.queryParameters["date"] ?: throw IllegalArgumentException("Date required")
            val date = parseDateTime(dateStr)
            val availability = service.getProviderAvailability(providerId, date)
            call.respond(mapOf("availability" to availability))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get availability")))
        }
    }
    
    suspend fun getProviderServices(call: ApplicationCall) {
        try {
            val providerId = call.parameters["id"] ?: throw IllegalArgumentException("Provider ID required")
            val services = service.getProviderServices(providerId)
            call.respond(mapOf("services" to services))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get services")))
        }
    }
    
    suspend fun bookAppointment(call: ApplicationCall) {
        try {
            val request = call.receive<AppointmentRequest>()
            val appointment = service.bookAppointment(request)
            call.respond(HttpStatusCode.Created, mapOf("appointment" to appointment))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Booking failed")))
        }
    }
    
    suspend fun getAppointment(call: ApplicationCall) {
        try {
            val appointmentId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Appointment ID required")); return }
            val authUserId = requireAppointmentOwnership(call, appointmentId)
            if (authUserId == null) return
            call.respond(mapOf("appointment" to service.getAppointment(appointmentId)!!))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get appointment")))
        }
    }
    
    suspend fun getUserAppointments(call: ApplicationCall) {
        try {
            val userId = call.request.queryParameters["userId"] ?: throw IllegalArgumentException("User ID required")
            val appointments = service.getUserAppointments(userId)
            call.respond(mapOf("appointments" to appointments, "count" to appointments.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get appointments")))
        }
    }
    
    suspend fun getProviderAppointments(call: ApplicationCall) {
        try {
            val providerId = call.request.queryParameters["providerId"] ?: throw IllegalArgumentException("Provider ID required")
            val appointments = service.getProviderAppointments(providerId)
            call.respond(mapOf("appointments" to appointments, "count" to appointments.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get appointments")))
        }
    }
    
    suspend fun confirmAppointment(call: ApplicationCall) {
        try {
            val appointmentId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Appointment ID required")); return }
            val authUserId = requireAppointmentOwnership(call, appointmentId)
            if (authUserId == null) return
            val appointment = service.confirmAppointment(appointmentId)
            if (appointment != null) {
                call.respond(mapOf("appointment" to appointment, "message" to "Appointment confirmed"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot confirm appointment"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Confirmation failed")))
        }
    }
    
    suspend fun startAppointment(call: ApplicationCall) {
        try {
            val appointmentId = call.parameters["id"] ?: throw IllegalArgumentException("Appointment ID required")
            val appointment = service.startAppointment(appointmentId)
            
            if (appointment != null) {
                call.respond(mapOf("appointment" to appointment, "message" to "Appointment started"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot start appointment"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to start appointment")))
        }
    }
    
    suspend fun completeAppointment(call: ApplicationCall) {
        try {
            val appointmentId = call.parameters["id"] ?: throw IllegalArgumentException("Appointment ID required")
            val appointment = service.completeAppointment(appointmentId)
            
            if (appointment != null) {
                call.respond(mapOf("appointment" to appointment, "message" to "Appointment completed"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot complete appointment"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Completion failed")))
        }
    }
    
    suspend fun cancelAppointment(call: ApplicationCall) {
        try {
            val appointmentId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Appointment ID required")); return }
            val authUserId = requireAppointmentOwnership(call, appointmentId)
            if (authUserId == null) return
            val appointment = service.cancelAppointment(appointmentId)
            if (appointment != null) {
                call.respond(mapOf("appointment" to appointment, "message" to "Appointment cancelled"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot cancel appointment"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Cancellation failed")))
        }
    }
    
    suspend fun markNoShow(call: ApplicationCall) {
        try {
            val appointmentId = call.parameters["id"] ?: run { call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Appointment ID required")); return }
            val authUserId = requireAppointmentOwnership(call, appointmentId)
            if (authUserId == null) return
            val appointment = service.markNoShow(appointmentId)
            if (appointment != null) {
                call.respond(mapOf("appointment" to appointment, "message" to "Marked as no-show"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot mark as no-show"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to mark no-show")))
        }
    }
    
    suspend fun addReview(call: ApplicationCall) {
        try {
            val request = call.receive<AppointmentReviewRequest>()
            val review = service.addReview(
                request.userId,
                request.appointmentId,
                request.rating,
                request.comment
            )
            call.respond(HttpStatusCode.Created, mapOf("review" to review))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to add review")))
        }
    }
    
    suspend fun getProviderReviews(call: ApplicationCall) {
        try {
            val providerId = call.parameters["id"] ?: throw IllegalArgumentException("Provider ID required")
            val reviews = service.getProviderReviews(providerId)
            call.respond(mapOf("reviews" to reviews, "count" to reviews.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get reviews")))
        }
    }
    
    suspend fun getUpcomingAppointments(call: ApplicationCall) {
        try {
            val providerId = call.request.queryParameters["providerId"] ?: throw IllegalArgumentException("Provider ID required")
            val days = call.request.queryParameters["days"]?.toIntOrNull() ?: 7
            val appointments = service.getUpcomingAppointments(providerId, days)
            call.respond(mapOf("appointments" to appointments, "count" to appointments.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get appointments")))
        }
    }
    
    private fun parseDateTime(dateTimeStr: String): LocalDateTime {
        return try {
            LocalDateTime.parse(dateTimeStr)
        } catch (e: Exception) {
            throw IllegalArgumentException("Invalid datetime format: $dateTimeStr")
        }
    }
}

data class AppointmentRequest(
    val userId: String,
    val providerId: String,
    val serviceId: String,
    val preferredDateTime: LocalDateTime,
    val notes: String? = null
)

data class TimeSlot(
    val dayOfWeek: Int,
    val startTime: String,
    val endTime: String,
    val isAvailable: Boolean = true
)

data class AppointmentReviewRequest(
    val userId: String,
    val appointmentId: String,
    val rating: Int,
    val comment: String
)
