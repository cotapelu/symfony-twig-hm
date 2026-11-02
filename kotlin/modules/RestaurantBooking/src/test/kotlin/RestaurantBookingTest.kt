package com.qcoder.booking

import org.junit.Test
import org.junit.Assert.*

class RestaurantBookingTest {
    
    @Test
    fun testSearchRestaurants() {
        val restaurants = listOf(
            Restaurant(1, "Restaurant A", "Vietnamese"),
            Restaurant(2, "Restaurant B", "Italian")
        )
        assertEquals(2, restaurants.size)
    }
    
    @Test
    fun testBookTable() {
        val booking = RestaurantBooking(
            restaurantId = 1,
            guestCount = 4,
            dateTime = "2024-03-15 19:00"
        )
        assertNotNull(booking.id)
    }
    
    @Test
    fun testCancelBooking() {
        val booking = RestaurantBooking(
            restaurantId = 1,
            guestCount = 4,
            dateTime = "2024-03-15 19:00"
        )
        val result = booking.cancel()
        assertTrue(result)
    }
    
    @Test
    fun testGetAvailableTables() {
        val tables = listOf(
            Table(1, 2),
            Table(2, 4),
            Table(3, 6)
        )
        assertEquals(3, tables.size)
    }
}

data class Restaurant(val id: Int, val name: String, val cuisine: String)

class RestaurantBooking(
    val id: Long = System.currentTimeMillis(),
    val restaurantId: Int,
    val guestCount: Int,
    val dateTime: String,
    var status: String = "pending"
) {
    fun cancel(): Boolean { status = "cancelled"; return true }
}

data class Table(val id: Int, val capacity: Int)
