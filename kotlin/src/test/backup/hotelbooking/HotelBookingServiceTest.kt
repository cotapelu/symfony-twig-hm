package hotelbooking

import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import java.time.LocalDate

class HotelBookingServiceTest {

    private val hotelRepo = FakeHotelRepository()
    private val roomTypeRepo = FakeRoomTypeRepository()
    private val roomRepo = FakeRoomRepository()
    private val bookingRepo = FakeBookingRepository()
    private val reviewRepo = FakeReviewRepository()
    private val service = HotelBookingService(hotelRepo, roomTypeRepo, roomRepo, bookingRepo, reviewRepo)

    @Test
    fun `searchHotels by city should return hotels`() {
        hotelRepo.save(Hotel(id = "h1", name = "H1", city = "Saigon", isActive = true, starRating = 4))
        hotelRepo.save(Hotel(id = "h2", name = "H2", city = "Hanoi", isActive = true, starRating = 3))

        val results = service.searchHotels(SearchRequest(city = "Saigon"))
        assertEquals(1, results.size)
        assertEquals("H1", results[0].name)
    }

    @Test
    fun `checkAvailability should return available room types`() {
        val hotel = Hotel(id = "h1", name = "Test", isActive = true)
        hotelRepo.save(hotel)
        val rt = RoomType(id = "rt1", hotelId = "h1", availableRooms = 5, basePrice = 100.0, maxOccupancy = 2)
        roomTypeRepo.save(rt)

        val checkIn = LocalDate.now()
        val checkOut = checkIn.plusDays(2)
        val available = service.checkAvailability("h1", checkIn, checkOut, 1)
        assertTrue(available.isNotEmpty())
    }

    @Test
    fun `createBooking should fail when no rooms available`() {
        val hotel = Hotel(id = "h2", isActive = true)
        hotelRepo.save(hotel)
        val rt = RoomType(id = "rt2", hotelId = "h2", availableRooms = 0, basePrice = 100.0, maxOccupancy = 2)
        roomTypeRepo.save(rt)

        val ex = assertThrows<IllegalStateException> {
            service.createBooking(
                userId = "u1",
                hotelId = "h2",
                roomTypeId = "rt2",
                checkIn = LocalDate.now(),
                checkOut = LocalDate.now().plusDays(1),
                guestCount = 1,
                guestInfo = GuestInfo(),
                specialRequests = null
            )
        }
        assertTrue(ex.message!!.contains("No rooms available"))
    }

    @Test
    fun `createBooking should succeed and decrement available rooms`() {
        val hotel = Hotel(id = "h3", isActive = true)
        hotelRepo.save(hotel)
        val rt = RoomType(id = "rt3", hotelId = "h3", availableRooms = 5, basePrice = 200.0, maxOccupancy = 2)
        roomTypeRepo.save(rt)

        val checkIn = LocalDate.now()
        val checkOut = checkIn.plusDays(2)
        val booking = service.createBooking(
            userId = "u1",
            hotelId = "h3",
            roomTypeId = "rt3",
            checkIn = checkIn,
            checkOut = checkOut,
            guestCount = 1,
            guestInfo = GuestInfo(),
            specialRequests = null
        )

        assertEquals("u1", booking.userId)
        val updatedRt = roomTypeRepo.findById("rt3")!!
        assertEquals(4, updatedRt.availableRooms)
    }

    // Fake repositories
    private class FakeHotelRepository : HotelRepository {
        private val hotels = mutableMapOf<String, Hotel>()
        override fun findAll(): List<Hotel> = hotels.values.toList()
        override fun findById(id: String): Hotel? = hotels[id]
        override fun findByCity(city: String): List<Hotel> = hotels.values.filter { it.city == city }.toList()
        override fun findByCountry(country: String): List<Hotel> = hotels.values.filter { it.country == country }.toList()
        override fun findActiveByCity(city: String): List<Hotel> = findAll().filter { it.city == city && it.isActive }
        override fun findByMinRating(minRating: Int): List<Hotel> = findAll().filter { it.starRating >= minRating }
        override fun findTopRated(): List<Hotel> = findAll().sortedByDescending { it.starRating }
        override fun findByCityAndCountry(city: String, country: String): List<Hotel> = findAll().filter { it.city == city && it.country == country }
        override fun save(hotel: Hotel): Hotel { hotels[hotel.id] = hotel; return hotel }
        override fun delete(id: String) { hotels.remove(id) }
    }

