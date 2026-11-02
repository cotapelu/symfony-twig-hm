package com.qcoder.booking

import org.junit.Test
import org.junit.Assert.*

class AppointmentBookingTest {
    
    @Test
    fun testCreateAppointment() {
        val booking = AppointmentBooking(patientId = 1, doctorId = 2, timeSlot = "2024-01-15 10:00")
        assertNotNull(booking.id)
        assertEquals(1, booking.patientId)
        assertEquals(2, booking.doctorId)
    }
    
    @Test
    fun testCancelAppointment() {
        val booking = AppointmentBooking(patientId = 1, doctorId = 2, timeSlot = "2024-01-15 10:00")
        val result = booking.cancel()
        assertTrue(result)
    }
    
    @Test
    fun testRescheduleAppointment() {
        val booking = AppointmentBooking(patientId = 1, doctorId = 2, timeSlot = "2024-01-15 10:00")
        booking.reschedule("2024-01-16 14:00")
        assertEquals("2024-01-16 14:00", booking.timeSlot)
    }
    
    @Test
    fun testConfirmAppointment() {
        val booking = AppointmentBooking(patientId = 1, doctorId = 2, timeSlot = "2024-01-15 10:00")
        booking.confirm()
        assertEquals("confirmed", booking.status)
    }
}

class AppointmentBooking(
    val id: Long = System.currentTimeMillis(),
    val patientId: Int,
    val doctorId: Int,
    var timeSlot: String,
    var status: String = "pending"
) {
    fun cancel(): Boolean {
        status = "cancelled"
        return true
    }
    
    fun reschedule(newTimeSlot: String) {
        timeSlot = newTimeSlot
    }
    
    fun confirm() {
        status = "confirmed"
    }
}
