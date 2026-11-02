package eventsbooking

import java.time.LocalDateTime
import java.util.UUID

class EventBookingService(
    private val eventRepository: EventRepository,
    private val bookingRepository: EventBookingRepository
) {

    fun searchEvents(request: EventSearchRequest): List<Event> {
        var events = eventRepository.findPublished()

        if (request.category != null) {
            events = events.filter { it.category == request.category }
        }

        if (request.city != null) {
            events = events.filter { it.venue.city.contains(request.city, ignoreCase = true) }
        }

        if (request.startDate != null) {
            events = events.filter { it.startDate.toLocalDate() >= request.startDate }
        }

        if (request.query != null) {
            val query = request.query.lowercase()
            events = events.filter {
                it.title.lowercase().contains(query) ||
                it.description.lowercase().contains(query)
            }
        }

        return when (request.sortBy) {
            EventSortOption.DATE -> events.sortedBy { it.startDate }
            EventSortOption.PRICE -> events.sortedBy { it.ticketTypes.minOfOrNull { t -> t.price } ?: 0.0 }
            EventSortOption.POPULARITY -> events.sortedByDescending { it.soldTickets }
        }
    }

    fun getEvent(eventId: String): Event? {
        return eventRepository.findById(eventId)
    }

    fun getCategories(): List<EventCategory> {
        return EventCategory.values().toList()
    }

    fun getUpcomingEvents(): List<Event> {
        return eventRepository.findUpcoming()
            .sortedBy { it.startDate }
            .take(10)
    }

    fun createBooking(request: CreateEventBookingRequest): EventBooking {
        val event = eventRepository.findById(request.eventId)
            ?: throw IllegalArgumentException("Event not found")

        if (!event.isPublished) {
            throw IllegalStateException("Event is not available for booking")
        }

        var totalPrice = 0.0
        val bookedTickets = mutableListOf<BookedTicket>()

        for (ticketRequest in request.tickets) {
            val ticketType = event.ticketTypes.find { it.id == ticketRequest.ticketTypeId }
                ?: throw IllegalArgumentException("Ticket type not found")

            if (ticketRequest.quantity > ticketType.maxPerOrder) {
                throw IllegalArgumentException("Maximum ${ticketType.maxPerOrder} tickets per order for ${ticketType.name}")
            }

            val availableTickets = ticketType.quantity - ticketType.sold
            if (ticketRequest.quantity > availableTickets) {
                throw IllegalStateException("Not enough tickets available for ${ticketType.name}")
            }

            totalPrice += ticketType.price * ticketRequest.quantity
            bookedTickets.add(BookedTicket(
                ticketTypeId = ticketType.id,
                quantity = ticketRequest.quantity,
                pricePerTicket = ticketType.price
            ))
        }

        val booking = EventBooking(
            id = UUID.randomUUID().toString(),
            userId = request.userId,
            eventId = request.eventId,
            tickets = bookedTickets,
            totalPrice = totalPrice,
            bookingCode = generateBookingCode()
        )

        return bookingRepository.save(booking)
    }

    fun getBooking(bookingId: String): EventBooking? {
        return bookingRepository.findById(bookingId)
    }

    fun getUserBookings(userId: String): List<EventBooking> {
        return bookingRepository.findByUserId(userId)
    }

    fun cancelBooking(bookingId: String): EventBooking? {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.status == BookingStatus.CANCELLED || booking.status == BookingStatus.COMPLETED) {
            throw IllegalStateException("Booking cannot be cancelled")
        }

        return bookingRepository.save(booking.copy(status = BookingStatus.CANCELLED))
    }

    private fun generateBookingCode(): String {
        val chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"
        return (1..8).map { chars.random() }.joinToString("")
    }
}

data class EventSearchRequest(
    val category: EventCategory? = null,
    val city: String? = null,
    val startDate: java.time.LocalDate? = null,
    val query: String? = null,
    val sortBy: EventSortOption = EventSortOption.DATE
)

enum class EventSortOption {
    DATE, PRICE, POPULARITY
}

data class CreateEventBookingRequest(
    val userId: String,
    val eventId: String,
    val tickets: List<TicketRequest>
)

data class TicketRequest(
    val ticketTypeId: String,
    val quantity: Int
)

enum class BookingStatus {
    PENDING, CONFIRMED, COMPLETED, CANCELLED
}