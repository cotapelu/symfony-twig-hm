package com.qcoder.events

import org.junit.Test
import org.junit.Assert.*

class EventsBookingTest {

    @Test
    fun testCreateEvent() {
        val event = Event(
            id = 1,
            name = "Music Festival",
            location = "Thu Tuong Park",
            date = "2026-05-01",
            price = 150.0,
            available = true
        )
        assertEquals("Music Festival", event.name)
        assertEquals("Thu Tuong Park", event.location)
    }

    @Test
    fun testBookEvent() {
        val booking = EventBooking(
            eventId = 1,
            attendeeId = 123,
            quantity = 2
        )
        assertNotNull(booking.id)
        assertEquals(2, booking.quantity)
    }

    @Test
    fun testEventCapacity() {
        val event = Event(
            id = 1,
            name = "Tech Conference",
            location = "Convention Center",
            date = "2026-06-15",
            price = 300.0,
            available = true,
            capacity = 500,
            booked = 450
        )
        assertTrue(event.hasAvailability(50))
        assertFalse(event.hasAvailability(100))
    }

    @Test
    fun testBookingConfirmation() {
        val booking = EventBooking(
            eventId = 1,
            attendeeId = 123,
            quantity = 3
        )
        assertEquals("confirmed", booking.status)
    }

    @Test
    fun testEventSearch() {
        val events = listOf(
            Event(1, "Food Festival", "Park", "2026-05-01", 50.0, true),
            Event(2, "Art Exhibition", "Museum", "2026-05-10", 30.0, true),
            Event(3, "Tech Talk", "Center", "2026-05-15", 0.0, true)
        )
        val freeEvents = events.filter { it.price == 0.0 }
        assertEquals(1, freeEvents.size)
    }
}

data class Event(
    val id: Long,
    val name: String,
    val location: String,
    val date: String,
    val price: Double,
    val available: Boolean,
    val capacity: Int = 1000,
    val booked: Int = 0
) {
    fun hasAvailability(count: Int): Boolean {
        return (booked + count) <= capacity
    }
}

class EventBooking(
    val id: Long = System.currentTimeMillis(),
    val eventId: Long,
    val attendeeId: Int,
    val quantity: Int,
    var status: String = "confirmed"
)
