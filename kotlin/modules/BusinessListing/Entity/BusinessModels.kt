package businesslisting

data class BusinessListing(
    val id: Long = 0,
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
    val photos: List<String> = emptyList(),
    val rating: Double = 0.0,
    val reviewCount: Int = 0,
    val status: String = "active",
    val verified: Boolean = false,
    val createdAt: String = "",
    val updatedAt: String = ""
)

data class BusinessReview(
    val id: Long = 0,
    val listingId: Long,
    val userId: String,
    val rating: Int,
    val comment: String,
    val photos: List<String> = emptyList(),
    val createdAt: String = ""
)

data class BusinessCategory(
    val id: Long = 0,
    val name: String,
    val slug: String,
    val icon: String? = null,
    val parentId: Long? = null
)
