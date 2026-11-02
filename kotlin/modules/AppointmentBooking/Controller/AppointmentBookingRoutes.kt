package appointmentbooking

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import java.time.LocalDateTime

fun Route.appointmentBookingRoutes() {
    val service = AppointmentBookingService(
        ServiceProviderRepository(),
        AppointmentRepository(),
        AppointmentReviewRepository()
    )
    
    // Provider routes
    get("/api/appointment/providers/search") {
        val request = call.receive<ProviderSearchRequest>()
        val providers = service.searchProviders(request)
        call.respond(providers)
    }
    
    get("/api/appointment/providers/{providerId}") {
        val providerId = call.parameters["providerId"] ?: throw IllegalArgumentException("Provider ID required")
        val provider = service.getProvider(providerId)
        if (provider != null) {
            call.respond(provider)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Provider not found")
        }
    }
    
    get("/api/appointment/providers/{providerId}/availability") {
        val providerId = call.parameters["providerId"] ?: throw IllegalArgumentException("Provider ID required")
        val dateStr = call.parameters["date"] ?: throw IllegalArgumentException("Date required")
        val date = LocalDateTime.parse(dateStr)
        val slots = service.getProviderAvailability(providerId, date)
        call.respond(slots)
    }
    
    get("/api/appointment/providers/{providerId}/services") {
        val providerId = call.parameters["providerId"] ?: throw IllegalArgumentException("Provider ID required")
        val services = service.getProviderServices(providerId)
        call.respond(services)
    }
    
    get("/api/appointment/providers/{providerId}/reviews") {
        val providerId = call.parameters["providerId"] ?: throw IllegalArgumentException("Provider ID required")
        val reviews = service.getProviderReviews(providerId)
        call.respond(reviews)
    }
    
    // Appointment routes
    post("/api/appointment/book") {
        val request = call.receive<AppointmentRequest>()
        val appointment = service.bookAppointment(request)
        call.respond(appointment)
    }
    
    get("/api/appointment/{appointmentId}") {
        val appointmentId = call.parameters["appointmentId"] ?: throw IllegalArgumentException("Appointment ID required")
        val appointment = service.getAppointment(appointmentId)
        if (appointment != null) {
            call.respond(appointment)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Appointment not found")
        }
    }
    
    get("/api/appointment/user/{userId}") {
        val userId = call.parameters["userId"] ?: throw IllegalArgumentException("User ID required")
        val appointments = service.getUserAppointments(userId)
        call.respond(appointments)
    }
    
    get("/api/appointment/provider/{providerId}") {
        val providerId = call.parameters["providerId"] ?: throw IllegalArgumentException("Provider ID required")
        val appointments = service.getProviderAppointments(providerId)
        call.respond(appointments)
    }
    
    post("/api/appointment/{appointmentId}/confirm") {
        val appointmentId = call.parameters["appointmentId"] ?: throw IllegalArgumentException("Appointment ID required")
        val appointment = service.confirmAppointment(appointmentId)
        if (appointment != null) {
            call.respond(appointment)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot confirm appointment")
        }
    }
    
    post("/api/appointment/{appointmentId}/start") {
        val appointmentId = call.parameters["appointmentId"] ?: throw IllegalArgumentException("Appointment ID required")
        val appointment = service.startAppointment(appointmentId)
        if (appointment != null) {
            call.respond(appointment)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot start appointment")
        }
    }
    
    post("/api/appointment/{appointmentId}/complete") {
        val appointmentId = call.parameters["appointmentId"] ?: throw IllegalArgumentException("Appointment ID required")
        val appointment = service.completeAppointment(appointmentId)
        if (appointment != null) {
            call.respond(appointment)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot complete appointment")
        }
    }
    
    post("/api/appointment/{appointmentId}/cancel") {
        val appointmentId = call.parameters["appointmentId"] ?: throw IllegalArgumentException("Appointment ID required")
        val appointment = service.cancelAppointment(appointmentId)
        if (appointment != null) {
            call.respond(appointment)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot cancel appointment")
        }
    }
    
    post("/api/appointment/{appointmentId}/noshow") {
        val appointmentId = call.parameters["appointmentId"] ?: throw IllegalArgumentException("Appointment ID required")
        val appointment = service.markNoShow(appointmentId)
        if (appointment != null) {
            call.respond(appointment)
        } else {
            call.respond(io.ktor.http.HttpStatusCode.BadRequest, "Cannot mark as no-show")
        }
    }
    
    // Review routes
    post("/api/appointment/{appointmentId}/review") {
        val appointmentId = call.parameters["appointmentId"] ?: throw IllegalArgumentException("Appointment ID required")
        val request = call.receive<AddReviewRequest>()
        val review = service.addReview(
            request.userId,
            appointmentId,
            request.rating,
            request.comment
        )
        call.respond(review)
    }
    
    get("/api/appointment/provider/{providerId}/upcoming") {
        val providerId = call.parameters["providerId"] ?: throw IllegalArgumentException("Provider ID required")
        val days = call.parameters["days"]?.toInt() ?: 7
        val appointments = service.getUpcomingAppointments(providerId, days)
        call.respond(appointments)
    }
}

data class AddReviewRequest(
    val userId: String,
    val rating: Int,
    val comment: String
)
