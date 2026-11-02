package flighttickets

import java.time.LocalDate
import java.time.LocalDateTime
import java.time.LocalTime
import java.util.concurrent.ConcurrentHashMap

class FlightRepositoryImpl : FlightRepository {
    private val flights = ConcurrentHashMap<String, Flight>()

    override fun findAll(): List<Flight> = flights.values.toList()

    override fun findById(id: String): Flight? = flights[id]

    override fun searchFlights(departureCity: String, arrivalCity: String, date: LocalDate): List<Flight> {
        return flights.values.filter { flight ->
            flight.departureAirport.city.equals(departureCity, ignoreCase = true) &&
            flight.arrivalAirport.city.equals(arrivalCity, ignoreCase = true) &&
            flight.departureTime.toLocalDate() == date &&
            flight.isActive
        }
    }

    override fun findByAirline(airlineId: String): List<Flight> = 
        flights.values.filter { it.airline.id == airlineId }

    override fun findByDepartureAirportAndDate(airportCode: String, date: LocalDate): List<Flight> =
        flights.values.filter { 
            it.departureAirport.code == airportCode && 
            it.departureTime.toLocalDate() == date 
        }

    override fun findUpcoming(): List<Flight> =
        flights.values.filter { it.departureTime.isAfter(LocalDateTime.now()) }

    override fun save(flight: Flight): Flight {
        flights[flight.id] = flight
        return flight
    }

    override fun delete(id: String) {
        flights.remove(id)
    }
}

class SeatRepositoryImpl : SeatRepository {
    private val seats = ConcurrentHashMap<String, Seat>()

    override fun findAll(): List<Seat> = seats.values.toList()

    override fun findById(id: String): Seat? = seats[id]

    override fun findByFlightId(flightId: String): List<Seat> =
        seats.values.filter { it.flightId == flightId }

    override fun findByFlightIdAndSeatClass(flightId: String, seatClass: SeatClass): List<Seat> =
        seats.values.filter { it.flightId == flightId && it.seatClass == seatClass }

    override fun countAvailableByFlightAndClass(flightId: String, seatClass: SeatClass): Int =
        seats.values.count { it.flightId == flightId && it.seatClass == seatClass && it.isAvailable }

    override fun findAvailableByFlightId(flightId: String): List<Seat> =
        seats.values.filter { it.flightId == flightId && it.isAvailable }

    override fun save(seat: Seat): Seat {
        seats[seat.id] = seat
        return seat
    }

    override fun delete(id: String) {
        seats.remove(id)
    }
}

class FlightBookingRepositoryImpl : FlightBookingRepository {
    private val bookings = ConcurrentHashMap<String, FlightBooking>()

    override fun findAll(): List<FlightBooking> = bookings.values.toList()

    override fun findById(id: String): FlightBooking? = bookings[id]

    override fun findByUserId(userId: String): List<FlightBooking> =
        bookings.values.filter { it.userId == userId }

    override fun findByBookingReference(bookingReference: String): FlightBooking? =
        bookings.values.find { it.bookingReference == bookingReference }

    override fun findByUserIdOrderByCreatedAtDesc(userId: String): List<FlightBooking> =
        bookings.values.filter { it.userId == userId }
            .sortedByDescending { it.createdAt }

    override fun findByFlightAndStatus(flightId: String, status: FlightBookingStatus): List<FlightBooking> =
        bookings.values.filter { it.flightId == flightId && it.bookingStatus == status }

    override fun findByStatus(status: FlightBookingStatus): List<FlightBooking> =
        bookings.values.filter { it.bookingStatus == status }

    override fun save(booking: FlightBooking): FlightBooking {
        bookings[booking.id] = booking
        return booking
    }

    override fun delete(id: String) {
        bookings.remove(id)
    }
}

class AirlineRepositoryImpl : AirlineRepository {
    private val airlines = ConcurrentHashMap<String, Airline>()

    override fun findAll(): List<Airline> = airlines.values.toList()

    override fun findById(id: String): Airline? = airlines[id]

    override fun findByCode(code: String): Airline? =
        airlines.values.find { it.code == code }

    override fun findByNameContainingIgnoreCase(name: String): List<Airline> =
        airlines.values.filter { it.name.contains(name, ignoreCase = true) }

    override fun save(airline: Airline): Airline {
        airlines[airline.id] = airline
        return airline
    }

    override fun delete(id: String) {
        airlines.remove(id)
    }

    fun seedSampleData() {
        if (airlines.isNotEmpty()) return

        listOf(
            Airline("airline_1", "Vietnam Airlines", "VN", null),
            Airline("airline_2", "VietJet Air", "VJ", null),
            Airline("airline_3", "Bamboo Airways", "QH", null)
        ).forEach { save(it) }
    }
}

class AirportRepositoryImpl : AirportRepository {
    private val airports = ConcurrentHashMap<String, Airport>()

    override fun findAll(): List<Airport> = airports.values.toList()

    override fun findById(id: String): Airport? = airports[id]

    override fun findByCity(city: String): List<Airport> =
        airports.values.filter { it.city.equals(city, ignoreCase = true) }

    override fun findByCountry(country: String): List<Airport> =
        airports.values.filter { it.country.equals(country, ignoreCase = true) }

    override fun search(query: String): List<Airport> =
        airports.values.filter { 
            it.city.contains(query, ignoreCase = true) ||
            it.code.contains(query, ignoreCase = true) ||
            it.name.contains(query, ignoreCase = true)
        }

    override fun save(airport: Airport): Airport {
        airports[airport.code] = airport
        return airport
    }

    override fun delete(id: String) {
        airports.remove(id)
    }

    fun seedSampleData() {
        if (airports.isNotEmpty()) return

        listOf(
            Airport("SGN", "Tan Son Nhat International Airport", "Ho Chi Minh City", "Vietnam", "Asia/Ho_Chi_Minh"),
            Airport("HAN", "Noi Bai International Airport", "Hanoi", "Vietnam", "Asia/Hanoi"),
            Airport("DAD", "Da Nang International Airport", "Da Nang", "Vietnam", "Asia/Ho_Chi_Minh"),
            Airport("BKK", "Suvarnabhumi Airport", "Bangkok", "Thailand", "Asia/Bangkok"),
            Airport("SIN", "Changi Airport", "Singapore", "Singapore", "Asia/Singapore")
        ).forEach { save(it) }
    }
}