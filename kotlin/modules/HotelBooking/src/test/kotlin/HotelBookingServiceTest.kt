package hotelbooking

import org.junit.jupiter.api.BeforeEach
import org.junit.jupiter.api.Test
import org.junit.jupiter.api.Assertions.*
import java.time.LocalDate
import java.util.UUID

class HotelBookingServiceTest {

    private lateinit var service: HotelBookingService
    private lateinit var hotelRepository: InMemoryHotelRepository
    private lateinit var roomTypeRepository: InMemoryRoomTypeRepository
    private lateinit var roomRepository: InMemoryRoomRepository
    private lateinit var bookingRepository: InMemoryBookingRepository
    private lateinit var reviewRepository: InMemoryReviewRepository

    @BeforeEach
    fun setUp() {
        hotelRepository = InMemoryHotelRepository()
        roomTypeRepository = InMemoryRoomTypeRepository()
        roomRepository = InMemoryRoomRepository()
        bookingRepository = InMemoryBookingRepository()
        reviewRepository = InMemoryReviewRepository()
        service = HotelBookingService(
            hotelRepository,
            roomTypeRepository,
            roomRepository,
            bookingRepository,
            reviewRepository
        )
    }

    @Test
    fun testSearchHotelsByCity() {
        val hotel = createTestHotel(id = "hotel_1", city = "Ho Chi Minh")
        hotelRepository.save(hotel)

        val request = SearchRequest(city = "Ho Chi Minh")
        val results = service.searchHotels(request)

        assertEquals(1, results.size)
        assertEquals("hotel_1", results[0].id)
    }

    @Test
    fun testSearchHotelsByStarRating() {
        hotelRepository.save(createTestHotel(id = "hotel_1", city = "Hanoi", starRating = 5))
        hotelRepository.save(createTestHotel(id = "hotel_2", city = "Hanoi", starRating = 3))

        val request = SearchRequest(city = "Hanoi", starRating = 4)
        val results = service.searchHotels(request)

        assertEquals(1, results.size)
        assertEquals(5, results[0].starRating)
    }

    @Test
    fun testSearchHotelsByAmenities() {
        val hotel = createTestHotel(
            id = "hotel_1",
            city = "Da Nang",
            amenities = listOf("wifi", "pool", "gym")
        )
        hotelRepository.save(hotel)

        val request = SearchRequest(
            city = "Da Nang",
            amenities = listOf("wifi", "pool")
        )
        val results = service.searchHotels(request)

        assertEquals(1, results.size)
    }

    @Test
    fun testGetHotel() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        val result = service.getHotel("hotel_1")

