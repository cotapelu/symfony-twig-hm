package eventsbooking

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*
import java.time.LocalDateTime

class EventBookingHandler(private val service: EventsBookingService) {

    private fun extractUserId(call: ApplicationCall): String? {
        return call.request.headers["X-User-Id"]
    }

    private fun requireAuth(call: ApplicationCall): String? {
        val userId = extractUserId(call)
        if (userId.isNullOrBlank()) {
            return null
        }
        return userId
    }

    private fun isAdmin(call: ApplicationCall): Boolean {
        val role = call.request.headers["X-User-Role"]
        return role == "ADMIN"
    }

    private fun validateEventId(eventId: String): String? {
        if (eventId.isBlank() || eventId.length > 100) {
            return "Invalid event ID"
        }
        return null
    }

    private fun validateTicketId(ticketId: String): String? {
        if (ticketId.isBlank() || ticketId.length > 100) {
            return "Invalid ticket ID"
        }
        return null
    }

    private fun validateQuantity(quantity: Int): String? {
        if (quantity < 1 || quantity > 10) {
            return "Quantity must be between 1 and 10"
        }
        return null
    }

    private fun validateRating(rating: Int): String? {
        if (rating < 1 || rating > 5) {
            return "Rating must be between 1 and 5"
        }
        return null
    }

    suspend fun searchEvents(call: ApplicationCall) {
        try {
            val query = call.request.queryParameters["query"]
            val category = call.request.queryParameters["category"]
            val startDate = call.request.queryParameters["startDate"]?.let { parseDateTime(it) }
            val location = call.request.queryParameters["location"]
            val sortBy = call.request.queryParameters["sortBy"]?.let {
                try { SortOption.valueOf(it.uppercase()) } catch (e: Exception) { SortOption.DATE }
            } ?: SortOption.DATE
            val limit = call.request.queryParameters["limit"]?.toIntOrNull() ?: 20
            val offset = call.request.queryParameters["offset"]?.toIntOrNull() ?: 0

            if (query != null && query.length > 200) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Query too long"))
                return
            }

            val request = EventSearchRequest(
                query = query,
                category = category,
                startDate = startDate,
                location = location,
                sortBy = sortBy,
                limit = limit.coerceIn(1, 100),
                offset = offset.coerceAtLeast(0)
            )

            val events = service.searchEvents(request)
            call.respond(mapOf("events" to events, "count" to events.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }

    suspend fun getEvent(call: ApplicationCall) {
        try {
            val eventId = call.parameters["id"] ?: throw IllegalArgumentException("Event ID required")
            
            val idError = validateEventId(eventId)
            if (idError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to idError))
                return
            }
            
            val event = service.getEvent(eventId)

            if (event != null) {
                call.respond(mapOf("event" to event))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Event not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get event")))
        }
    }

    suspend fun getEventWithTickets(call: ApplicationCall) {
        try {
            val eventId = call.parameters["id"] ?: throw IllegalArgumentException("Event ID required")
            
            val idError = validateEventId(eventId)
            if (idError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to idError))
                return
            }
            
            val eventWithTickets = service.getEventWithTickets(eventId)

            if (eventWithTickets != null) {
                call.respond(mapOf("event" to eventWithTickets.first, "tickets" to eventWithTickets.second))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Event not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get event tickets")))
        }
    }

    suspend fun createEvent(call: ApplicationCall) {
        try {
            if (!isAdmin(call)) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Admin access required"))
                return
            }

            val request = call.receive<CreateEventRequest>()
            
            if (request.title.isBlank() || request.title.length > 255) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Event title required (max 255 chars)"))
                return
            }

