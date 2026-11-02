package com.qcoder.booking

import org.junit.Test
import org.junit.Assert.*

class FlightBookingTest {
    
    @Test
    fun testSearchFlights() {
        val flights = listOf(
            Flight("FL001", "SGN", "HAN", 150.0),
            Flight("FL002", "SGN", "HCM", 120.0)
        )
        assertEquals(2, flights.size)
    }
    
    @Test
    fun testBookFlight() {
        val booking = FlightBooking(
            flightId = "FL001",
            passengerId = 123,
            seatClass = "economy"
        )
        assertNotNull(booking.id)
    }
    
    @Test
    fun testCancelFlight() {
        val booking = FlightBooking(
            flightId = "FL001",
            passengerId = 123,
            seatClass = "economy"
        )
        val result = booking.cancel()
        assertTrue(result)
    }
    
    @Test
    fun testSelectSeat() {
        val booking = FlightBooking(
            flightId = "FL001",
            passengerId = 123,
            seatClass = "economy"
        )
        booking.selectSeat("12A")
        assertEquals("12A", booking.seat)
    }
}

data class Flight(
    val id: String,
    val from: String,
    val to: String,
    val price: Double
)

class FlightBooking(
    val id: Long = System.currentTimeMillis(),
    val flightId: String,
    val passengerId: Int,
    val seatClass: String,
    var seat: String? = null,
    var status: String = "pending"
) {
    fun cancel(): Boolean { status = "cancelled"; return true }
    fun selectSeat(seatNumber: String) { seat = seatNumber }
}
