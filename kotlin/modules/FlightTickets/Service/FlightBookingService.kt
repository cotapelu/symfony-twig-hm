package flighttickets

import flighttickets.*
import java.time.LocalDate
import java.time.LocalDateTime
import java.util.UUID

class FlightBookingService(
    private val flightRepository: FlightRepository,
    private val seatRepository: SeatRepository,
    private val bookingRepository: FlightBookingRepository,
    private val airlineRepository: AirlineRepository,
    private val airportRepository: AirportRepository
) {

    fun searchAirports(query: String): List<Airport> {
        return airportRepository.search(query)
    }

    fun searchFlights(request: FlightSearchRequest): FlightSearchResult {
        val outboundFlights = flightRepository.searchFlights(
            request.departureCity,
            request.arrivalCity,
            request.departureDate
        ).filter { flight ->
            (request.stops == null || flight.stops <= request.stops) &&
            (request.airlines.isEmpty() || request.airlines.contains(flight.airline.id))
        }.map { flight ->
            val prices = getPrices(flight.id, request.seatClass)
            FlightWithPrice(flight, getAvailableSeats(flight.id), prices)
        }.filter { fp -> (fp.availableSeats[request.seatClass] ?: 0) > 0 }

        var totalPrice = outboundFlights.minOfOrNull { it.prices[request.seatClass] ?: Double.MAX_VALUE } ?: 0.0

        val returnFlights = if (request.returnDate != null && request.tripType == TripType.ROUND_TRIP) {
            val returnResults = flightRepository.searchFlights(
                request.arrivalCity,
                request.departureCity,
                request.returnDate
            ).map { flight ->
                val prices = getPrices(flight.id, request.seatClass)
                FlightWithPrice(flight, getAvailableSeats(flight.id), prices)
            }
            val minReturnPrice = returnResults.minOfOrNull { it.prices[request.seatClass] ?: Double.MAX_VALUE } ?: 0.0
            totalPrice += minReturnPrice
            returnResults
        } else null

        return FlightSearchResult(outboundFlights, returnFlights, totalPrice)
    }

    private fun getPrices(flightId: String, requestedClass: SeatClass): Map<SeatClass, Double> {
        val seats = seatRepository.findByFlightId(flightId)
        val prices = mutableMapOf<SeatClass, Double>()

        SeatClass.values().forEach { seatClass ->
            val seat = seats.find { it.seatClass == seatClass }
            prices[seatClass] = seat?.price ?: 0.0
        }

        return prices
    }

    private fun getAvailableSeats(flightId: String): Map<SeatClass, Int> {
        val available = seatRepository.findAvailableByFlightId(flightId)
        val counts = mutableMapOf<SeatClass, Int>()

        SeatClass.values().forEach { counts[it] = 0 }
        available.forEach { counts[it.seatClass] = (counts[it.seatClass] ?: 0) + 1 }

        return counts
    }

    fun getFlight(flightId: String): Flight? {
        return flightRepository.findById(flightId)
    }

    fun getAvailableSeats(flightId: String, seatClass: SeatClass): List<Seat> {
        return seatRepository.findByFlightIdAndSeatClass(flightId, seatClass).filter { it.isAvailable }
    }

    fun createBooking(
        userId: String,
        flightId: String,
        seats: List<String>,
        passengers: List<Passenger>,
        contactInfo: FlightContactInfo,
        luggageInfo: FlightLuggageInfo,
        mealPreference: String?,
        specialRequests: String?
    ): FlightBooking {
        val flight = flightRepository.findById(flightId)
            ?: throw IllegalArgumentException("Flight not found")

        val seatObjects = seats.mapNotNull { seatId ->
            seatRepository.findById(seatId)
        }

        if (seatObjects.size != seats.size) {
            throw IllegalArgumentException("Some seats are not available")
        }

        if (!seatObjects.all { it.isAvailable }) {
            throw IllegalStateException("Some seats are already booked")
        }

        val totalPrice = seatObjects.sumOf { it.price }

        val booking = FlightBooking(
            id = UUID.randomUUID().toString(),
            userId = userId,
            bookingReference = generateBookingReference(),
            flightId = flightId,
            seats = seatObjects,
            passengers = passengers,
            contactInfo = contactInfo,
            totalPrice = totalPrice,
            finalPrice = totalPrice,
            luggageInfo = luggageInfo,
            mealPreference = mealPreference,
            specialRequests = specialRequests
        )

        seatObjects.forEach { seat ->
            seatRepository.save(seat.copy(isAvailable = false))
        }

        return bookingRepository.save(booking)
    }

    private fun generateBookingReference(): String {
        val chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
        return (1..6).map { chars.random() }.joinToString("")
    }

    fun getBooking(bookingId: String): FlightBooking? {
        return bookingRepository.findById(bookingId)
    }

    fun getBookingByReference(reference: String): FlightBooking? {
        return bookingRepository.findByBookingReference(reference)
    }

    fun getUserBookings(userId: String): List<FlightBooking> {
        return bookingRepository.findByUserIdOrderByCreatedAtDesc(userId)
    }

    fun confirmBooking(bookingId: String): FlightBooking? {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.bookingStatus != FlightBookingStatus.PENDING) {
            throw IllegalStateException("Booking cannot be confirmed")
        }

        return bookingRepository.save(booking.copy(
            bookingStatus = FlightBookingStatus.CONFIRMED,
            paymentStatus = FlightPaymentStatus.PAID
        ))
    }

    fun cancelBooking(bookingId: String): FlightBooking? {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.bookingStatus == FlightBookingStatus.CANCELLED ||
            booking.bookingStatus == FlightBookingStatus.CHECKED_IN) {
            throw IllegalStateException("Booking cannot be cancelled")
        }

        val cancelledBooking = booking.copy(
            bookingStatus = FlightBookingStatus.CANCELLED,
            paymentStatus = FlightPaymentStatus.REFUNDED
        )

        cancelledBooking.seats.forEach { seat ->
            seatRepository.save(seat.copy(isAvailable = true))
        }

        return bookingRepository.save(cancelledBooking)
    }

    fun checkIn(bookingId: String, passengerIndex: Int, seatNumber: String): CheckInInfo {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.bookingStatus != FlightBookingStatus.CONFIRMED) {
            throw IllegalStateException("Booking must be confirmed before check-in")
        }

        val flight = flightRepository.findById(booking.flightId)
        val now = LocalDateTime.now()
        val checkInOpens = flight?.departureTime?.minusHours(24) ?: now

        if (now.isBefore(checkInOpens)) {
            throw IllegalStateException("Check-in opens 24 hours before departure")
        }

        booking.passengers.getOrNull(passengerIndex)?.let {
            bookingRepository.save(booking.copy(bookingStatus = FlightBookingStatus.CHECKED_IN))
        }

        return CheckInInfo(bookingId, passengerIndex, seatNumber)
    }

    fun getBoardingPass(bookingId: String, passengerIndex: Int): BoardingPass? {
        val booking = bookingRepository.findById(bookingId) ?: return null
        val flight = flightRepository.findById(booking.flightId) ?: return null
        val passenger = booking.passengers.getOrNull(passengerIndex) ?: return null

        return BoardingPass(
            bookingId = bookingId,
            flightNumber = flight.flightNumber,
            passengerName = "${passenger.firstName} ${passenger.lastName}",
            seatNumber = passenger.seatNumber,
            gate = "A${(1..20).random()}",
            boardingTime = flight.departureTime.minusMinutes(45),
            terminal = "T${(1..3).random()}"
        )
    }

    fun getAirlines(): List<Airline> {
        return airlineRepository.findAll()
    }

    fun getAirports(): List<Airport> {
        return airportRepository.findAll()
    }
}
