package hotelbooking

import hotelbooking.*
import java.time.temporal.ChronoUnit
import java.util.UUID

class HotelBookingService(
    private val hotelRepository: HotelRepository,
    private val roomTypeRepository: RoomTypeRepository,
    private val roomRepository: RoomRepository,
    private val bookingRepository: BookingRepository,
    private val reviewRepository: ReviewRepository
) {

    fun searchHotels(request: SearchRequest): List<Hotel> {
        var hotels = mutableListOf<Hotel>()

        if (request.city != null) {
            hotels.addAll(hotelRepository.findActiveByCity(request.city))
        } else if (request.country != null) {
            hotels.addAll(hotelRepository.findByCountry(request.country))
        } else {
            hotels.addAll(hotelRepository.findAll().filter { it.isActive })
        }

        hotels = hotels.filter { hotel ->
            (request.starRating == null || hotel.starRating >= request.starRating) &&
            request.amenities.all { amenity -> hotel.amenities.contains(amenity) }
        }.toMutableList()

        return when (request.sortBy) {
            SortOption.PRICE -> hotels.sortedBy { it.policies.cancellationPolicy.hashCode() }
            SortOption.RATING -> hotels.sortedByDescending { it.starRating }
            SortOption.POPULARITY -> hotels.sortedByDescending { it.starRating }
            SortOption.DISTANCE -> hotels
        }
    }

    fun getHotel(hotelId: String): Hotel? {
        return hotelRepository.findById(hotelId)
    }

    fun getHotelRoomTypes(hotelId: String): List<RoomType> {
        return roomTypeRepository.findByHotelId(hotelId)
    }

    fun getRoomType(roomTypeId: String): RoomType? {
        return roomTypeRepository.findById(roomTypeId)
    }

    fun checkAvailability(hotelId: String, checkIn: LocalDate, checkOut: LocalDate, rooms: Int): List<RoomType> {
        val roomTypes = roomTypeRepository.findAvailableByHotelId(hotelId)
        return roomTypes.filter { rt ->
            val days = ChronoUnit.DAYS.between(checkIn, checkOut).toInt()
            val totalAvailable = countAvailableRooms(rt.id, checkIn, checkOut)
            totalAvailable >= rooms
        }
    }

    private fun countAvailableRooms(roomTypeId: String, checkIn: LocalDate, checkOut: LocalDate): Int {
        val bookings = bookingRepository.findAll().filter { b ->
            b.roomTypeId == roomTypeId &&
            b.checkInDate < checkOut &&
            b.checkOutDate > checkIn &&
            b.bookingStatus != BookingStatus.CANCELLED
        }
        val roomType = roomTypeRepository.findById(roomTypeId)
        return (roomType?.availableRooms ?: 0) - bookings.size
    }

    fun createBooking(
        userId: String,
        hotelId: String,
        roomTypeId: String,
        checkIn: LocalDate,
        checkOut: LocalDate,
        guestCount: Int,
        guestInfo: GuestInfo,
        specialRequests: String?
    ): Booking {
        val roomType = roomTypeRepository.findById(roomTypeId)
            ?: throw IllegalArgumentException("Room type not found")

        if (roomType.availableRooms <= 0) {
            throw IllegalStateException("No rooms available")
        }

        if (guestCount > roomType.maxOccupancy) {
            throw IllegalArgumentException("Room can accommodate maximum ${roomType.maxOccupancy} guests")
        }

        val days = ChronoUnit.DAYS.between(checkIn, checkOut).toInt()
        val totalPrice = roomType.basePrice * days

        val booking = Booking(
            id = UUID.randomUUID().toString(),
            userId = userId,
            hotelId = hotelId,
            roomTypeId = roomTypeId,
            checkInDate = checkIn,
            checkOutDate = checkOut,
            guestCount = guestCount,
            guestInfo = guestInfo,
            rooms = emptyList(),
            totalPrice = totalPrice,
            finalPrice = totalPrice,
            specialRequests = specialRequests
        )

        return bookingRepository.save(booking)
    }

    fun getBooking(bookingId: String): Booking? {
        return bookingRepository.findById(bookingId)
    }

    fun getUserBookings(userId: String): List<Booking> {
        return bookingRepository.findByUserIdOrderByCreatedAtDesc(userId)
    }

    fun getHotelBookings(hotelId: String): List<Booking> {
        return bookingRepository.findByHotelId(hotelId)
    }

    fun confirmBooking(bookingId: String): Booking? {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.bookingStatus != BookingStatus.PENDING) {
            throw IllegalStateException("Booking cannot be confirmed")
        }

        val confirmedBooking = booking.copy(
            bookingStatus = BookingStatus.CONFIRMED,
            paymentStatus = PaymentStatus.PAID
        )

        val roomType = roomTypeRepository.findById(booking.roomTypeId)
        roomType?.let {
            roomTypeRepository.save(it.copy(availableRooms = it.availableRooms - 1))
        }

        return bookingRepository.save(confirmedBooking)
    }

    fun cancelBooking(bookingId: String): Booking? {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.bookingStatus == BookingStatus.CANCELLED ||
            booking.bookingStatus == BookingStatus.COMPLETED) {
            throw IllegalStateException("Booking cannot be cancelled")
        }

        val cancelledBooking = booking.copy(
            bookingStatus = BookingStatus.CANCELLED,
            paymentStatus = PaymentStatus.REFUNDED
        )

        val roomType = roomTypeRepository.findById(booking.roomTypeId)
        roomType?.let {
            roomTypeRepository.save(it.copy(availableRooms = it.availableRooms + 1))
        }

        return bookingRepository.save(cancelledBooking)
    }

    fun checkIn(bookingId: String): Booking? {
        val booking = bookingRepository.findById(bookingId)
            ?: throw IllegalArgumentException("Booking not found")

        if (booking.bookingStatus != BookingStatus.CONFIRMED) {
            throw IllegalStateException("Invalid booking status for check-in")
        }

        return bookingRepository.save(booking.copy(bookingStatus = BookingStatus.COMPLETED))
    }

    fun addReview(
        userId: String,
        hotelId: String,
        bookingId: String,
        rating: Int,
        title: String,
        content: String,
        categories: Map<String, Int>
    ): Review {
        val review = Review(
            id = UUID.randomUUID().toString(),
            userId = userId,
            hotelId = hotelId,
            bookingId = bookingId,
            rating = rating,
            title = title,
            content = content,
            categories = categories,
            images = emptyList()
        )
        return reviewRepository.save(review)
    }

    fun getHotelReviews(hotelId: String): List<Review> {
        return reviewRepository.findByHotelIdOrderByCreatedAtDesc(hotelId)
    }

    fun getHotelRating(hotelId: String): Pair<Double, Long> {
        val avgRating = reviewRepository.averageRatingByHotelId(hotelId) ?: 0.0
        val count = reviewRepository.countByHotelId(hotelId)
        return Pair(avgRating, count)
    }
}
