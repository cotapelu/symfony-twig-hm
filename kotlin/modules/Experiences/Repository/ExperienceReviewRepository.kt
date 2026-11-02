package experiences

import java.time.LocalDateTime
import java.util.UUID
import java.util.concurrent.ConcurrentHashMap

class ExperienceReviewRepository {
    private val reviews = ConcurrentHashMap<String, ExperienceReview>()

    fun save(review: ExperienceReview): ExperienceReview {
        reviews[review.id] = review
        return review
    }

    fun findById(id: String): ExperienceReview? {
        return reviews[id]
    }

    fun findByExperienceId(experienceId: String): List<ExperienceReview> {
        return reviews.values
            .filter { it.experienceId == experienceId }
            .sortedByDescending { it.createdAt }
    }

    fun findByUserId(userId: String): List<ExperienceReview> {
        return reviews.values
            .filter { it.userId == userId }
            .sortedByDescending { it.createdAt }
    }

    fun calculateAverageRating(experienceId: String): Double {
        val experienceReviews = findByExperienceId(experienceId)
        return if (experienceReviews.isEmpty()) {
            0.0
        } else {
            experienceReviews.map { it.rating }.average()
        }
    }

    fun hasUserReviewed(userId: String, experienceId: String): Boolean {
        return reviews.values.any { 
            it.userId == userId && it.experienceId == experienceId 
        }
    }
}

data class ExperienceReview(
    val id: String = UUID.randomUUID().toString(),
    val userId: String,
    val experienceId: String,
    val bookingId: String,
    val rating: Int,
    val title: String,
    val comment: String,
    val hostRating: Int? = null,
    val valueRating: Int? = null,
    val createdAt: LocalDateTime = LocalDateTime.now()
)
