package eventsbooking

import java.time.LocalDateTime
import java.util.concurrent.ConcurrentHashMap

class EventRepository {
    private val events = ConcurrentHashMap<String, Event>()

    fun findAll(): List<Event> = events.values.toList()

    fun findById(id: String): Event? = events[id]

    fun findPublished(): List<Event> = events.values.filter { it.isPublished }

    fun findByCategory(category: EventCategory): List<Event> =
        events.values.filter { it.category == category && it.isPublished }

    fun findByCity(city: String): List<Event> =
        events.values.filter { it.venue.city.contains(city, ignoreCase = true) && it.isPublished }

    fun findUpcoming(): List<Event> =
        events.values.filter { it.startDate.isAfter(LocalDateTime.now()) && it.isPublished }

    fun save(event: Event): Event {
        events[event.id] = event
        return event
    }

    fun seedSampleData() {
        if (events.isNotEmpty()) return

        listOf(
            Event(
                id = "event_1",
                title = "Ho Chi Minh City Music Festival",
                description = "Annual music festival featuring local and international artists",
                category = EventCategory.FESTIVAL,
                venue = Venue(
                    name = "District 2 Park",
                    address = "Thu Thiem New Urban Area",
                    city = "Ho Chi Minh City",
                    capacity = 10000,
                    amenities = listOf("Parking", "Food Court", "Restrooms", "First Aid")
                ),
                startDate = LocalDateTime.now().plusDays(30),
                endDate = LocalDateTime.now().plusDays(32),
                images = listOf("event1_1.jpg", "event1_2.jpg"),
                ticketTypes = listOf(
                    TicketType("ticket_1_1", "General Admission", "Standard entry", 25.0, 5000, 0, 10),
                    TicketType("ticket_1_2", "VIP Pass", "VIP access with exclusive area", 75.0, 500, 0, 4)
                ),
                organizer = Organizer("org_1", "Live Nation Vietnam", null, true),
                capacity = 10000,
                soldTickets = 3500,
                rating = 4.7
            ),
            Event(
                id = "event_2",
                title = "Tech Summit 2024",
                description = "Southeast Asia's biggest technology conference",
                category = EventCategory.CONFERENCE,
                venue = Venue(
                    name = "SECC",
                    address = "799 Nguyen Van Linh",
                    city = "Ho Chi Minh City",
                    capacity = 3000,
                    amenities = listOf("Parking", "WiFi", "Catering", "Translation Services")
                ),
                startDate = LocalDateTime.now().plusDays(60),
                endDate = LocalDateTime.now().plusDays(61),
                images = listOf("event2_1.jpg"),
                ticketTypes = listOf(
                    TicketType("ticket_2_1", "Early Bird", "Limited early bird pricing", 99.0, 500, 0, 5),
                    TicketType("ticket_2_2", "Regular", "Full conference access", 199.0, 1500, 0, 5),
                    TicketType("ticket_2_3", "VIP", "VIP with networking dinner", 499.0, 200, 0, 2)
                ),
                organizer = Organizer("org_2", "TechHub Vietnam", null, true),
                capacity = 3000,
                soldTickets = 1200,
                rating = 4.8
            ),
            Event(
                id = "event_3",
                title = "Broadway Night: Phantom of the Opera",
                description = "World-class musical theater performance",
                category = EventCategory.THEATER,
                venue = Venue(
                    name = "Saigon Opera House",
                    address = "7 Lam Son Square",
                    city = "Ho Chi Minh City",
                    capacity = 800,
                    amenities = listOf("Parking", "Wheelchair Accessible", "Concessions")
                ),
                startDate = LocalDateTime.now().plusDays(15),
                endDate = LocalDateTime.now().plusDays(15).plusHours(3),
                images = listOf("event3_1.jpg"),
                ticketTypes = listOf(
                    TicketType("ticket_3_1", "Balcony", "Balcony seating", 45.0, 300, 0, 8),
                    TicketType("ticket_3_2", "Orchestra", "Orchestra seating", 85.0, 400, 0, 8),
                    TicketType("ticket_3_3", "VIP Box", "Private box with champagne", 200.0, 50, 0, 4)
                ),
                organizer = Organizer("org_3", "Vietnam Arts Council", null, false),
                capacity = 800,
                soldTickets = 600,
                rating = 4.9
            )
        ).forEach { save(it) }
    }
}

class EventBookingRepository {
    private val bookings = ConcurrentHashMap<String, EventBooking>()

    fun findById(id: String): EventBooking? = bookings[id]

    fun findByUserId(userId: String): List<EventBooking> =
        bookings.values.filter { it.userId == userId }.sortedByDescending { it.createdAt }

    fun findByEventId(eventId: String): List<EventBooking> =
        bookings.values.filter { it.eventId == eventId }

    fun save(booking: EventBooking): EventBooking {
        bookings[booking.id] = booking
        return booking
    }
}