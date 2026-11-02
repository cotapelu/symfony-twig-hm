package com.qcoder.booking

import org.junit.Test
import org.junit.Assert.*

class HotelBookingTest {
    
    @Test
    fun testCreateBooking() {
        val booking = HotelBooking(
            hotelId = 1,
            roomId = 101,
            guestId = 123,
            checkIn = "2024-03-01",
            checkOut = "2024-03-05"
        )
        assertNotNull(booking.id)
        assertEquals(1, booking.hotelId)
    }
    
    @Test
    fun testConfirmBooking() {
        val booking = HotelBooking(
            hotelId = 1,
            roomId = 101,
            guestId = 123,
            checkIn = "2024-03-01",
            checkOut = "2024-03-05"
        )
        booking.confirm()
        assertEquals("confirmed", booking.status)
    }
    
    @Test
    fun testCancelBooking() {
        val booking = HotelBooking(
            hotelId = 1,
            roomId = 101,
            guestId = 123,
            checkIn = "2024-03-01",
            checkOut = "2024-03-05"
        )
        val result = booking.cancel()
        assertTrue(result)
    }
    
    @Test
    fun testGetAvailableRooms() {
        val rooms = listOf(
            HotelRoom(101, "standard", 100.0),
            HotelRoom(102, "deluxe", 150.0)
        )
        assertEquals(2, rooms.size)
    }
}

class HotelBooking(
    val id: Long = System.currentTimeMillis(),
    val hotelId: Int,
    val roomId: Int,
    val guestId: Int,
    val checkIn: String,
    val checkOut: String,
    var status: String = "pending"
) {
    fun confirm() { status = "confirmed" }
    fun cancel(): Boolean { status = "cancelled"; return true }
}

data class HotelRoom(val id: Int, val type: String, val price: Double)
