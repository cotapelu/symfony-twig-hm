package com.example.parking

import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import java.time.Instant

class ParkingServiceTest {

    private val service = ParkingService()

    @Test
    fun `createSpot should create parking spot with id`() {
        val spot = service.createSpot(
            location = "Zone A",
            spotNumber = "001",
            spotType = "CAR",
            hourlyRate = 5.0
        )
        assertNotNull(spot.id)
        assertEquals("Zone A", spot.location)
        assertEquals("001", spot.spotNumber)
        assertEquals("CAR", spot.spotType)
        assertEquals(5.0, spot.hourlyRate)
    }

    @Test
    fun `createSpot should validate required fields`() {
        assertThrows<IllegalArgumentException> {
            service.createSpot("", "001", "CAR", 5.0)
        }
    }

    @Test
    fun `reserveSpot should reserve spot for vehicle`() {
        val spot = service.createSpot("Zone B", "002", "CAR", 10.0)
        val reservation = service.reserveSpot(spot.id!!, "plate123", Instant.now().plusSeconds(3600))
        assertNotNull(reservation)
        assertEquals(spot.id, reservation.spotId)
        assertEquals("plate123", reservation.vehiclePlate)
        assertEquals("RESERVED", reservation.status)
    }

    @Test
    fun `reserveSpot should fail for already reserved spot`() {
        val spot = service.createSpot("Zone C", "003", "CAR", 8.0)
        service.reserveSpot(spot.id!!, "car1", Instant.now().plusSeconds(3600))
        val ex = assertThrows<IllegalStateException> {
            service.reserveSpot(spot.id!!, "car2", Instant.now().plusSeconds(3600))
        }
        assertTrue(ex.message!!.contains("already reserved"))
    }

    @Test
    fun `checkIn should update reservation to occupied`() {
        val spot = service.createSpot("Zone D", "004", "CAR", 12.0)
        val reservation = service.reserveSpot(spot.id!!, "carX", Instant.now().plusSeconds(7200))
        val occupied = service.checkIn(reservation.id!!)
        assertNotNull(occupied)
        assertEquals("OCCUPIED", occupied!!.status)
    }

    @Test
    fun `checkOut should calculate fee and free spot`() {
        val spot = service.createSpot("Zone E", "005", "CAR", 15.0)
        val reservation = service.reserveSpot(spot.id!!, "carY", Instant.now().minusSeconds(3600))
        val occupied = service.checkIn(reservation.id!!)
        val receipt = service.checkOut(occupied!!.id!!)
        assertNotNull(receipt)
        assertTrue(receipt!!.totalFee > 0)
        // Spot should become available
        val updatedSpot = service.getSpot(spot.id!!)
        assertNull(updatedSpot!!.currentReservationId)
    }

    @Test
    fun `getAvailableSpots should list spots not reserved`() {
        val spot1 = service.createSpot("Zone F", "006", "CAR", 10.0)
        val spot2 = service.createSpot("Zone F", "007", "CAR", 10.0)
        service.reserveSpot(spot1.id!!, "carA", Instant.now().plusSeconds(3600))

        val available = service.getAvailableSpots("Zone F")
        assertEquals(1, available.size)
        assertEquals(spot2.id, available[0].id)
    }

    @Test
    fun `extendReservation should extend duration`() {
        val spot = service.createSpot("Zone G", "008", "CAR", 20.0)
        val reservation = service.reserveSpot(spot.id!!, "carB", Instant.now().plusSeconds(1800))
        val extended = service.extendReservation(reservation.id!!, 3600)
        assertNotNull(extended)
        val newExpiry = extended!!.expiresAt!!
        assertTrue(newExpiry.isAfter(Instant.now().plusSeconds(3500)))
    }
}
