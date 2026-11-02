package flighttickets

import java.time.LocalDate
import java.time.LocalDateTime
import java.time.LocalTime

data class Flight(
    val id: String,
    val flightNumber: String,
    val airline: Airline,
    val departureAirport: Airport,
    val arrivalAirport: Airport,
    val departureTime: LocalDateTime,
    val arrivalTime: LocalDateTime,
    val duration: Int,
    val aircraft: String,
    val isInternational: Boolean,
    val stops: Int = 0,
    val layovers: List<Layover> = emptyList(),
    val isActive: Boolean = true
)

data class Airline(
    val id: String,
    val name: String,
    val code: String,
    val logo: String?
)

data class Airport(
    val code: String,
    val name: String,
    val city: String,
    val country: String,
    val timezone: String
)

data class Layover(
    val airport: Airport,
    val duration: Int,
    val waitTime: Int
)

data class Seat(
    val id: String,
    val flightId: String,
    val seatNumber: String,
    val seatClass: SeatClass,
    val price: Double,
    val isAvailable: Boolean = true,
    val features: List<String> = emptyList()
)

enum class SeatClass {
    ECONOMY, PREMIUM_ECONOMY, BUSINESS, FIRST
}

data class FlightBooking(
    val id: String,
    val userId: String,
    val bookingReference: String,
    val flightId: String,
    val seats: List<Seat>,
    val passengers: List<Passenger>,
    val contactInfo: FlightContactInfo,
    val totalPrice: Double,
    val discount: Double = 0.0,
    val finalPrice: Double,
    val paymentStatus: FlightPaymentStatus = FlightPaymentStatus.PENDING,
    val bookingStatus: FlightBookingStatus = FlightBookingStatus.PENDING,
    val luggageInfo: FlightLuggageInfo,
    val mealPreference: String? = null,
    val specialRequests: String? = null,
    val createdAt: LocalDateTime = LocalDateTime.now()
)

data class Passenger(
    val firstName: String,
    val lastName: String,
    val dateOfBirth: LocalDate,
    val gender: String,
    val nationality: String,
    val passportNumber: String? = null,
    val passportExpiry: LocalDate? = null,
    val visaNumber: String? = null,
    val seatNumber: String
)

data class FlightContactInfo(
    val email: String,
    val phone: String,
    val address: String? = null
)

data class FlightLuggageInfo(
    val cabinBaggage: Int = 1,
    val checkedBaggage: Int = 0,
    val overweightBaggage: Int = 0
)

enum class FlightBookingStatus {
    PENDING, CONFIRMED, CANCELLED, COMPLETED, CHECKED_IN, BOARDED, DEPARTED, ARRIVED
}

enum class FlightPaymentStatus {
    PENDING, PAID, REFUNDED, FAILED
}

data class FlightSearchRequest(
    val departureCity: String,
    val arrivalCity: String,
    val departureDate: LocalDate,
    val returnDate: LocalDate? = null,
    val passengers: Int = 1,
    val seatClass: SeatClass = SeatClass.ECONOMY,
    val tripType: TripType = TripType.ONE_WAY,
    val stops: Int? = null,
    val airlines: List<String> = emptyList(),
    val minDepartureTime: LocalTime? = null,
    val maxDepartureTime: LocalTime? = null,
    val maxPrice: Double? = null
)

enum class TripType {
    ONE_WAY, ROUND_TRIP, MULTI_CITY
}

data class FlightSearchResult(
    val outboundFlights: List<FlightWithPrice>,
    val returnFlights: List<FlightWithPrice>? = null,
    val totalPrice: Double
)

data class FlightWithPrice(
    val flight: Flight,
    val availableSeats: Map<SeatClass, Int>,
    val prices: Map<SeatClass, Double>
)

data class CheckInInfo(
    val bookingId: String,
    val passengerIndex: Int,
    val seatNumber: String,
    val checkInTime: LocalDateTime = LocalDateTime.now()
)

data class BoardingPass(
    val bookingId: String,
    val flightNumber: String,
    val passengerName: String,
    val seatNumber: String,
    val gate: String,
    val boardingTime: LocalDateTime,
    val terminal: String
)
