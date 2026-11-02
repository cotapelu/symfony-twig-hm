package hotelbooking

import java.time.LocalDate
import java.util.concurrent.ConcurrentHashMap

class HotelRepositoryImpl : HotelRepository {
    private val hotels = ConcurrentHashMap<String, Hotel>()
    private var idCounter = 0

    override fun findAll(): List<Hotel> = hotels.values.toList()

    override fun findById(id: String): Hotel? = hotels[id]

    override fun findByCity(city: String): List<Hotel> = 
        hotels.values.filter { it.city.equals(city, ignoreCase = true) }

    override fun findByCountry(country: String): List<Hotel> = 
        hotels.values.filter { it.country.equals(country, ignoreCase = true) }

    override fun findActiveByCity(city: String): List<Hotel> = 
        hotels.values.filter { it.city.equals(city, ignoreCase = true) && it.isActive }

    override fun findByMinRating(minRating: Int): List<Hotel> = 
        hotels.values.filter { it.starRating >= minRating }

    override fun findTopRated(): List<Hotel> = 
        hotels.values.sortedByDescending { it.starRating }.take(10)

    override fun findByCityAndCountry(city: String, country: String): List<Hotel> = 
        hotels.values.filter { 
            it.city.equals(city, ignoreCase = true) && 
            it.country.equals(country, ignoreCase = true) 
        }

    override fun save(hotel: Hotel): Hotel {
        hotels[hotel.id] = hotel
        return hotel
    }

    override fun delete(id: String) {
        hotels.remove(id)
    }

    fun seedSampleData() {
        if (hotels.isNotEmpty()) return
        
        listOf(
            Hotel(
                id = "hotel_1",
                name = "Grand Plaza Hotel",
                description = "Luxury 5-star hotel in the heart of the city with stunning views",
                address = "123 Main Street",
                city = "New York",
                country = "USA",
                latitude = 40.7128,
                longitude = -74.0060,
                starRating = 5,
                amenities = listOf("WiFi", "Pool", "Spa", "Gym", "Restaurant", "Bar", "Parking"),
                policies = HotelPolicies(),
                images = listOf("https://example.com/hotel1_1.jpg", "https://example.com/hotel1_2.jpg"),
                contactInfo = HotelContactInfo("+1-555-0123", "info@grandplaza.com", "https://grandplaza.com")
            ),
            Hotel(
                id = "hotel_2",
                name = "Seaside Resort",
                description = "Beautiful beachfront resort with private beach access",
                address = "456 Ocean Drive",
                city = "Miami",
                country = "USA",
                latitude = 25.7617,
                longitude = -80.1918,
                starRating = 4,
                amenities = listOf("WiFi", "Pool", "Beach", "Restaurant", "Bar", "Parking"),
                policies = HotelPolicies(),
                images = listOf("https://example.com/hotel2_1.jpg"),
                contactInfo = HotelContactInfo("+1-555-0456", "stay@seasideresort.com", "https://seasideresort.com")
            ),
            Hotel(
                id = "hotel_3",
                name = "Mountain Lodge",
                description = "Cozy mountain retreat perfect for skiing and hiking",
                address = "789 Alpine Way",
                city = "Denver",
                country = "USA",
                latitude = 39.7392,
                longitude = -104.9903,
                starRating = 3,
                amenities = listOf("WiFi", "Fireplace", "Restaurant", "Parking", "Ski Storage"),
                policies = HotelPolicies(),
                images = listOf("https://example.com/hotel3_1.jpg"),
                contactInfo = HotelContactInfo("+1-555-0789", "hello@mountainlodge.com", null)
            )
        ).forEach { save(it) }
    }
}

class RoomTypeRepositoryImpl : RoomTypeRepository {
    private val roomTypes = ConcurrentHashMap<String, RoomType>()

    override fun findAll(): List<RoomType> = roomTypes.values.toList()

    override fun findById(id: String): RoomType? = roomTypes[id]

    override fun findByHotelId(hotelId: String): List<RoomType> = 
        roomTypes.values.filter { it.hotelId == hotelId }

    override fun findAvailableByHotelId(hotelId: String): List<RoomType> = 
        roomTypes.values.filter { it.hotelId == hotelId && it.availableRooms > 0 }

    override fun findAvailableById(id: String): RoomType? = 
        roomTypes[id]?.takeIf { it.availableRooms > 0 }

    override fun save(roomType: RoomType): RoomType {
        roomTypes[roomType.id] = roomType
        return roomType
    }

    override fun delete(id: String) {
        roomTypes.remove(id)
    }

