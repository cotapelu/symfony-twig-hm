package businesslisting

import io.ktor.server.application.*
import io.ktor.server.request.*
import io.ktor.server.response.*
import io.ktor.server.routing.*
import io.ktor.http.*

private fun authenticate(call: ApplicationCall): Boolean {
	val authHeader = call.request.headers["Authorization"]
	return authHeader != null && authHeader.startsWith("Bearer ") && authHeader.length > 7
}

private fun requireAuth(call: ApplicationCall) {
	if (!authenticate(call)) {
		call.respond(HttpStatusCode.Unauthorized, mapOf("error" to "Authentication required"))
		throw IllegalArgumentException("Unauthorized")
	}
}

fun Route.businessListingRoutes() {
	val service = BusinessListingService(
		BusinessListingRepository(),
		BusinessReviewRepository(),
		BusinessCategoryRepository()
	)
    
    // Listing routes
    get("/api/business/search") {
        val request = call.receive<BusinessSearchRequest>()
        val listings = service.searchListings(request)
        call.respond(listings)
    }
    
    get("/api/business/{listingId}") {
        val listingId = call.parameters["listingId"]?.toLongOrNull() ?: throw IllegalArgumentException("Listing ID required")
        val result = service.getListingWithReviews(listingId)
        if (result != null) {
            call.respond(mapOf("listing" to result.first, "reviews" to result.second))
        } else {
            call.respond(io.ktor.http.HttpStatusCode.NotFound, "Listing not found")
        }
    }
    
post("/api/business") {
		requireAuth(call)
		val request = call.receive<CreateListingRequest>()
		val listing = service.createListing(request)
		call.respond(listing)
	}

	put("/api/business/{listingId}") {
		requireAuth(call)
		val listingId = call.parameters["listingId"]?.toLongOrNull() ?: throw IllegalArgumentException("Listing ID required")
		val request = call.receive<UpdateListingRequest>()
		val listing = service.updateListing(listingId, request)
		if (listing != null) {
			call.respond(listing)
		} else {
			call.respond(io.ktor.http.HttpStatusCode.NotFound, "Listing not found")
		}
	}

	delete("/api/business/{listingId}") {
		requireAuth(call)
		val listingId = call.parameters["listingId"]?.toLongOrNull() ?: throw IllegalArgumentException("Listing ID required")
		val success = service.deleteListing(listingId)
		if (success) {
			call.respond(mapOf("status" to "deleted"))
		} else {
			call.respond(io.ktor.http.HttpStatusCode.NotFound, "Listing not found")
		}
	}

	post("/api/business/{listingId}/verify") {
		requireAuth(call)
		val listingId = call.parameters["listingId"]?.toLongOrNull() ?: throw IllegalArgumentException("Listing ID required")
		val listing = service.verifyListing(listingId)
		if (listing != null) {
			call.respond(listing)
		} else {
			call.respond(io.ktor.http.HttpStatusCode.NotFound, "Listing not found")
		}
	}

	// Review routes
	post("/api/business/{listingId}/reviews") {
		requireAuth(call)
		val listingId = call.parameters["listingId"]?.toLongOrNull() ?: throw IllegalArgumentException("Listing ID required")
		val request = call.receive<CreateReviewRequest>()
		val review = service.addReview(request.copy(listingId = listingId))
		call.respond(review)
	}
    
    // Category routes
    get("/api/business/categories") {
        val categories = service.getCategories()
        call.respond(categories)
    }
    
    // Owner routes
    get("/api/business/owner/{ownerId}") {
        val ownerId = call.parameters["ownerId"] ?: throw IllegalArgumentException("Owner ID required")
        val listings = service.getListingsByOwner(ownerId)
        call.respond(listings)
    }
    
    // Nearby routes
    get("/api/business/nearby") {
        val lat = call.parameters["latitude"]?.toDoubleOrNull() ?: throw IllegalArgumentException("Latitude required")
        val lon = call.parameters["longitude"]?.toDoubleOrNull() ?: throw IllegalArgumentException("Longitude required")
        val radius = call.parameters["radius"]?.toDoubleOrNull() ?: 10.0
        val limit = call.parameters["limit"]?.toIntOrNull() ?: 20
        val listings = service.getNearbyListings(lat, lon, radius, limit)
        call.respond(listings)
    }
}
