package hotelbooking

import hotelbooking.*

interface HotelRepository {
    fun findAll(): List<Hotel>
    fun findById(id: String): Hotel?
    fun findByCity(city: String): List<Hotel>
    fun findByCountry(country: String): List<Hotel>
    fun findActiveByCity(city: String): List<Hotel>
    fun findByMinRating(minRating: Int): List<Hotel>
    fun findTopRated(): List<Hotel>
    fun findByCityAndCountry(city: String, country: String): List<Hotel>
    fun save(hotel: Hotel): Hotel
    fun delete(id: String)
}

interface RoomTypeRepository {
    fun findAll(): List<RoomType>
    fun findById(id: String): RoomType?
    fun findByHotelId(hotelId: String): List<RoomType>
    fun findAvailableByHotelId(hotelId: String): List<RoomType>
    fun findAvailableById(id: String): RoomType?
    fun save(roomType: RoomType): RoomType
    fun delete(id: String)
}

interface RoomRepository {
    fun findAll(): List<Room>
    fun findById(id: String): Room?
    fun findByRoomTypeId(roomTypeId: String): List<Room>
    fun findByHotelIdAndStatus(hotelId: String, status: RoomStatus): List<Room>
    fun countAvailableByRoomTypeId(roomTypeId: String): Int
    fun save(room: Room): Room
    fun delete(id: String)
}

interface BookingRepository {
    fun findAll(): List<Booking>
    fun findById(id: String): Booking?
    fun findByUserId(userId: String): List<Booking>
    fun findByHotelId(hotelId: String): List<Booking>
    fun findByUserIdOrderByCreatedAtDesc(userId: String): List<Booking>
    fun findActiveBookingsByHotelAndDate(hotelId: String, date: LocalDate): List<Booking>
    fun findByHotelAndCheckIn(hotelId: String, checkIn: LocalDate): List<Booking>
    fun findByStatus(status: BookingStatus): List<Booking>
    fun save(booking: Booking): Booking
    fun delete(id: String)
}

interface ReviewRepository {
    fun findAll(): List<Review>
    fun findById(id: String): Review?
    fun findByHotelId(hotelId: String): List<Review>
    fun findByUserId(userId: String): List<Review>
    fun findByHotelIdOrderByCreatedAtDesc(hotelId: String): List<Review>
    fun averageRatingByHotelId(hotelId: String): Double?
    fun countByHotelId(hotelId: String): Long
    fun save(review: Review): Review
    fun delete(id: String)
}
