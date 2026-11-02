package com.qcoder.travelpackages

import org.junit.Test
import org.junit.Assert.*

class TravelPackageTest {

    @Test
    fun testCreatePackage() {
        val travelPackage = TravelPackage(
            id = 1,
            name = "Summer Vacation",
            destination = "Da Nang",
            price = 500.0,
            duration = 5,
            available = true
        )
        assertEquals("Summer Vacation", travelPackage.name)
        assertEquals("Da Nang", travelPackage.destination)
        assertEquals(500.0, travelPackage.price, 0.0)
    }

    @Test
    fun testPackageAvailability() {
        val travelPackage = TravelPackage(
            id = 1,
            name = "Weekend Getaway",
            destination = "Vung Tau",
            price = 200.0,
            duration = 2,
            available = true
        )
        assertTrue(travelPackage.available)
    }

    @Test
    fun testPackageBooking() {
        val booking = PackageBooking(
            packageId = 1,
            travelerId = 123,
            travelDate = "2026-04-01"
        )
        assertNotNull(booking.id)
        assertEquals(1, booking.packageId)
    }

    @Test
    fun testBookingCancellation() {
        val booking = PackageBooking(
            packageId = 1,
            travelerId = 123,
            travelDate = "2026-04-01"
        )
        booking.cancel()
        assertEquals("cancelled", booking.status)
    }

    @Test
    fun testPackageSearch() {
        val packages = listOf(
            TravelPackage(1, "Beach Paradise", "Phu Quoc", 800.0, 7, true),
            TravelPackage(2, "Mountain Adventure", "Sapa", 600.0, 5, true),
            TravelPackage(3, "City Tour", "Ho Chi Minh", 300.0, 3, true)
        )
        val filtered = packages.filter { it.price <= 500.0 }
        assertEquals(1, filtered.size)
    }
}

data class TravelPackage(
    val id: Long,
    val name: String,
    val destination: String,
    val price: Double,
    val duration: Int,
    val available: Boolean
)

class PackageBooking(
    val id: Long = System.currentTimeMillis(),
    val packageId: Long,
    val travelerId: Int,
    val travelDate: String,
    var status: String = "confirmed"
) {
    fun cancel() {
        status = "cancelled"
    }
}
