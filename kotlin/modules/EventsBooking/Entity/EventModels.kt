package eventsbooking

import java.time.LocalDate
import java.time.LocalDateTime

data class Event(
    val id: String,
    val title: String,
    val description: String,
    val category: EventCategory,
    val venue: Venue,
    val startDate: LocalDateTime,
    val endDate: LocalDateTime,
    val images: List<String>,
    val ticketTypes: List<TicketType>,
    val organizer: Organizer,
    val capacity: Int,
    val soldTickets: Int = 0,
    val rating: Double = 0.0,
    val isPublished: Boolean = true,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

enum class EventCategory {
    CONCERT, CONFERENCE, SPORTS, THEATER, EXHIBITION, WORKSHOP, FESTIVAL, NETWORKING, OTHER
}

data class Venue(
    val name: String,
    val address: String,
    val city: String,
    val capacity: Int,
    val amenities: List<String>
)

data class TicketType(
    val id: String,
    val name: String,
    val description: String,
    val price: Double,
    val quantity: Int,
    val sold: Int = 0,
    val maxPerOrder: Int = 10
)

data class Organizer(
    val id: String,
    val name: String,
    val logo: String?,
    val verified: Boolean = false
)

data class EventBooking(
    val id: String,
    val userId: String,
    val eventId: String,
    val tickets: List<BookedTicket>,
    val totalPrice: Double,
    val status: BookingStatus = BookingStatus.PENDING,
    val bookingCode: String,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

data class BookedTicket(
    val ticketTypeId: String,
    val quantity: Int,
    val pricePerTicket: Double
)
