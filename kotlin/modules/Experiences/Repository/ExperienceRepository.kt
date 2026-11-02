package experiences

import java.util.concurrent.ConcurrentHashMap

class ExperienceRepository {
    private val experiences = ConcurrentHashMap<String, Experience>()

    fun save(experience: Experience): Experience {
        experiences[experience.id] = experience
        return experience
    }

    fun findById(id: String): Experience? {
        return experiences[id]
    }

    fun findAll(): List<Experience> {
        return experiences.values.toList()
    }

    fun findActive(): List<Experience> {
        return experiences.values.filter { it.isActive }
    }

    fun search(
        query: String? = null,
        category: ExperienceCategory? = null,
        city: String? = null,
        minPrice: Double? = null,
        maxPrice: Double? = null,
        duration: Int? = null,
        minRating: Double? = null
    ): List<Experience> {
        return experiences.values.filter { experience ->
            var matches = experience.isActive

            if (query != null) {
                matches = matches && (
                    experience.title.contains(query, ignoreCase = true) ||
                    experience.description.contains(query, ignoreCase = true) ||
                    experience.location.contains(query, ignoreCase = true)
                )
            }

            if (category != null) {
                matches = matches && experience.category == category
            }

            if (city != null) {
                matches = matches && experience.location.contains(city, ignoreCase = true)
            }

            if (minPrice != null) {
                matches = matches && experience.pricePerPerson >= minPrice
            }

            if (maxPrice != null) {
                matches = matches && experience.pricePerPerson <= maxPrice
            }

            if (duration != null) {
                matches = matches && experience.duration <= duration
            }

            if (minRating != null) {
                matches = matches && experience.rating >= minRating
            }

            matches
        }
    }

