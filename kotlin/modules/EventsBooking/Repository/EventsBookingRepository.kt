package eventsbooking

import java.time.LocalDateTime

interface EventRepository {
    fun findById(id: String): Event?
    fun findByStatus(status: String): List<Event>
    fun findByDateRange(start: LocalDateTime, end: LocalDateTime): List<Event>
    fun save(event: Event): Event
    fun updateStatus(id: String, status: String)
    fun updateMinTicketPrice(id: String, price: Double)
    fun incrementAttendeeCount(id: String, count: Int)
    fun decrementAttendeeCount(id: String, count: Int)
}

interface TicketRepository {
    fun findById(id: String): Ticket?
    fun findByEventId(eventId: String): List<Ticket>
    fun findByUserId(userId: String): List<Ticket>
    fun save(ticket: Ticket): Ticket
    fun updateStatus(id: String, status: TicketStatus)
    fun updateSold(ticketTypeId: String, sold: Int)
}

interface TicketTypeRepository {
    fun findById(id: String): TicketType?
    fun findByEventId(eventId: String): List<TicketType>
    fun save(ticketType: TicketType): TicketType
}

interface EventReviewRepository {
    fun findById(id: String): EventReview?
    fun findByEventId(eventId: String): List<EventReview>
    fun save(review: EventReview): EventReview
}

enum class TicketStatus {
    AVAILABLE, PENDING, CONFIRMED, USED, CANCELLED, SOLD_OUT
}

data class Ticket(
    val id: String,
    val ticketTypeId: String,
    val eventId: String,
    val userId: String,
    val quantity: Int,
    val totalPrice: Double,
    val status: TicketStatus,
    val qrCode: String,
    val purchasedAt: LocalDateTime = LocalDateTime.now()
)

data class TicketType(
    val id: String,
    val eventId: String,
    val name: String,
    val description: String?,
    val price: Double,
    val quantity: Int,
    val sold: Int,
    val maxPerOrder: Int
)

data class EventReview(
    val id: String,
    val eventId: String,
    val userId: String,
    val rating: Int,
    val comment: String,
    val createdAt: LocalDateTime = LocalDateTime.now()
)