    private class FakeRoomTypeRepository : RoomTypeRepository {
        private val roomTypes = mutableMapOf<String, RoomType>()
        override fun findAll(): List<RoomType> = roomTypes.values.toList()
        override fun findById(id: String): RoomType? = roomTypes[id]
        override fun findByHotelId(hotelId: String): List<RoomType> = roomTypes.values.filter { it.hotelId == hotelId }.toList()
        override fun findAvailableByHotelId(hotelId: String): List<RoomType> = findAll().filter { it.hotelId == hotelId && it.availableRooms > 0 }
        override fun findAvailableById(id: String): RoomType? = roomTypes[id]?.takeIf { it.availableRooms > 0 }
        override fun save(roomType: RoomType): RoomType { roomTypes[roomType.id] = roomType; return roomType }
        override fun delete(id: String) { roomTypes.remove(id) }
    }

    private class FakeRoomRepository : RoomRepository {
        private val rooms = mutableMapOf<String, Room>()
        override fun findAll(): List<Room> = rooms.values.toList()
        override fun findById(id: String): Room? = rooms[id]
        override fun findByRoomTypeId(roomTypeId: String): List<Room> = rooms.values.filter { it.roomTypeId == roomTypeId }.toList()
        override fun findByHotelIdAndStatus(hotelId: String, status: RoomStatus): List<Room> = emptyList()
        override fun countAvailableByRoomTypeId(roomTypeId: String): Int = 0
        override fun save(room: Room): Room { rooms[room.id] = room; return room }
        override fun delete(id: String) { rooms.remove(id) }
    }

    private class FakeBookingRepository : BookingRepository {
        private val bookings = mutableMapOf<String, Booking>()
        override fun findAll(): List<Booking> = bookings.values.toList()
        override fun findById(id: String): Booking? = bookings[id]
        override fun findByUserId(userId: String): List<Booking> = bookings.values.filter { it.userId == userId }.toList()
        override fun findByHotelId(hotelId: String): List<Booking> = bookings.values.filter { it.hotelId == hotelId }.toList()
        override fun findByUserIdOrderByCreatedAtDesc(userId: String): List<Booking> = findByUserId(userId).sortedByDescending { it.createdAt }
        override fun findActiveBookingsByHotelAndDate(hotelId: String, date: LocalDate): List<Booking> = emptyList()
        override fun findByHotelAndCheckIn(hotelId: String, checkIn: LocalDate): List<Booking> = emptyList()
        override fun findByStatus(status: BookingStatus): List<Booking> = bookings.values.filter { it.bookingStatus == status }.toList()
        override fun save(booking: Booking): Booking { bookings[booking.id] = booking; return booking }
        override fun delete(id: String) { bookings.remove(id) }
    }

    private class FakeReviewRepository : ReviewRepository {
        private val reviews = mutableMapOf<String, Review>()
        override fun findAll(): List<Review> = reviews.values.toList()
        override fun findById(id: String): Review? = reviews[id]
        override fun findByHotelId(hotelId: String): List<Review> = reviews.values.filter { it.hotelId == hotelId }.toList()
        override fun findByUserId(userId: String): List<Review> = reviews.values.filter { it.userId == userId }.toList()
        override fun findByHotelIdOrderByCreatedAtDesc(hotelId: String): List<Review> = findByHotelId(hotelId).sortedByDescending { it.createdAt }
        override fun averageRatingByHotelId(hotelId: String): Double? = findByHotelId(hotelId).map { it.rating.toDouble() }.average().takeIf { !it.isNaN() }
        override fun countByHotelId(hotelId: String): Long = findByHotelId(hotelId).size.toLong()
        override fun save(review: Review): Review { reviews[review.id] = review; return review }
        override fun delete(id: String) { reviews.remove(id) }
    }
}