            val event = service.createEvent(request)
            call.respond(HttpStatusCode.Created, mapOf("event" to event))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to create event")))
        }
    }

    suspend fun publishEvent(call: ApplicationCall) {
        try {
            if (!isAdmin(call)) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Admin access required"))
                return
            }

            val eventId = call.parameters["id"] ?: throw IllegalArgumentException("Event ID required")
            
            val idError = validateEventId(eventId)
            if (idError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to idError))
                return
            }
            
            val event = service.publishEvent(eventId)

            if (event != null) {
                call.respond(mapOf("event" to event, "message" to "Event published successfully"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot publish event"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to publish event")))
        }
    }

    suspend fun cancelEvent(call: ApplicationCall) {
        try {
            if (!isAdmin(call)) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Admin access required"))
                return
            }

            val eventId = call.parameters["id"] ?: throw IllegalArgumentException("Event ID required")
            
            val idError = validateEventId(eventId)
            if (idError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to idError))
                return
            }
            
            val event = service.cancelEvent(eventId)

            if (event != null) {
                call.respond(mapOf("event" to event, "message" to "Event cancelled successfully"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot cancel event"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to cancel event")))
        }
    }

    suspend fun addTicketType(call: ApplicationCall) {
        try {
            if (!isAdmin(call)) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Admin access required"))
                return
            }

            val eventId = call.parameters["id"] ?: throw IllegalArgumentException("Event ID required")
            
            val idError = validateEventId(eventId)
            if (idError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to idError))
                return
            }
            
            val request = call.receive<CreateTicketTypeRequest>()
            
            if (request.name.isBlank() || request.price < 0) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Ticket name and valid price required"))
                return
            }
            
            if (request.availableQuantity < 1) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Available quantity must be at least 1"))
                return
            }

            val ticketType = service.addTicketType(eventId, request)
            call.respond(HttpStatusCode.Created, mapOf("ticketType" to ticketType))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to add ticket type")))
        }
    }

    suspend fun purchaseTicket(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val request = call.receive<PurchaseTicketRequest>()
            
            if (request.eventId.isBlank() || request.ticketTypeId.isBlank()) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Event ID and ticket type ID required"))
                return
            }

            val qtyError = validateQuantity(request.quantity)
            if (qtyError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to qtyError))
                return
            }

            val purchaseRequest = request.copy(userId = userId)
            val ticket = service.purchaseTicket(purchaseRequest)
            call.respond(HttpStatusCode.Created, mapOf("ticket" to ticket))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Ticket purchase failed")))
        }
    }

    suspend fun cancelTicket(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val ticketId = call.parameters["id"] ?: throw IllegalArgumentException("Ticket ID required")
            
            val idError = validateTicketId(ticketId)
            if (idError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to idError))
                return
            }

            val existingTicket = service.getTicket(ticketId)
            if (existingTicket == null) {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Ticket not found"))
                return
            }
            
            if (existingTicket.userId != userId) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Cannot cancel other user's ticket"))
                return
            }

            val ticket = service.cancelTicket(ticketId)

            if (ticket != null) {
                call.respond(mapOf("ticket" to ticket, "message" to "Ticket cancelled successfully"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot cancel ticket"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to cancel ticket")))
        }
    }

    suspend fun useTicket(call: ApplicationCall) {
        try {
            if (!isAdmin(call)) {
                call.respond(HttpStatusCode.Forbidden, mapOf("error" to "Admin access required"))
                return
            }

            val ticketId = call.parameters["id"] ?: throw IllegalArgumentException("Ticket ID required")
            
            val idError = validateTicketId(ticketId)
            if (idError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to idError))
                return
            }
            
            val ticket = service.useTicket(ticketId)

            if (ticket != null) {
                call.respond(mapOf("ticket" to ticket, "message" to "Ticket validated successfully"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot validate ticket"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to validate ticket")))
        }
    }

    suspend fun getUserTickets(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val tickets = service.getUserTickets(userId)
            call.respond(mapOf("tickets" to tickets, "count" to tickets.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get tickets")))
        }
    }

    suspend fun getUpcomingEvents(call: ApplicationCall) {
        try {
            val days = call.request.queryParameters["days"]?.toIntOrNull() ?: 7
            
            if (days < 1 || days > 365) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Days must be between 1 and 365"))
                return
            }
            
            val events = service.getUpcomingEvents(days)
            call.respond(mapOf("events" to events, "count" to events.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get upcoming events")))
        }
    }

    suspend fun getFeaturedEvents(call: ApplicationCall) {
        try {
            val events = service.getFeaturedEvents()
            call.respond(mapOf("events" to events, "count" to events.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get featured events")))
        }
    }

    suspend fun addReview(call: ApplicationCall) {
        try {
            val userId = requireAuth(call)
            if (userId == null) {
                call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
                return
            }

            val request = call.receive<CreateEventReviewRequest>()
            
            val ratingError = validateRating(request.rating)
            if (ratingError != null) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to ratingError))
                return
            }

            if (request.comment.length > 1000) {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Comment too long (max 1000 chars)"))
                return
            }

            val reviewRequest = request.copy(userId = userId)
            val review = service.addReview(reviewRequest)
            call.respond(HttpStatusCode.Created, mapOf("review" to review))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to add review")))
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