    fun seedSampleData() {
        if (roomTypes.isNotEmpty()) return
        
        listOf(
            RoomType(
                id = "room_1",
                hotelId = "hotel_1",
                name = "Deluxe King Room",
                description = "Spacious room with king bed and city view",
                maxOccupancy = 2,
                bedType = "King",
                size = 35.0,
                amenities = listOf("WiFi", "TV", "Minibar", "Safe", "Coffee Machine"),
                images = listOf("https://example.com/room1_1.jpg"),
                basePrice = 250.0,
                totalRooms = 10,
                availableRooms = 8
            ),
            RoomType(
                id = "room_2",
                hotelId = "hotel_1",
                name = "Executive Suite",
                description = "Luxury suite with separate living area",
                maxOccupancy = 3,
                bedType = "King + Sofa",
                size = 55.0,
                amenities = listOf("WiFi", "TV", "Minibar", "Safe", "Coffee Machine", "Living Room", "Kitchenette"),
                images = listOf("https://example.com/room2_1.jpg"),
                basePrice = 450.0,
                totalRooms = 5,
                availableRooms = 3
            ),
            RoomType(
                id = "room_3",
                hotelId = "hotel_2",
                name = "Ocean View Room",
                description = "Room with stunning ocean views",
                maxOccupancy = 2,
                bedType = "Queen",
                size = 30.0,
                amenities = listOf("WiFi", "TV", "Balcony", "Minibar"),
                images = listOf("https://example.com/room3_1.jpg"),
                basePrice = 180.0,
                totalRooms = 15,
                availableRooms = 12
            )
        ).forEach { save(it) }
    }
}

class RoomRepositoryImpl : RoomRepository {
    private val rooms = ConcurrentHashMap<String, Room>()

    override fun findAll(): List<Room> = rooms.values.toList()

    override fun findById(id: String): Room? = rooms[id]

    override fun findByRoomTypeId(roomTypeId: String): List<Room> = 
        rooms.values.filter { it.roomTypeId == roomTypeId }

    override fun findByHotelIdAndStatus(hotelId: String, status: RoomStatus): List<Room> = 
        rooms.values.filter { it.hotelId == hotelId && it.status == status }

    override fun countAvailableByRoomTypeId(roomTypeId: String): Int = 
        rooms.values.count { it.roomTypeId == roomTypeId && it.status == RoomStatus.AVAILABLE }

    override fun save(room: Room): Room {
        rooms[room.id] = room
        return room
    }

    override fun delete(id: String) {
        rooms.remove(id)
    }
}

class BookingRepositoryImpl : BookingRepository {
    private val bookings = ConcurrentHashMap<String, Booking>()

    override fun findAll(): List<Booking> = bookings.values.toList()

    override fun findById(id: String): Booking? = bookings[id]

    override fun findByUserId(userId: String): List<Booking> = 
        bookings.values.filter { it.userId == userId }

    override fun findByHotelId(hotelId: String): List<Booking> = 
        bookings.values.filter { it.hotelId == hotelId }

    override fun findByUserIdOrderByCreatedAtDesc(userId: String): List<Booking> = 
        bookings.values.filter { it.userId == userId }
            .sortedByDescending { it.createdAt }

    override fun findActiveBookingsByHotelAndDate(hotelId: String, date: LocalDate): List<Booking> = 
        bookings.values.filter { 
            it.hotelId == hotelId && 
            it.checkInDate <= date && 
            it.checkOutDate > date &&
            it.bookingStatus != BookingStatus.CANCELLED
        }

    override fun findByHotelAndCheckIn(hotelId: String, checkIn: LocalDate): List<Booking> = 
        bookings.values.filter { it.hotelId == hotelId && it.checkInDate == checkIn }

    override fun findByStatus(status: BookingStatus): List<Booking> = 
        bookings.values.filter { it.bookingStatus == status }

    override fun save(booking: Booking): Booking {
        bookings[booking.id] = booking
        return booking
    }

    override fun delete(id: String) {
        bookings.remove(id)
    }
}

class ReviewRepositoryImpl : ReviewRepository {
    private val reviews = ConcurrentHashMap<String, Review>()

    override fun findAll(): List<Review> = reviews.values.toList()

    override fun findById(id: String): Review? = reviews[id]

    override fun findByHotelId(hotelId: String): List<Review> = 
        reviews.values.filter { it.hotelId == hotelId }

    override fun findByUserId(userId: String): List<Review> = 
        reviews.values.filter { it.userId == userId }

    override fun findByHotelIdOrderByCreatedAtDesc(hotelId: String): List<Review> = 
        reviews.values.filter { it.hotelId == hotelId }
            .sortedByDescending { it.createdAt }

    override fun averageRatingByHotelId(hotelId: String): Double? {
        val hotelReviews = findByHotelId(hotelId)
        return if (hotelReviews.isNotEmpty()) {
            hotelReviews.map { it.rating }.average()
        } else null
    }

    override fun countByHotelId(hotelId: String): Long = 
        reviews.values.count { it.hotelId == hotelId }.toLong()

    override fun save(review: Review): Review {
        reviews[review.id] = review
        return review
    }

    override fun delete(id: String) {
        reviews.remove(id)
    }
}