        assertNotNull(result)
        assertEquals("hotel_1", result?.id)
    }

    @Test
    fun testGetHotelNotFound() {
        val result = service.getHotel("nonexistent")
        assertNull(result)
    }

    @Test
    fun testGetHotelRoomTypes() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        val roomType = RoomType(
            id = "room_1",
            hotelId = "hotel_1",
            name = "Deluxe Room",
            description = "Spacious room",
            maxOccupancy = 2,
            bedType = "King",
            size = 35.0,
            amenities = listOf("wifi"),
            images = emptyList(),
            basePrice = 100.0,
            totalRooms = 10,
            availableRooms = 5
        )
        roomTypeRepository.save(roomType)

        val results = service.getHotelRoomTypes("hotel_1")

        assertEquals(1, results.size)
        assertEquals("Deluxe Room", results[0].name)
    }

    @Test
    fun testCheckAvailability() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        val roomType = RoomType(
            id = "room_1",
            hotelId = "hotel_1",
            name = "Standard Room",
            description = "Basic room",
            maxOccupancy = 2,
            bedType = "Double",
            size = 25.0,
            amenities = listOf("wifi"),
            images = emptyList(),
            basePrice = 50.0,
            totalRooms = 5,
            availableRooms = 3
        )
        roomTypeRepository.save(roomType)

        val checkIn = LocalDate.now().plusDays(1)
        val checkOut = LocalDate.now().plusDays(3)
        val available = service.checkAvailability("hotel_1", checkIn, checkOut, 2)

        assertEquals(1, available.size)
    }

    @Test
    fun testCreateBooking() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        val roomType = RoomType(
            id = "room_1",
            hotelId = "hotel_1",
            name = "Deluxe Room",
            description = "Spacious room",
            maxOccupancy = 2,
            bedType = "King",
            size = 35.0,
            amenities = listOf("wifi"),
            images = emptyList(),
            basePrice = 100.0,
            totalRooms = 10,
            availableRooms = 5
        )
        roomTypeRepository.save(roomType)

        val checkIn = LocalDate.now().plusDays(1)
        val checkOut = LocalDate.now().plusDays(3)
        val guestInfo = GuestInfo(
            firstName = "John",
            lastName = "Doe",
            email = "john@example.com",
            phone = "+1234567890",
            country = "USA"
        )

        val booking = service.createBooking(
            userId = "user_1",
            hotelId = "hotel_1",
            roomTypeId = "room_1",
            checkIn = checkIn,
            checkOut = checkOut,
            guestCount = 2,
            guestInfo = guestInfo,
            specialRequests = "Late check-in"
        )

        assertNotNull(booking)
        assertEquals("user_1", booking.userId)
        assertEquals(200.0, booking.totalPrice) // 100 * 2 days
    }

    @Test
    fun testCreateBookingRoomNotFound() {
        val guestInfo = GuestInfo("John", "Doe", "john@example.com", "+1234567890", "USA")

        assertThrows(IllegalArgumentException::class.java) {
            service.createBooking(
                userId = "user_1",
                hotelId = "hotel_1",
                roomTypeId = "nonexistent",
                checkIn = LocalDate.now(),
                checkOut = LocalDate.now().plusDays(1),
                guestCount = 2,
                guestInfo = guestInfo,
                specialRequests = null
            )
        }
    }

    @Test
    fun testGetBooking() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        val roomType = createTestRoomType(hotelId = "hotel_1")
        roomTypeRepository.save(roomType)

        val booking = createTestBooking()
        bookingRepository.save(booking)

        val result = service.getBooking(booking.id)

        assertNotNull(result)
        assertEquals(booking.id, result?.id)
    }

    @Test
    fun testGetUserBookings() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        val roomType = createTestRoomType(hotelId = "hotel_1")
        roomTypeRepository.save(roomType)

        bookingRepository.save(createTestBooking(userId = "user_1"))
        bookingRepository.save(createTestBooking(userId = "user_1"))
        bookingRepository.save(createTestBooking(userId = "user_2"))

        val results = service.getUserBookings("user_1")

        assertEquals(2, results.size)
    }

    @Test
    fun testConfirmBooking() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        val roomType = createTestRoomType(hotelId = "hotel_1", availableRooms = 5)
        roomTypeRepository.save(roomType)

        val booking = createTestBooking()
        bookingRepository.save(booking)

        val confirmed = service.confirmBooking(booking.id)

        assertNotNull(confirmed)
        assertEquals(BookingStatus.CONFIRMED, confirmed.bookingStatus)
        assertEquals(PaymentStatus.PAID, confirmed.paymentStatus)
    }

    @Test
    fun testCancelBooking() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        val roomType = createTestRoomType(hotelId = "hotel_1")
        roomTypeRepository.save(roomType)

        val booking = createTestBooking()
        bookingRepository.save(booking)

        val cancelled = service.cancelBooking(booking.id)

        assertNotNull(cancelled)
        assertEquals(BookingStatus.CANCELLED, cancelled.bookingStatus)
        assertEquals(PaymentStatus.REFUNDED, cancelled.paymentStatus)
    }

    @Test
    fun testAddReview() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        val review = service.addReview(
            userId = "user_1",
            hotelId = "hotel_1",
            bookingId = "booking_1",
            rating = 5,
            title = "Great stay!",
            content = "Wonderful experience",
            categories = mapOf("cleanliness" to 5, "service" to 4)
        )

        assertNotNull(review)
        assertEquals(5, review.rating)
        assertEquals("Great stay!", review.title)
    }

    @Test
    fun testGetHotelReviews() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        reviewRepository.save(Review(
            id = "review_1",
            userId = "user_1",
            hotelId = "hotel_1",
            bookingId = "booking_1",
            rating = 4,
            title = "Good",
            content = "Nice hotel",
            categories = emptyMap(),
            images = emptyList()
        ))

        val reviews = service.getHotelReviews("hotel_1")

        assertEquals(1, reviews.size)
    }

    @Test
    fun testGetHotelRating() {
        val hotel = createTestHotel(id = "hotel_1", city = "Hanoi")
        hotelRepository.save(hotel)

        reviewRepository.save(Review(
            id = "review_1",
            userId = "user_1",
            hotelId = "hotel_1",
            bookingId = "booking_1",
            rating = 4,
            title = "Good",
            content = "Nice hotel",
            categories = emptyMap(),
            images = emptyList()
        ))
        reviewRepository.save(Review(
            id = "review_2",
            userId = "user_2",
            hotelId = "hotel_1",
            bookingId = "booking_2",
            rating = 5,
            title = "Great!",
            content = "Amazing experience",
            categories = emptyMap(),
            images = emptyList()
        ))

        val (avgRating, count) = service.getHotelRating("hotel_1")

        assertEquals(4.5, avgRating)
        assertEquals(2, count)
    }

    // Helper methods
    private fun createTestHotel(
        id: String = "hotel_1",
        name: String = "Test Hotel",
        city: String = "Hanoi",
        country: String = "Vietnam",
        starRating: Int = 4,
        amenities: List<String> = listOf("wifi", "pool")
    ) = Hotel(
        id = id,
        name = name,
        description = "Test hotel description",
        address = "123 Test Street",
        city = city,
        country = country,
        latitude = 21.0285,
        longitude = 105.8542,
        starRating = starRating,
        amenities = amenities,
        policies = HotelPolicies(),
        images = emptyList(),
        contactInfo = HotelContactInfo("+84-123-456-789", "test@hotel.com", null)
    )

    private fun createTestRoomType(
        hotelId: String = "hotel_1",
        availableRooms: Int = 5
    ) = RoomType(
        id = "room_1",
        hotelId = hotelId,
        name = "Deluxe Room",
        description = "Spacious room",
        maxOccupancy = 2,
        bedType = "King",
        size = 35.0,
        amenities = listOf("wifi"),
        images = emptyList(),
        basePrice = 100.0,
        totalRooms = 10,
        availableRooms = availableRooms
    )

    private fun createTestBooking(userId: String = "user_1") = Booking(
        id = UUID.randomUUID().toString(),
        userId = userId,
        hotelId = "hotel_1",
        roomTypeId = "room_1",
        checkInDate = LocalDate.now().plusDays(1),
        checkOutDate = LocalDate.now().plusDays(3),
        guestCount = 2,
        guestInfo = GuestInfo("John", "Doe", "john@example.com", "+1234567890", "USA"),
        rooms = emptyList(),
        totalPrice = 200.0,
        finalPrice = 200.0
    )
}

