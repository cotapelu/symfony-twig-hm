package flighttickets

import flighttickets.*

interface FlightRepository {
    fun findAll(): List<Flight>
    fun findById(id: String): Flight?
    fun searchFlights(departureCity: String, arrivalCity: String, date: LocalDate): List<Flight>
    fun findByAirline(airlineId: String): List<Flight>
    fun findByDepartureAirportAndDate(airportCode: String, date: LocalDate): List<Flight>
    fun findUpcoming(): List<Flight>
    fun save(flight: Flight): Flight
    fun delete(id: String)
}

interface SeatRepository {
    fun findAll(): List<Seat>
    fun findById(id: String): Seat?
    fun findByFlightId(flightId: String): List<Seat>
    fun findByFlightIdAndSeatClass(flightId: String, seatClass: SeatClass): List<Seat>
    fun countAvailableByFlightAndClass(flightId: String, seatClass: SeatClass): Int
    fun findAvailableByFlightId(flightId: String): List<Seat>
    fun save(seat: Seat): Seat
    fun delete(id: String)
}

interface FlightBookingRepository {
    fun findAll(): List<FlightBooking>
    fun findById(id: String): FlightBooking?
    fun findByUserId(userId: String): List<FlightBooking>
    fun findByBookingReference(bookingReference: String): FlightBooking?
    fun findByUserIdOrderByCreatedAtDesc(userId: String): List<FlightBooking>
    fun findByFlightAndStatus(flightId: String, status: FlightBookingStatus): List<FlightBooking>
    fun findByStatus(status: FlightBookingStatus): List<FlightBooking>
    fun save(booking: FlightBooking): FlightBooking
    fun delete(id: String)
}

interface AirlineRepository {
    fun findAll(): List<Airline>
    fun findById(id: String): Airline?
    fun findByCode(code: String): Airline?
    fun findByNameContainingIgnoreCase(name: String): List<Airline>
    fun save(airline: Airline): Airline
    fun delete(id: String)
}

interface AirportRepository {
    fun findAll(): List<Airport>
    fun findById(id: String): Airport?
    fun findByCity(city: String): List<Airport>
    fun findByCountry(country: String): List<Airport>
    fun search(query: String): List<Airport>
    fun save(airport: Airport): Airport
    fun delete(id: String)
}
