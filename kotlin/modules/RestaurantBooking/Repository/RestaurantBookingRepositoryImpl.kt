package restaurantbooking

import java.time.LocalDateTime
import java.util.concurrent.ConcurrentHashMap

class RestaurantRepositoryImpl : RestaurantRepository {
    private val restaurants = ConcurrentHashMap<String, Restaurant>()

    override fun findById(id: String): Restaurant? = restaurants[id]

    override fun findAll(): List<Restaurant> = restaurants.values.toList()

    override fun save(restaurant: Restaurant): Restaurant {
        restaurants[restaurant.id] = restaurant
        return restaurant
    }

    override fun updateRating(id: String, rating: Double, reviewCount: Int) {
        restaurants[id]?.let { restaurant ->
            restaurants[id] = restaurant.copy(rating = rating, reviewCount = reviewCount)
        }
    }

    fun seedSampleData() {
        if (restaurants.isNotEmpty()) return

        listOf(
            Restaurant(
                id = "rest_1",
                name = "Nha Hang Ngon",
                cuisine = "Vietnamese",
                address = "160 Pasteur, District 1",
                city = "Ho Chi Minh City",
                priceRange = 2,
                ambiance = "Casual Elegant",
                features = listOf("Outdoor Seating", "Vegetarian Options", "Full Bar"),
                images = listOf("rest1.jpg"),
                isActive = true
            ),
            Restaurant(
                id = "rest_2",
                name = "Pizza 4P's",
                cuisine = "Italian",
                address = "8/15 Le Thanh Ton, District 1",
                city = "Ho Chi Minh City",
                priceRange = 3,
                ambiance = "Modern Casual",
                features = listOf("Homemade Cheese", "Wood-fired Oven", "Family Friendly"),
                images = listOf("rest2.jpg"),
                isActive = true
            ),
            Restaurant(
                id = "rest_3",
                name = "The Deck Saigon",
                cuisine = "International",
                address = "38 Nguyen U Di, Thao Dien",
                city = "Ho Chi Minh City",
                priceRange = 4,
                ambiance = "Riverside Fine Dining",
                features = listOf("River View", "Cocktail Bar", "Sunday Brunch"),
                images = listOf("rest3.jpg"),
                isActive = true
            )
        ).forEach { save(it) }
    }
}

class TableRepositoryImpl : TableRepository {
    private val tables = ConcurrentHashMap<String, Table>()

    override fun findById(id: String): Table? = tables[id]

    override fun findByRestaurantId(restaurantId: String): List<Table> = 
        tables.values.filter { it.restaurantId == restaurantId }

    override fun save(table: Table): Table {
        tables[table.id] = table
        return table
    }

    fun seedSampleData() {
        if (tables.isNotEmpty()) return

        listOf(
            Table("table_1", "rest_1", 2, "Indoor"),
            Table("table_2", "rest_1", 2, "Indoor"),
            Table("table_3", "rest_1", 4, "Indoor"),
            Table("table_4", "rest_1", 4, "Garden"),
            Table("table_5", "rest_1", 6, "Garden"),
            Table("table_6", "rest_1", 8, "Private Room")
        ).forEach { save(it) }

        listOf(
            Table("table_7", "rest_2", 2, "Main Floor"),
            Table("table_8", "rest_2", 2, "Main Floor"),
            Table("table_9", "rest_2", 4, "Main Floor"),
            Table("table_10", "rest_2", 4, "Main Floor"),
            Table("table_11", "rest_2", 6, "Upper Floor")
        ).forEach { save(it) }

        listOf(
            Table("table_12", "rest_3", 2, "Riverside"),
            Table("table_13", "rest_3", 2, "Riverside"),
            Table("table_14", "rest_3", 4, "Riverside"),
            Table("table_15", "rest_3", 4, "Indoor"),
            Table("table_16", "rest_3", 6, "Private Deck")
        ).forEach { save(it) }
    }
}

class ReservationRepositoryImpl : ReservationRepository {
    private val reservations = ConcurrentHashMap<String, RestaurantBooking>()

    override fun findById(id: String): RestaurantBooking? = reservations[id]

    override fun findByRestaurantId(restaurantId: String): List<RestaurantBooking> = 
        reservations.values.filter { it.restaurantId == restaurantId }

    override fun findByUserIdOrderByCreatedAtDesc(userId: String): List<RestaurantBooking> = 
        reservations.values.filter { it.userId == userId }
            .sortedByDescending { it.createdAt }

    override fun save(booking: RestaurantBooking): RestaurantBooking {
        reservations[booking.id] = booking
        return booking
    }
}

class ReviewRepositoryImpl : ReviewRepository {
    private val reviews = ConcurrentHashMap<String, RestaurantReview>()

    override fun findById(id: String): RestaurantReview? = reviews[id]

    override fun findByRestaurantIdOrderByCreatedAtDesc(restaurantId: String): List<RestaurantReview> = 
        reviews.values.filter { it.restaurantId == restaurantId }
            .sortedByDescending { it.createdAt }

    override fun findByReservationId(reservationId: String): RestaurantReview? = 
        reviews.values.find { it.reservationId == reservationId }

    override fun save(review: RestaurantReview): RestaurantReview {
        reviews[review.id] = review
        return review
    }
}