// In-memory repositories for testing
class InMemoryHotelRepository : HotelRepository {
    private val hotels = mutableMapOf<String, Hotel>()

    override fun findById(id: String): Hotel? = hotels[id]

    override fun findAll(): List<Hotel> = hotels.values.toList()

    override fun findActiveByCity(city: String): List<Hotel> =
        hotels.values.filter { it.isActive && it.city == city }

    override fun findByCountry(country: String): List<Hotel> =
        hotels.values.filter { it.country == country }

    override fun save(hotel: Hotel): Hotel {
        hotels[hotel.id] = hotel
        return hotel
    }

    override fun delete(id: String) { hotels.remove(id) }
}

class InMemoryRoomTypeRepository : RoomTypeRepository {
    private val roomTypes = mutableMapOf<String, RoomType>()

    override fun findById(id: String): RoomType? = roomTypes[id]

    override fun findByHotelId(hotelId: String): List<RoomType> =
        roomTypes.values.filter { it.hotelId == hotelId }

    override fun findAvailableByHotelId(hotelId: String): List<RoomType> =
        roomTypes.values.filter { it.hotelId == hotelId && it.availableRooms > 0 }

    override fun save(roomType: RoomType): RoomType {
        roomTypes[roomType.id] = roomType
        return roomType
    }
}

class InMemoryRoomRepository : RoomRepository {
    private val rooms = mutableMapOf<String, Room>()

    override fun findById(id: String): Room? = rooms[id]

    override fun findByRoomTypeId(roomTypeId: String): List<Room> =
        rooms.values.filter { it.roomTypeId == roomTypeId }

    override fun save(room: Room): Room {
        rooms[room.id] = room
        return room
    }
}

class InMemoryBookingRepository : BookingRepository {
    private val bookings = mutableMapOf<String, Booking>()

    override fun findById(id: String): Booking? = bookings[id]

    override fun findAll(): List<Booking> = bookings.values.toList()

    override fun findByUserId(userId: String): List<Booking> =
        bookings.values.filter { it.userId == userId }

    override fun findByUserIdOrderByCreatedAtDesc(userId: String): List<Booking> =
        bookings.values.filter { it.userId == userId }
            .sortedByDescending { it.createdAt }

    override fun findByHotelId(hotelId: String): List<Booking> =
        bookings.values.filter { it.hotelId == hotelId }

    override fun save(booking: Booking): Booking {
        bookings[booking.id] = booking
        return booking
    }
}

class InMemoryReviewRepository : ReviewRepository {
    private val reviews = mutableMapOf<String, Review>()

    override fun findById(id: String): Review? = reviews[id]

    override fun findAll(): List<Review> = reviews.values.toList()

    override fun findByHotelId(hotelId: String): List<Review> =
        reviews.values.filter { it.hotelId == hotelId }

    override fun findByHotelIdOrderByCreatedAtDesc(hotelId: String): List<Review> =
        reviews.values.filter { it.hotelId == hotelId }
            .sortedByDescending { it.createdAt }

    override fun save(review: Review): Review {
        reviews[review.id] = review
        return review
    }

    override fun averageRatingByHotelId(hotelId: String): Double? {
        val hotelReviews = reviews.values.filter { it.hotelId == hotelId }
        return if (hotelReviews.isEmpty()) null
        else hotelReviews.map { it.rating }.average()
    }

    override fun countByHotelId(hotelId: String): Long =
        reviews.values.count { it.hotelId == hotelId }
}
