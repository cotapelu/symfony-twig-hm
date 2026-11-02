package flighttickets

import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import java.time.LocalDate
import java.time.LocalTime

class FlightBookingServiceTest {

    private val flightRepo = FakeFlightRepository()
    private val seatRepo = FakeSeatRepository()
    private val bookingRepo = FakeFlightBookingRepository()
    private val airlineRepo = FakeAirlineRepository()
    private val airportRepo = FakeAirportRepository()
    private val service = FlightBookingService(
        flightRepo, seatRepo, bookingRepo, airlineRepo, airportRepo
    )

    @Test
    fun `searchFlights should return matching flights`() {
        val airline = Airline(id = "a1", name = "Sky", code = "SK")
        airlineRepo.save(airline)
        val dep = Airport(id = "d1", code = "SGN", name = "Saigon")
        val arr = Airport(id = "a2", code = "HAN", name = "Hanoi")
        airportRepo.save(dep)
        airportRepo.save(arr)
        val flight = Flight(
            id = "f1",
            airlineId = "a1",
            departureAirportId = "d1",
            arrivalAirportId = "a2",
            departureTime = LocalTime.now(),
            arrivalTime = LocalTime.now().plusHours(2),
            date = LocalDate.now(),
            basePrice = 100.0
        )
        flightRepo.save(flight)

        val results = service.searchFlights(
            from = "SGN", to = "HAN", date = LocalDate.now()
        )
        assertEquals(1, results.size)
        assertEquals("f1", results[0].id)
    }

    @Test
    fun `getSeatMap should return available and occupied seats`() {
        val flight = Flight(id = "f2", basePrice = 50.0)
        flightRepo.save(flight)
        seatRepo.saveAll(listOf(
            Seat(id = "s1", flightId = "f2", row = 1, col = "A", isOccupied = false),
            Seat(id = "s2", flightId = "f2", row = 1, col = "B", isOccupied = true)
        ))

        val map = service.getSeatMap("f2")
        assertEquals(2, map.seats.size)
        assertEquals(1, map.available.size)
        assertEquals(1, map.occupied.size)
    }

    @Test
    fun `createBooking should fail if seats not available`() {
        val flight = Flight(id = "f3", basePrice = 80.0)
        flightRepo.save(flight)
        seatRepo.save(Seat(id = "s3", flightId = "f3", row = 2, col = "A", isOccupied = false))
        seatRepo.save(Seat(id = "s4", flightId = "f3", row = 2, col = "B", isOccupied = false))

        val ex = assertThrows<IllegalStateException> {
            service.createBooking(
                userId = "u1",
                flightId = "f3",
                seatIds = listOf("s3", "s4", "s999"),
                passengerName = "John"
            )
        }
        assertTrue(ex.message!!.contains("not available"))
    }

    @Test
    fun `createBooking should succeed and mark seats occupied`() {
        val flight = Flight(id = "f4", basePrice = 100.0)
        flightRepo.save(flight)
        seatRepo.save(Seat(id = "s5", flightId = "f4", row = 3, col = "A", isOccupied = false))
        seatRepo.save(Seat(id = "s6", flightId = "f4", row = 3, col = "B", isOccupied = false))

        val booking = service.createBooking(
            userId = "u1",
            flightId = "f4",
            seatIds = listOf("s5", "s6"),
            passengerName = "Jane"
        )

        assertNotNull(booking.id)
        assertEquals(2, booking.seatIds.size)
        assertTrue(seatRepo.findById("s5")!!.isOccupied)
        assertTrue(seatRepo.findById("s6")!!.isOccupied)
    }

    @Test
    fun `confirmBooking should update status and charge payment`() {
        val booking = FlightBooking(
            id = "fb1",
            userId = "u1",
            flightId = "f5",
            seatIds = listOf("s7"),
            totalAmount = 200.0,
            status = BookingStatus.PENDING
        )
        flightRepo.save(Flight(id = "f5", basePrice = 200.0))
        seatRepo.save(Seat(id = "s7", flightId = "f5", row = 4, col = "A", isOccupied = false))
        bookingRepo.save(booking)

        val confirmed = service.confirmBooking("fb1")

        assertEquals(BookingStatus.CONFIRMED, confirmed.status)
        assertTrue(confirmed.paidAt != null)
    }

    // Fake repos
    private class FakeFlightRepository : FlightRepository {
        private val flights = mutableMapOf<String, Flight>()
        override fun findAll(): List<Flight> = flights.values.toList()
        override fun findById(id: String): Flight? = flights[id]
        override fun save(flight: Flight): Flight { flights[flight.id] = flight; return flight }
        override fun delete(id: String) { flights.remove(id) }
        // stub methods
        override fun findByDate(date: LocalDate): List<Flight> = flights.values.filter { it.date == date }.toList()
        override fun findByRoute(origin: String, dest: String, date: LocalDate): List<Flight> = emptyList()
        override fun searchFlights(from: String, to: String, date: LocalDate): List<Flight> = emptyList()
    }

    private class FakeSeatRepository : SeatRepository {
        private val seats = mutableMapOf<String, Seat>()
        override fun findAll(): List<Seat> = seats.values.toList()
        override fun findById(id: String): Seat? = seats[id]
        override fun save(seat: Seat): Seat { seats[seat.id] = seat; return seat }
        override fun saveAll(seatList: List<Seat>) { seatList.forEach { seats[it.id] = it } }
        override fun delete(id: String) { seats.remove(id) }
        override fun findByFlightId(flightId: String): List<Seat> = seats.values.filter { it.flightId == flightId }.toList()
        override fun findByFlightIdAndOccupied(flightId: String, occupied: Boolean): List<Seat> = findByFlightId(flightId).filter { it.isOccupied == occupied }
    }

    private class FakeFlightBookingRepository : FlightBookingRepository {
        private val bookings = mutableMapOf<String, FlightBooking>()
        override fun findAll(): List<FlightBooking> = bookings.values.toList()
        override fun findById(id: String): FlightBooking? = bookings[id]
        override fun save(booking: FlightBooking): FlightBooking { bookings[booking.id] = booking; return booking }
        override fun delete(id: String) { bookings.remove(id) }
        override fun findByUserId(userId: String): List<FlightBooking> = bookings.values.filter { it.userId == userId }.toList()
        override fun findByFlightId(flightId: String): List<FlightBooking> = bookings.values.filter { it.flightId == flightId }.toList()
        override fun findByStatus(status: BookingStatus): List<FlightBooking> = bookings.values.filter { it.status == status }.toList()
    }

    private class FakeAirlineRepository : AirlineRepository {
        private val airlines = mutableMapOf<String, Airline>()
        override fun findAll(): List<Airline> = airlines.values.toList()
        override fun findById(id: String): Airline? = airlines[id]
        override fun save(airline: Airline): Airline { airlines[airline.id] = airline; return airline }
        override fun delete(id: String) { airlines.remove(id) }
    }

    private class FakeAirportRepository : AirportRepository {
        private val airports = mutableMapOf<String, Airport>()
        override fun findAll(): List<Airport> = airports.values.toList()
        override fun findById(id: String): Airport? = airports[id]
        override fun save(airport: Airport): Airport { airports[airport.id] = airport; return airport }
        override fun delete(id: String) { airports.remove(id) }
        override fun findByCode(code: String): Airport? = airports.values.find { it.code == code }
    }
}
