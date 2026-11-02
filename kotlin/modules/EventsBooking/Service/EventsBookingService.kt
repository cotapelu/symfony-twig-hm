package eventsbooking

import java.time.LocalDateTime
import java.util.UUID

class EventsBookingService(
    private val eventRepository: EventRepository,
    private val ticketRepository: TicketRepository,
    private val reviewRepository: EventReviewRepository
) {

    fun searchEvents(request: EventSearchRequest): List<Event> {
        var events = eventRepository.findByStatus("published")
        
        if (request.category != null) {
            events = events.filter { it.category == request.category }
        }
        
        if (request.query != null) {
            val query = request.query.lowercase()
            events = events.filter { 
                it.title.lowercase().contains(query) || 
                it.description.lowercase().contains(query)
            }
        }
        
        if (request.startDate != null) {
            events = events.filter { it.startDateTime >= request.startDate }
        }
        
        if (request.location != null) {
            events = events.filter { 
                it.venue.lowercase().contains(request.location.lowercase()) ||
                it.city.lowercase().contains(request.location.lowercase())
            }
        }
        
        return when (request.sortBy) {
            SortOption.DATE -> events.sortedBy { it.startDateTime }
            SortOption.POPULARITY -> events.sortedByDescending { it.attendeeCount }
            SortOption.PRICE -> events.sortedBy { it.minTicketPrice }
        }.drop(request.offset).take(request.limit)
    }

    fun getEvent(eventId: String): Event? {
        return eventRepository.findById(eventId)
    }

    fun getEventWithTickets(eventId: String): Pair<Event, List<TicketType>>? {
        val event = eventRepository.findById(eventId) ?: return null
        val tickets = ticketRepository.findByEventId(eventId)
        return Pair(event, tickets)
    }

fun createEvent(request: CreateEventRequest): Event {
	// Input validation
	require(request.organizerId.isNotBlank()) { "Organizer ID is required" }
	require(request.title.isNotBlank()) { "Title is required" }
	require(request.title.length <= 200) { "Title is too long" }
	require(request.venue.isNotBlank()) { "Venue is required" }
	require(request.startDateTime.isAfter(java.time.LocalDateTime.now())) { "Start date must be in the future" }
	require(request.maxAttendees > 0) { "Max attendees must be positive" }

	val event = Event(
            id = UUID.randomUUID().toString(),
            organizerId = request.organizerId,
            title = request.title,
            description = request.description,
            category = request.category,
            venue = request.venue,
            address = request.address,
            city = request.city,
            startDateTime = request.startDateTime,
            endDateTime = request.endDateTime,
            coverImage = request.coverImage,
            status = "draft",
            maxAttendees = request.maxAttendees,
            minTicketPrice = request.ticketTypes.minOf { it.price }
        )
        return eventRepository.save(event)
    }

    fun publishEvent(eventId: String): Event? {
        val event = eventRepository.findById(eventId)
            ?: throw IllegalArgumentException("Event not found")
        
        return eventRepository.save(event.copy(status = "published"))
    }

    fun cancelEvent(eventId: String): Event? {
        val event = eventRepository.findById(eventId)
            ?: throw IllegalArgumentException("Event not found")
        
        // Cancel all pending tickets
        val tickets = ticketRepository.findByEventId(eventId)
        tickets.filter { it.status == TicketStatus.AVAILABLE }.forEach {
            ticketRepository.updateStatus(it.id, TicketStatus.CANCELLED)
        }
        
        return eventRepository.save(event.copy(status = "cancelled"))
    }

    fun addTicketType(eventId: String, request: CreateTicketTypeRequest): TicketType {
        val event = eventRepository.findById(eventId)
            ?: throw IllegalArgumentException("Event not found")
        
        val ticketType = TicketType(
            id = UUID.randomUUID().toString(),
            eventId = eventId,
            name = request.name,
            description = request.description,
            price = request.price,
            quantity = request.quantity,
            sold = 0,
            maxPerOrder = request.maxPerOrder
        )
        
        val savedTicket = ticketRepository.save(ticketType)
        
        // Update event min price
        val allTickets = ticketRepository.findByEventId(eventId)
        eventRepository.updateMinTicketPrice(eventId, allTickets.minOf { it.price })
        
        return savedTicket
    }

    fun purchaseTicket(request: PurchaseTicketRequest): Ticket {
        val ticketType = ticketRepository.findById(request.ticketTypeId)
            ?: throw IllegalArgumentException("Ticket type not found")
        
        if (ticketType.sold >= ticketType.quantity) {
            throw IllegalStateException("Tickets sold out")
        }
        
        if (request.quantity > ticketType.maxPerOrder) {
            throw IllegalArgumentException("Maximum ${ticketType.maxPerOrder} tickets per order")
        }
        
        val available = ticketType.quantity - ticketType.sold
        if (request.quantity > available) {
            throw IllegalArgumentException("Only $available tickets available")
        }
        
        val ticket = Ticket(
            id = UUID.randomUUID().toString(),
            ticketTypeId = request.ticketTypeId,
            eventId = ticketType.eventId,
            userId = request.userId,
            quantity = request.quantity,
            totalPrice = ticketType.price * request.quantity,
            status = TicketStatus.CONFIRMED,
            qrCode = UUID.randomUUID().toString()
        )
        
        ticketRepository.save(ticket.copy(status = TicketStatus.PENDING))
        ticketRepository.updateSold(ticketType.id, ticketType.sold + request.quantity)
        
        val finalTicket = ticket.copy(status = TicketStatus.CONFIRMED)
        ticketRepository.save(finalTicket)
        
        // Update event attendee count
        eventRepository.incrementAttendeeCount(ticketType.eventId, request.quantity)
        
        return finalTicket
    }

    fun cancelTicket(ticketId: String): Ticket? {
        val ticket = ticketRepository.findById(ticketId)
            ?: throw IllegalArgumentException("Ticket not found")
        
        if (ticket.status == TicketStatus.USED || ticket.status == TicketStatus.CANCELLED) {
            throw IllegalStateException("Ticket cannot be cancelled")
        }
        
        ticketRepository.updateStatus(ticketId, TicketStatus.CANCELLED)
        
        val ticketType = ticketRepository.findById(ticket.ticketTypeId)
        ticketType?.let {
            ticketRepository.updateSold(it.id, it.sold - ticket.quantity)
        }
        
        eventRepository.decrementAttendeeCount(ticket.eventId, ticket.quantity)
        
        return ticket.copy(status = TicketStatus.CANCELLED)
    }

    fun useTicket(ticketId: String): Ticket? {
        val ticket = ticketRepository.findById(ticketId)
            ?: throw IllegalArgumentException("Ticket not found")
        
        if (ticket.status != TicketStatus.CONFIRMED) {
            throw IllegalStateException("Ticket is not valid")
        }
        
        return ticketRepository.save(ticket.copy(status = TicketStatus.USED))
    }

    fun getUserTickets(userId: String): List<Ticket> {
        return ticketRepository.findByUserId(userId)
    }

    fun getEventTickets(eventId: String): List<Ticket> {
        return ticketRepository.findByEventId(eventId)
    }

    fun addReview(request: CreateEventReviewRequest): EventReview {
        val review = EventReview(
            id = UUID.randomUUID().toString(),
            eventId = request.eventId,
            userId = request.userId,
            rating = request.rating,
            comment = request.comment
        )
        return reviewRepository.save(review)
    }

    fun getUpcomingEvents(days: Int = 7): List<Event> {
        val now = LocalDateTime.now()
        val future = now.plusDays(days.toLong())
        return eventRepository.findByDateRange(now, future)
            .filter { it.status == "published" }
            .sortedBy { it.startDateTime }
    }

    fun getFeaturedEvents(): List<Event> {
        return eventRepository.findByStatus("published")
            .sortedByDescending { it.attendeeCount }
            .take(10)
    }
}

data class EventSearchRequest(
    val query: String? = null,
    val category: String? = null,
    val startDate: LocalDateTime? = null,
    val location: String? = null,
    val sortBy: SortOption = SortOption.DATE,
    val limit: Int = 20,
    val offset: Int = 0
)

enum class SortOption {
    DATE, POPULARITY, PRICE
}

data class CreateEventRequest(
    val organizerId: String,
    val title: String,
    val description: String,
    val category: String,
    val venue: String,
    val address: String,
    val city: String,
    val startDateTime: LocalDateTime,
    val endDateTime: LocalDateTime,
    val coverImage: String? = null,
    val maxAttendees: Int,
    val ticketTypes: List<CreateTicketTypeRequest>
)

data class CreateTicketTypeRequest(
    val name: String,
    val description: String? = null,
    val price: Double,
    val quantity: Int,
    val maxPerOrder: Int = 10
)

data class PurchaseTicketRequest(
    val ticketTypeId: String,
    val userId: String,
    val quantity: Int
)

data class CreateEventReviewRequest(
    val eventId: String,
    val userId: String,
    val rating: Int,
    val comment: String
)
