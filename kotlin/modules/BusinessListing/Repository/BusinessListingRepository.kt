package businesslisting

interface BusinessListingRepository {
    fun findById(id: Long): BusinessListing?
    fun findByStatus(status: String): List<BusinessListing>
    fun findByOwnerId(ownerId: String): List<BusinessListing>
    fun findByCategory(category: String): List<BusinessListing>
    fun save(listing: BusinessListing): BusinessListing
    fun updateStatus(id: Long, status: String): Boolean
    fun updateRating(id: Long, rating: Double, reviewCount: Int)
}

interface BusinessReviewRepository {
    fun findById(id: Long): BusinessReview?
    fun findByListingId(listingId: Long): List<BusinessReview>
    fun findByListingIdOrderByCreatedAtDesc(listingId: Long): List<BusinessReview>
    fun findByUserId(userId: String): List<BusinessReview>
    fun save(review: BusinessReview): BusinessReview
}

interface BusinessCategoryRepository {
    fun findAll(): List<BusinessCategory>
    fun findById(id: Long): BusinessCategory?
    fun findBySlug(slug: String): BusinessCategory?
    fun save(category: BusinessCategory): BusinessCategory
}