    fun seedSampleData() {
        val sampleExperiences = listOf(
            Experience(
                id = "exp-001",
                title = "Sunset Kayaking in Ha Long Bay",
                description = "Experience the breathtaking beauty of Ha Long Bay at sunset. Paddle through limestone karsts, explore hidden caves, and enjoy a traditional Vietnamese dinner on a floating fishing village.",
                location = "Ha Long Bay, Quang Ninh, Vietnam",
                category = ExperienceCategory.ADVENTURE,
                duration = 6,
                maxParticipants = 12,
                pricePerPerson = 89.0,
                highlights = listOf("Limestone karst exploration", "Hidden caves", "Floating fishing village dinner", "Professional guide"),
                includes = listOf("Kayak and equipment", "Life jacket", "Dinner", "Hotel pickup/dropoff", "English-speaking guide"),
                images = listOf("https://images.unsplash.com/photo-1558617320-e695f0d420de", "https://images.unsplash.com/photo-1559592413-7cec4d0c8d60"),
                hostName = "Vietnam Adventure Tours",
                hostBio = "Local experts with 15+ years experience showing visitors the hidden gems of Ha Long Bay",
                rating = 4.9,
                reviewCount = 234,
                availableDates = listOf(
                    java.time.LocalDate.now().plusDays(2),
                    java.time.LocalDate.now().plusDays(4),
                    java.time.LocalDate.now().plusDays(6)
                )
            ),
            Experience(
                id = "exp-002",
                title = "Traditional Thai Cooking Class",
                description = "Learn authentic Thai cuisine from a local chef in Bangkok. Visit a fresh market, prepare 5 classic dishes, and enjoy your creations with new friends.",
                location = "Bangkok, Thailand",
                category = ExperienceCategory.FOOD,
                duration = 4,
                maxParticipants = 8,
                pricePerPerson = 65.0,
                highlights = listOf("Market tour", "5 authentic dishes", "Recipe booklet", "Small group size"),
                includes = listOf("Market tour", "All ingredients", "Cooking instruction", "Meal with drinks", "Recipe cards"),
                images = listOf("https://images.unsplash.com/photo-1504674900247-0877df9cc836", "https://images.unsplash.com/photo-1555939594-58d7cb561ad1"),
                hostName = "Chef Nok's Kitchen",
                hostBio = "Former head chef at 5-star hotel, now sharing authentic Thai cooking secrets",
                rating = 4.8,
                reviewCount = 189,
                availableDates = listOf(
                    java.time.LocalDate.now().plusDays(1),
                    java.time.LocalDate.now().plusDays(3),
                    java.time.LocalDate.now().plusDays(5)
                )
            ),
            Experience(
                id = "exp-003",
                title = "Kyoto Tea Ceremony & Temple Tour",
                description = "Immerse yourself in Japanese culture with a traditional tea ceremony experience followed by a guided tour of ancient temples and gardens.",
                location = "Kyoto, Japan",
                category = ExperienceCategory.CULTURAL,
                duration = 5,
                maxParticipants = 6,
                pricePerPerson = 120.0,
                highlights = listOf("Authentic tea ceremony", "Zen garden meditation", "Temple exploration", "Kimono wearing option"),
                includes = listOf("Tea ceremony", "Temple admission", "English guide", "Traditional sweets", "Kimono rental"),
                images = listOf("https://images.unsplash.com/photo-1545569341-9eb8b30979d9", "https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e"),
                hostName = "Kyoto Cultural Experiences",
                hostBio = "Certified tea master with deep knowledge of Japanese traditions and history",
                rating = 4.9,
                reviewCount = 312,
                availableDates = listOf(
                    java.time.LocalDate.now().plusDays(2),
                    java.time.LocalDate.now().plusDays(5),
                    java.time.LocalDate.now().plusDays(7)
                )
            ),
            Experience(
                id = "exp-004",
                title = "Bali Yoga & Wellness Retreat",
                description = "Rejuvenate your mind and body with a full day wellness retreat in Ubud. Includes yoga sessions, spa treatments, and healthy organic meals.",
                location = "Ubud, Bali, Indonesia",
                category = ExperienceCategory.WELLNESS,
                duration = 8,
                maxParticipants = 10,
                pricePerPerson = 150.0,
                highlights = listOf("Sunrise yoga", "Balinese massage", "Organic lunch", "Meditation session"),
                includes = listOf("2 yoga sessions", "60-min massage", "Organic meals", "Pool access", "Transportation"),
                images = listOf("https://images.unsplash.com/photo-1545205597-3d9d02c29597", "https://images.unsplash.com/photo-1506126613408-eca07ce68773"),
                hostName = "Bali Wellness Sanctuary",
                hostBio = "Certified yoga instructors and wellness experts dedicated to holistic healing",
                rating = 4.7,
                reviewCount = 156,
                availableDates = listOf(
                    java.time.LocalDate.now().plusDays(1),
                    java.time.LocalDate.now().plusDays(4),
                    java.time.LocalDate.now().plusDays(6)
                )
            ),
            Experience(
                id = "exp-005",
                title = "Street Art & Graffiti Workshop",
                description = "Explore Melbourne's famous laneways and learn spray paint techniques from a local street artist. Create your own piece to take home!",
                location = "Melbourne, Australia",
                category = ExperienceCategory.ARTS,
                duration = 3,
                maxParticipants = 8,
                pricePerPerson = 75.0,
                highlights = listOf("Laneway tour", "Spray paint techniques", "Create your own art", "Take home canvas"),
                includes = listOf("All art supplies", "Safety equipment", "Canvas to keep", "Drinks", "Professional artist guide"),
                images = listOf("https://images.unsplash.com/photo-1561839561-b13bcfe95249", "https://images.unsplash.com/photo-1571115764595-644a1f56a55c"),
                hostName = "Melbourne Street Art Tours",
                hostBio = "Local street artist showcasing Melbourne's vibrant underground art scene",
                rating = 4.6,
                reviewCount = 98,
                availableDates = listOf(
                    java.time.LocalDate.now().plusDays(2),
                    java.time.LocalDate.now().plusDays(3),
                    java.time.LocalDate.now().plusDays(5)
                )
            )
        )

        sampleExperiences.forEach { save(it) }
    }
}
