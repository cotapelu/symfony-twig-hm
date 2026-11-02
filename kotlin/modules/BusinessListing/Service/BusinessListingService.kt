package businesslisting

import java.time.LocalDateTime
import java.util.UUID

class BusinessListingService(
    private val listingRepository: BusinessListingRepository,
    private val reviewRepository: BusinessReviewRepository,
    private val categoryRepository: BusinessCategoryRepository
) {

    fun searchListings(request: BusinessSearchRequest): List<BusinessListing> {
        var listings = listingRepository.findByStatus("active")
        
        if (request.category != null) {
            listings = listings.filter { it.category == request.category }
        }
        
        if (request.query != null) {
            val query = request.query.lowercase()
            listings = listings.filter { 
                it.name.lowercase().contains(query) || 
                it.description.lowercase().contains(query)
            }
        }
        
        if (request.latitude != null && request.longitude != null && request.radiusKm != null) {
            listings = listings.filter { listing ->
                val distance = calculateDistance(
                    request.latitude, request.longitude,
                    listing.latitude, listing.longitude
                )
                distance <= request.radiusKm
            }.sortedBy { listing ->
                calculateDistance(
                    request.latitude, request.longitude,
                    listing.latitude, listing.longitude
                )
            }
        }
        
        return when (request.sortBy) {
            SortOption.RATING -> listings.sortedByDescending { it.rating }
            SortOption.REVIEWS -> listings.sortedByDescending { it.reviewCount }
            SortOption.NEWEST -> listings.sortedByDescending { it.createdAt }
        }.drop(request.offset).take(request.limit)
    }

    private fun calculateDistance(lat1: Double, lon1: Double, lat2: Double, lon2: Double): Double {
        val r = 6371.0
        val dLat = Math.toRadians(lat2 - lat1)
        val dLon = Math.toRadians(lon2 - lon1)
        val a = kotlin.math.sin(dLat / 2) * kotlin.math.sin(dLat / 2) +
                kotlin.math.cos(Math.toRadians(lat1)) * kotlin.math.cos(Math.toRadians(lat2)) *
                kotlin.math.sin(dLon / 2) * kotlin.math.sin(dLon / 2)
        val c = 2 * kotlin.math.atan2(kotlin.math.sqrt(a), kotlin.math.sqrt(1 - a))
        return r * c
    }

    fun getListing(listingId: Long): BusinessListing? {
        return listingRepository.findById(listingId)
    }

    fun getListingWithReviews(listingId: Long): Pair<BusinessListing, List<BusinessReview>>? {
        val listing = listingRepository.findById(listingId) ?: return null
        val reviews = reviewRepository.findByListingIdOrderByCreatedAtDesc(listingId)
        return Pair(listing, reviews)
    }

    fun createListing(request: CreateListingRequest): BusinessListing {
        val listing = BusinessListing(
            ownerId = request.ownerId,
            name = request.name,
            description = request.description,
            category = request.category,
            address = request.address,
            latitude = request.latitude,
            longitude = request.longitude,
            phone = request.phone,
            email = request.email,
            website = request.website,
            openingHours = request.openingHours,
            photos = request.photos,
            status = "active",
            verified = false
        )
        return listingRepository.save(listing)
    }

    fun updateListing(listingId: Long, request: UpdateListingRequest): BusinessListing? {
        val listing = listingRepository.findById(listingId)
            ?: throw IllegalArgumentException("Listing not found")
        
        val updated = listing.copy(
            name = request.name ?: listing.name,
            description = request.description ?: listing.description,
            address = request.address ?: listing.address,
            latitude = request.latitude ?: listing.latitude,
            longitude = request.longitude ?: listing.longitude,
            phone = request.phone ?: listing.phone,
            email = request.email ?: listing.email,
            website = request.website ?: listing.website,
            openingHours = request.openingHours ?: listing.openingHours,
            photos = request.photos ?: listing.photos,
            updatedAt = LocalDateTime.now().toString()
        )
        return listingRepository.save(updated)
    }

    fun deleteListing(listingId: Long): Boolean {
        val listing = listingRepository.findById(listingId)
            ?: throw IllegalArgumentException("Listing not found")
        
        return listingRepository.updateStatus(listingId, "deleted")
    }

    fun verifyListing(listingId: Long): BusinessListing? {
        val listing = listingRepository.findById(listingId)
            ?: throw IllegalArgumentException("Listing not found")
        
        return listingRepository.save(listing.copy(verified = true))
    }

    fun addReview(request: CreateReviewRequest): BusinessReview {
        val listing = listingRepository.findById(request.listingId)
            ?: throw IllegalArgumentException("Listing not found")
        
        val review = BusinessReview(
            listingId = request.listingId,
            userId = request.userId,
            rating = request.rating,
            comment = request.comment,
            photos = request.photos,
            createdAt = LocalDateTime.now().toString()
        )
        
        val savedReview = reviewRepository.save(review)
        
        // Update listing rating
        val allReviews = reviewRepository.findByListingId(request.listingId)
        val newAvgRating = allReviews.map { it.rating }.average()
        listingRepository.updateRating(request.listingId, newAvgRating, allReviews.size)
        
        return savedReview
    }

    fun getCategories(): List<BusinessCategory> {
        return categoryRepository.findAll()
    }

    fun getListingsByOwner(ownerId: String): List<BusinessListing> {
        return listingRepository.findByOwnerId(ownerId)
    }

    fun getNearbyListings(latitude: Double, longitude: Double, radiusKm: Double, limit: Int): List<BusinessListing> {
        return listingRepository.findByStatus("active")
            .filter { listing ->
                val distance = calculateDistance(latitude, longitude, listing.latitude, listing.longitude)
                distance <= radiusKm
            }
            .sortedBy { listing ->
                calculateDistance(latitude, longitude, listing.latitude, listing.longitude)
            }
            .take(limit)
    }
}

data class BusinessSearchRequest(
    val query: String? = null,
    val category: String? = null,
    val latitude: Double? = null,
    val longitude: Double? = null,
    val radiusKm: Double? = null,
    val sortBy: SortOption = SortOption.RATING,
    val limit: Int = 20,
    val offset: Int = 0
)

enum class SortOption {
    RATING, REVIEWS, NEWEST
}

data class CreateListingRequest(
    val ownerId: String,
    val name: String,
    val description: String,
    val category: String,
    val address: String,
    val latitude: Double,
    val longitude: Double,
    val phone: String,
    val email: String,
    val website: String? = null,
    val openingHours: Map<String, String> = emptyMap(),
    val photos: List<String> = emptyList()
)

data class UpdateListingRequest(
    val name: String? = null,
    val description: String? = null,
    val address: String? = null,
    val latitude: Double? = null,
    val longitude: Double? = null,
    val phone: String? = null,
    val email: String? = null,
    val website: String? = null,
    val openingHours: Map<String, String>? = null,
    val photos: List<String>? = null
)

data class CreateReviewRequest(
    val listingId: Long,
    val userId: String,
    val rating: Int,
    val comment: String,
    val photos: List<String> = emptyList()
)
