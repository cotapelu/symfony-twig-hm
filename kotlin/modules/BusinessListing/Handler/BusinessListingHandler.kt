package businesslisting

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.http.*

class BusinessListingHandler(private val service: BusinessListingService) {
    
    suspend fun searchListings(call: ApplicationCall) {
        try {
            val query = call.request.queryParameters["query"]
            val category = call.request.queryParameters["category"]
            val latitude = call.request.queryParameters["latitude"]?.toDoubleOrNull()
            val longitude = call.request.queryParameters["longitude"]?.toDoubleOrNull()
            val radiusKm = call.request.queryParameters["radiusKm"]?.toDoubleOrNull()
            val sortBy = call.request.queryParameters["sortBy"]?.let {
                try { SortOption.valueOf(it.uppercase()) } catch (e: Exception) { SortOption.RATING }
            } ?: SortOption.RATING
            val limit = call.request.queryParameters["limit"]?.toIntOrNull() ?: 20
            val offset = call.request.queryParameters["offset"]?.toIntOrNull() ?: 0
            
            val request = BusinessSearchRequest(
                query = query,
                category = category,
                latitude = latitude,
                longitude = longitude,
                radiusKm = radiusKm,
                sortBy = sortBy,
                limit = limit,
                offset = offset
            )
            
            val listings = service.searchListings(request)
            call.respond(mapOf("listings" to listings, "count" to listings.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Search failed")))
        }
    }
    
    suspend fun getListing(call: ApplicationCall) {
        try {
            val listingId = call.parameters["id"]?.toLongOrNull() ?: throw IllegalArgumentException("Listing ID required")
            val listing = service.getListing(listingId)
            
            if (listing != null) {
                call.respond(mapOf("listing" to listing))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Listing not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get listing")))
        }
    }
    
    suspend fun getListingWithReviews(call: ApplicationCall) {
        try {
            val listingId = call.parameters["id"]?.toLongOrNull() ?: throw IllegalArgumentException("Listing ID required")
            val listingWithReviews = service.getListingWithReviews(listingId)
            
            if (listingWithReviews != null) {
                call.respond(mapOf("listing" to listingWithReviews.first, "reviews" to listingWithReviews.second))
            } else {
                call.respond(HttpStatusCode.NotFound, mapOf("error" to "Listing not found"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get listing reviews")))
        }
    }
    
    suspend fun createListing(call: ApplicationCall) {
        try {
            val request = call.receive<CreateListingRequest>()
            val listing = service.createListing(request)
            call.respond(HttpStatusCode.Created, mapOf("listing" to listing))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to create listing")))
        }
    }
    
    suspend fun updateListing(call: ApplicationCall) {
        try {
            val listingId = call.parameters["id"]?.toLongOrNull() ?: throw IllegalArgumentException("Listing ID required")
            val request = call.receive<UpdateListingRequest>()
            val listing = service.updateListing(listingId, request)
            
            if (listing != null) {
                call.respond(mapOf("listing" to listing))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot update listing"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to update listing")))
        }
    }
    
    suspend fun deleteListing(call: ApplicationCall) {
        try {
            val listingId = call.parameters["id"]?.toLongOrNull() ?: throw IllegalArgumentException("Listing ID required")
            val success = service.deleteListing(listingId)
            
            if (success) {
                call.respond(mapOf("message" to "Listing deleted successfully"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot delete listing"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to delete listing")))
        }
    }
    
    suspend fun verifyListing(call: ApplicationCall) {
        try {
            val listingId = call.parameters["id"]?.toLongOrNull() ?: throw IllegalArgumentException("Listing ID required")
            val listing = service.verifyListing(listingId)
            
            if (listing != null) {
                call.respond(mapOf("listing" to listing, "message" to "Listing verified"))
            } else {
                call.respond(HttpStatusCode.BadRequest, mapOf("error" to "Cannot verify listing"))
            }
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to verify listing")))
        }
    }
    
    suspend fun addReview(call: ApplicationCall) {
        try {
            val request = call.receive<CreateReviewRequest>()
            val review = service.addReview(request)
            call.respond(HttpStatusCode.Created, mapOf("review" to review))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to add review")))
        }
    }
    
    suspend fun getCategories(call: ApplicationCall) {
        try {
            val categories = service.getCategories()
            call.respond(mapOf("categories" to categories))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get categories")))
        }
    }
    
    suspend fun getListingsByOwner(call: ApplicationCall) {
        try {
            val ownerId = call.request.queryParameters["ownerId"] ?: throw IllegalArgumentException("Owner ID required")
            val listings = service.getListingsByOwner(ownerId)
            call.respond(mapOf("listings" to listings, "count" to listings.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get listings")))
        }
    }
    
    suspend fun getNearbyListings(call: ApplicationCall) {
        try {
            val latitude = call.request.queryParameters["latitude"]?.toDoubleOrNull() ?: throw IllegalArgumentException("Latitude required")
            val longitude = call.request.queryParameters["longitude"]?.toDoubleOrNull() ?: throw IllegalArgumentException("Longitude required")
            val radiusKm = call.request.queryParameters["radiusKm"]?.toDoubleOrNull() ?: 10.0
            val limit = call.request.queryParameters["limit"]?.toIntOrNull() ?: 20
            
            val listings = service.getNearbyListings(latitude, longitude, radiusKm, limit)
            call.respond(mapOf("listings" to listings, "count" to listings.size))
        } catch (e: Exception) {
            call.respond(HttpStatusCode.BadRequest, mapOf("error" to (e.message ?: "Failed to get nearby listings")))
        }
    }
}
